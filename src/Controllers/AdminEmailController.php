<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Email;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\AutenticacaoService;

/** Diagnóstico do envio de e-mail: mostra o que o servidor leu do .env e manda um e-mail de teste. */
final class AdminEmailController
{
    public function __construct(
        private readonly AutenticacaoService $autenticacao,
        private readonly Email $email,
    ) {
    }

    public function painel(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/email', [
            'titulo' => 'E-mail — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'config' => $this->configuracaoLida(),
            'teste' => Sessao::retirar('teste_email'),
        ], 'app'));
    }

    public function testar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $nome = (string) configuracao('app.nome');

        $enviado = $this->email->enviar(
            $usuario->email,
            "Teste de e-mail do {$nome}",
            "Se você leu isto, o envio de e-mails do {$nome} está funcionando.",
            "<p>Se você leu isto, o envio de e-mails do <strong>" . e($nome) . "</strong> está funcionando.</p>",
        );

        Sessao::definir('teste_email', [
            'ok' => $enviado,
            'para' => $usuario->email,
            'erro' => $this->email->ultimoErro,
        ]);
        return Resposta::redirecionar('/admin/email');
    }

    /** Nunca expõe a senha: só o tamanho e sinais de problema comuns de digitação no .env. */
    private function configuracaoLida(): array
    {
        $senha = (string) configuracao('email.smtp_senha');
        $usuario = (string) configuracao('email.smtp_usuario');

        return [
            'modo' => (string) configuracao('email.smtp_host') !== '' ? 'SMTP' : 'mail() do PHP',
            'de' => (string) configuracao('email.de'),
            'host' => (string) configuracao('email.smtp_host'),
            'porta' => (string) configuracao('email.smtp_porta'),
            'seguranca' => (string) configuracao('email.smtp_seguranca'),
            'verificar' => (string) configuracao('email.smtp_verificar'),
            'usuario' => $usuario,
            'usuario_completo' => str_contains($usuario, '@'),
            'senha_tamanho' => mb_strlen($senha),
            'senha_suspeita' => $senha !== trim($senha) || preg_match('/["\'\s]/', $senha) === 1,
            'url' => (string) configuracao('app.url'),
        ];
    }
}
