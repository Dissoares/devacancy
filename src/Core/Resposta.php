<?php

declare(strict_types=1);

namespace App\Core;

final class Resposta
{
    private function __construct(
        private string $conteudo = '',
        private int $status = 200,
        private array $cabecalhos = [],
        private ?string $arquivo = null,
    ) {
    }

    public static function html(string $conteudo, int $status = 200): self
    {
        return new self($conteudo, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function texto(string $conteudo, int $status = 200): self
    {
        return new self($conteudo, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function json(array $dados, int $status = 200): self
    {
        return new self((string) json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirecionar(string $destino): self
    {
        return new self('', 302, ['Location' => url($destino)]);
    }

    /** Redireciona o navegador inteiro a partir de uma requisição HTMX. */
    public static function redirecionarHtmx(string $destino): self
    {
        return new self('', 200, ['HX-Redirect' => url($destino)]);
    }

    public static function download(string $caminho, string $nome, string $tipo): self
    {
        return new self('', 200, [
            'Content-Type' => $tipo,
            'Content-Disposition' => 'inline; filename="' . addslashes($nome) . '"',
            'Content-Length' => (string) filesize($caminho),
            'X-Content-Type-Options' => 'nosniff',
        ], $caminho);
    }

    public static function csv(string $conteudo, string $nome): self
    {
        return new self("\xEF\xBB\xBF" . $conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nome . '"',
        ]);
    }

    public function comCabecalho(string $nome, string $valor): self
    {
        $this->cabecalhos[$nome] = $valor;
        return $this;
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        foreach ($this->cabecalhos as $nome => $valor) {
            header("{$nome}: {$valor}");
        }

        if ($this->arquivo !== null) {
            readfile($this->arquivo);
            return;
        }

        echo $this->conteudo;
    }
}
