<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Exceptions\NaoEncontradoException;
use App\Interfaces\MiddlewareInterface;
use App\Services\AutenticacaoService;

final class AdminMiddleware implements MiddlewareInterface
{
    /** Na primeira visita de um admin sem endereço salvo, guarda o endereço do acesso (links de e-mail passam a funcionar). */
    private function fixarEnderecoDoSite(): void
    {
        if (trim((string) configuracao('app.url')) !== '') {
            return;
        }
        try {
            $endereco = endereco_do_site();
            if ($endereco !== '') {
                \App\Core\Conexao::obter()->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute(['APP_URL', $endereco]);
                \App\Core\Ambiente::esquecerPainel();
            }
        } catch (\Throwable $e) {
            error_log('Endereço do site não salvo: ' . $e->getMessage());
        }
    }

    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    public function processar(Requisicao $requisicao, callable $proximo): Resposta
    {
        // 404 em vez de 403: não revela que a área administrativa existe.
        if (!$this->autenticacao->usuarioAtual()?->ehAdmin()) {
            throw new NaoEncontradoException();
        }

        $this->fixarEnderecoDoSite();

        return $proximo($requisicao);
    }
}
