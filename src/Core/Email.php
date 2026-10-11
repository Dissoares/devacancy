<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Envio de e-mail transacional. Com MAIL_SMTP_HOST usa SMTP (recomendado: cai menos no spam);
 * sem ele usa o mail() do PHP (funciona na maioria das hospedagens compartilhadas).
 * Em ambiente local, sem SMTP, só grava a mensagem em storage/logs/emails.log.
 */
final class Email
{
    /** Motivo da última falha de envio (para a tela de diagnóstico do admin). */
    public ?string $ultimoErro = null;

    public function enviar(string $para, string $assunto, string $texto, string $html): bool
    {
        $this->ultimoErro = null;
        $de = (string) configuracao('email.de');
        $nome = (string) configuracao('email.nome');
        $limite = 'dv' . bin2hex(random_bytes(8));

        $cabecalhos = [
            'From: ' . $this->codificar($nome) . " <{$de}>",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$limite}\"",
        ];
        $corpo = "--{$limite}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($texto))
            . "--{$limite}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--{$limite}--\r\n";
        $assuntoCodificado = $this->codificar($assunto);

        if ((string) configuracao('email.smtp_host') !== '') {
            return $this->viaSmtp($para, $de, $assuntoCodificado, $cabecalhos, $corpo);
        }

        if (ambiente_local()) {
            $this->registrar("Para: {$para}\nAssunto: {$assunto}\n\n{$texto}\n");
            return true;
        }

        $enviado = @mail($para, $assuntoCodificado, $corpo, implode("\r\n", $cabecalhos), '-f' . $de);
        $this->ultimoErro = $enviado ? null : 'O mail() da hospedagem recusou o envio. Configure o SMTP.';
        $this->registrar($enviado
            ? "mail(): aceito pelo servidor da hospedagem para {$para} (remetente {$de}). Se não chegar, confira o spam e o SPF/DKIM do domínio ou use SMTP."
            : "mail(): a hospedagem recusou o envio para {$para}. Configure o SMTP (MAIL_SMTP_HOST).");
        return $enviado;
    }

    private function codificar(string $texto): string
    {
        return preg_match('/^[\x20-\x7e]*$/', $texto) === 1 ? $texto : '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }

    private function viaSmtp(string $para, string $de, string $assunto, array $cabecalhos, string $corpo): bool
    {
        $host = (string) configuracao('email.smtp_host');
        $porta = (int) configuracao('email.smtp_porta');
        $seguranca = (string) configuracao('email.smtp_seguranca');

        // MAIL_SMTP_VERIFICAR=nao aceita certificado que não bate com o nome do servidor (comum em cPanel).
        $contexto = stream_context_create(configuracao('email.smtp_verificar') === 'nao'
            ? ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]
            : []);
        $socket = @stream_socket_client(($seguranca === 'ssl' ? 'ssl' : 'tcp') . "://{$host}:{$porta}", $codigo, $mensagem, 15, STREAM_CLIENT_CONNECT, $contexto);
        if (!$socket) {
            $this->ultimoErro = "Não conectou em {$host}:{$porta}: {$mensagem}";
            $this->registrar('SMTP: ' . $this->ultimoErro);
            return false;
        }
        stream_set_timeout($socket, 15);

        try {
            $dominio = parse_url((string) configuracao('app.url'), PHP_URL_HOST) ?: 'localhost';
            $this->esperar($socket, '220');
            $this->comando($socket, "EHLO {$dominio}", '250');

            if ($seguranca === 'tls') {
                $this->comando($socket, 'STARTTLS', '220');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Falha ao iniciar TLS.');
                }
                $this->comando($socket, "EHLO {$dominio}", '250');
            }

            $usuario = (string) configuracao('email.smtp_usuario');
            if ($usuario !== '') {
                $this->comando($socket, 'AUTH LOGIN', '334');
                $this->comando($socket, base64_encode($usuario), '334');
                $this->comando($socket, base64_encode((string) configuracao('email.smtp_senha')), '235');
            }

            $this->comando($socket, "MAIL FROM:<{$de}>", '250');
            $this->comando($socket, "RCPT TO:<{$para}>", '25');
            $this->comando($socket, 'DATA', '354');

            $mensagemCompleta = implode("\r\n", [
                ...$cabecalhos,
                "To: <{$para}>",
                "Subject: {$assunto}",
                'Date: ' . date('r'),
                'Message-ID: <' . bin2hex(random_bytes(12)) . "@{$dominio}>",
            ]) . "\r\n\r\n" . $corpo;
            // Linhas começadas por "." precisam ser duplicadas no protocolo SMTP.
            $this->comando($socket, preg_replace('/^\./m', '..', $mensagemCompleta) . "\r\n.", '250');
            @fwrite($socket, "QUIT\r\n");
            $this->registrar("SMTP: enviado para {$para} via {$host}:{$porta}.");

            return true;
        } catch (RuntimeException $erro) {
            $this->ultimoErro = $erro->getMessage();
            $this->registrar('SMTP: ' . $erro->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function comando($socket, string $linha, string $esperado): void
    {
        fwrite($socket, $linha . "\r\n");
        $this->esperar($socket, $esperado);
    }

    /** @param resource $socket */
    private function esperar($socket, string $esperado): void
    {
        do {
            $resposta = fgets($socket, 1024);
            if ($resposta === false) {
                throw new RuntimeException('O servidor SMTP parou de responder.');
            }
        } while (isset($resposta[3]) && $resposta[3] === '-');

        if (!str_starts_with($resposta, $esperado)) {
            throw new RuntimeException('Resposta inesperada do SMTP: ' . trim($resposta));
        }
    }

    private function registrar(string $texto): void
    {
        $pasta = RAIZ . '/storage/logs';
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        @file_put_contents($pasta . '/emails.log', '[' . date('Y-m-d H:i:s') . "]\n{$texto}\n---\n", FILE_APPEND);
    }
}
