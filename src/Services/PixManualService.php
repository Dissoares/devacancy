<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PIX sem gateway: parte do "PIX copia e cola" estático do próprio dono (PIX_COPIA_COLA no .env) e monta, para cada
 * pagamento, um código novo com o valor do plano e um identificador (txid). O QR code é desenhado no navegador a partir
 * desse mesmo texto. Quem confirma o recebimento é o admin.
 *
 * Formato: BR Code do Banco Central (EMV): sequência de campos "ID(2) + TAMANHO(2) + VALOR", terminada pelo CRC16.
 */
final class PixManualService
{
    /** Ordem em que os campos devem aparecer no código final. */
    private const ORDEM_FIXA = ['00', '01', '26', '52', '53', '54', '58', '59', '60', '61', '62', '63'];

    public function configurado(): bool
    {
        return $this->base() !== null;
    }

    /** Por que o código do .env não serve, para a tela de diagnóstico do admin. Null quando está tudo certo. */
    public function problema(): ?string
    {
        $codigo = trim((string) configuracao('pix.copia_cola'));
        if ($codigo === '') {
            return 'PIX_COPIA_COLA não está no .env.';
        }
        $campos = $this->ler($codigo);
        if ($campos === null) {
            return 'O código PIX_COPIA_COLA está incompleto ou foi copiado errado (o final não confere).';
        }
        if (!isset($campos['26'])) {
            return 'O código não tem os dados da conta PIX.';
        }
        $conta = $this->ler($campos['26'], false) ?? [];
        if (isset($conta['25'])) {
            return 'Este é um código de cobrança (com link do banco), não de chave. Gere um "PIX copia e cola" estático pela sua chave.';
        }
        return null;
    }

    /** Nome e cidade que aparecem para o cliente no app do banco. @return array{nome: string, cidade: string, chave: string}|null */
    public function resumo(): ?array
    {
        $campos = $this->base();
        if ($campos === null) {
            return null;
        }
        $conta = $this->ler($campos['26'], false) ?? [];
        return ['nome' => $campos['59'] ?? '', 'cidade' => $campos['60'] ?? '', 'chave' => $this->mascarar($conta['01'] ?? '')];
    }

    /** Código copia e cola com valor fixo e txid (letras e números, até 25). */
    public function codigoPara(float $valor, string $txid): string
    {
        $base = $this->base() ?? throw new \RuntimeException('PIX_COPIA_COLA não configurado.');
        $txid = substr(preg_replace('/[^A-Za-z0-9]/', '', $txid) ?: '***', 0, 25);

        $campos = $base;
        $campos['00'] = '01';
        $campos['01'] = '11';
        $campos['52'] ??= '0000';
        $campos['53'] = '986';
        $campos['54'] = number_format($valor, 2, '.', '');
        $campos['58'] = 'BR';
        $campos['59'] = $this->limitar($campos['59'] ?? 'DEVACANCY', 25);
        $campos['60'] = $this->limitar($campos['60'] ?? 'SAO PAULO', 15);
        $campos['62'] = $this->campo('05', $txid);
        unset($campos['63']);

        $payload = '';
        foreach ($this->ordenar($campos) as $id => $valorCampo) {
            $payload .= $this->campo((string) $id, $valorCampo);
        }
        $payload .= '6304';
        return $payload . self::crc16($payload);
    }

    /** CRC16/CCITT-FALSE (polinômio 0x1021, início 0xFFFF), em 4 caracteres hexadecimais maiúsculos. */
    public static function crc16(string $texto): string
    {
        $crc = 0xFFFF;
        foreach (str_split($texto) as $caractere) {
            $crc ^= ord($caractere) << 8;
            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    // ---------------------------------------------------------------- Interno

    /** Campos do código do .env, ou null se estiver quebrado ou for do tipo cobrança. @return array<string, string>|null */
    private function base(): ?array
    {
        if ($this->problema() !== null) {
            return null;
        }
        return $this->ler(trim((string) configuracao('pix.copia_cola')));
    }

    /**
     * Lê campos "ID+TAMANHO+VALOR". Com $conferirCrc, exige o CRC16 correto no final (só vale para o código inteiro).
     * @return array<string, string>|null
     */
    private function ler(string $texto, bool $conferirCrc = true): ?array
    {
        // Só quebras de linha e pontas saem: espaços fazem parte de nomes como "DIEGO SOARES".
        $texto = trim(preg_replace('/[\r\n\t]+/', '', $texto) ?? '');
        if ($conferirCrc) {
            if (strlen($texto) < 12 || !str_contains($texto, '6304') || strtoupper(substr($texto, -4)) !== self::crc16(substr($texto, 0, -4))) {
                return null;
            }
        }

        $campos = [];
        $posicao = 0;
        $total = strlen($texto);
        while ($posicao + 4 <= $total) {
            $id = substr($texto, $posicao, 2);
            $tamanho = substr($texto, $posicao + 2, 2);
            if (!ctype_digit($tamanho)) {
                return null;
            }
            $valor = substr($texto, $posicao + 4, (int) $tamanho);
            if (strlen($valor) !== (int) $tamanho) {
                return null;
            }
            $campos[$id] = $valor;
            $posicao += 4 + (int) $tamanho;
        }
        return $posicao === $total ? $campos : null;
    }

    private function campo(string $id, string $valor): string
    {
        return $id . str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    /** @param array<string, string> $campos @return array<string, string> */
    private function ordenar(array $campos): array
    {
        uksort($campos, function (string $a, string $b): int {
            $posicao = function (string $id): int {
                $indice = array_search($id, self::ORDEM_FIXA, true);
                return $indice !== false ? $indice : (((int) $id >= 27 && (int) $id <= 51) ? 2 : 99);
            };
            return [$posicao($a), $a] <=> [$posicao($b), $b];
        });
        return $campos;
    }

    private function limitar(string $texto, int $maximo): string
    {
        return strtoupper(mb_substr(sem_acentos($texto), 0, $maximo));
    }

    private function mascarar(string $chave): string
    {
        return strlen($chave) <= 6 ? $chave : substr($chave, 0, 3) . '…' . substr($chave, -3);
    }
}
