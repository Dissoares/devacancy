<?php

declare(strict_types=1);

namespace App\Core;

final class Requisicao
{
    private array $parametrosRota = [];

    public function __construct(
        private readonly string $metodo,
        private readonly string $caminho,
        private readonly array $consulta,
        private readonly array $corpo,
        private readonly array $arquivos,
        private readonly array $cabecalhos,
        private readonly string $ip,
    ) {
    }

    public static function capturar(): self
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $caminho = '/' . trim($caminho, '/');

        $cabecalhos = [];
        foreach ($_SERVER as $chave => $valor) {
            if (str_starts_with($chave, 'HTTP_')) {
                $cabecalhos[strtolower(str_replace('_', '-', substr($chave, 5)))] = $valor;
            }
        }

        // O visitante pode mandar qualquer cabeçalho: este só existe se a Cloudflare (conferida pelo IP de origem) o preencher.
        unset($cabecalhos['x-pais-confiavel']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (self::ehCloudflare($ip)) {
            $ipReal = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
            $ip = filter_var($ipReal, FILTER_VALIDATE_IP) ? $ipReal : $ip;
            $pais = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
            if (preg_match('/^[A-Z]{2}$/', $pais) && $pais !== 'XX' && $pais !== 'T1') {
                $cabecalhos['x-pais-confiavel'] = $pais;
            }
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $caminho,
            self::utf8Valido($_GET),
            self::utf8Valido($_POST),
            $_FILES,
            $cabecalhos,
            $ip,
        );
    }

    /** Faixas de IP da Cloudflare (cloudflare.com/ips). Só quem chega por elas pode informar o IP e o país reais do visitante. */
    private const CLOUDFLARE = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public static function ehCloudflare(string $ip): bool
    {
        $binario = @inet_pton($ip);
        if ($binario === false) {
            return false;
        }

        foreach (self::CLOUDFLARE as $faixa) {
            [$rede, $bits] = explode('/', $faixa);
            $redeBinaria = inet_pton($rede);
            if (strlen($redeBinaria) !== strlen($binario)) {
                continue;
            }
            $bytes = intdiv((int) $bits, 8);
            $resto = (int) $bits % 8;
            if (substr($binario, 0, $bytes) !== substr($redeBinaria, 0, $bytes)) {
                continue;
            }
            if ($resto === 0 || ((ord($binario[$bytes]) ^ ord($redeBinaria[$bytes])) >> (8 - $resto)) === 0) {
                return true;
            }
        }
        return false;
    }

    /** Troca bytes que não são UTF-8 válido, para que um texto malformado não derrube o banco. */
    private static function utf8Valido(array $dados): array
    {
        array_walk_recursive($dados, function (mixed &$valor): void {
            if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                $valor = mb_convert_encoding($valor, 'UTF-8', 'UTF-8');
            }
        });
        return $dados;
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function caminho(): string
    {
        return $this->caminho;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function consulta(string $chave, mixed $padrao = null): mixed
    {
        $valor = $this->consulta[$chave] ?? $padrao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    /** Toda a query string (com arrays, como modelos[]=...). */
    public function consultas(): array
    {
        return $this->consulta;
    }

    public function entrada(string $chave, mixed $padrao = null): mixed
    {
        $valor = $this->corpo[$chave] ?? $padrao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    public function todos(): array
    {
        return array_map(fn ($valor) => is_string($valor) ? trim($valor) : $valor, $this->corpo);
    }

    /** Corpo JSON (webhooks). Vazio se não for JSON válido. */
    public function json(): array
    {
        $dados = json_decode((string) file_get_contents('php://input'), true);
        return is_array($dados) ? $dados : [];
    }

    public function arquivo(string $chave): ?array
    {
        $arquivo = $this->arquivos[$chave] ?? null;
        return ($arquivo && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $arquivo : null;
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function ehHtmx(): bool
    {
        return $this->cabecalho('hx-request') === 'true';
    }

    public function definirParametrosRota(array $parametros): void
    {
        $this->parametrosRota = $parametros;
    }

    public function parametro(string $chave): ?string
    {
        return $this->parametrosRota[$chave] ?? null;
    }
}
