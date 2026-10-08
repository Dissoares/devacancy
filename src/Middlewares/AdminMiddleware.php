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
    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    public function processar(Requisicao $requisicao, callable $proximo): Resposta
    {
        // 404 em vez de 403: não revela que a área administrativa existe.
        if (!$this->autenticacao->usuarioAtual()?->ehAdmin()) {
            throw new NaoEncontradoException();
        }

        return $proximo($requisicao);
    }
}
