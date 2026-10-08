<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Interfaces\MiddlewareInterface;
use App\Services\AutenticacaoService;

/** Telas de entrar/cadastrar: quem já está logado vai direto para a sua área. */
final class VisitanteMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    public function processar(Requisicao $requisicao, callable $proximo): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if ($usuario !== null) {
            return Resposta::redirecionar($this->autenticacao->destinoInicial($usuario));
        }

        return $proximo($requisicao);
    }
}
