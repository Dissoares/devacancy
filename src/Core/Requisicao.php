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

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $caminho,
            self::utf8Valido($_GET),
            self::utf8Valido($_POST),
            $_FILES,
            $cabecalhos,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        );
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
