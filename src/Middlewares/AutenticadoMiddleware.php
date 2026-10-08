<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Interfaces\MiddlewareInterface;
use App\Services\AutenticacaoService;

final class AutenticadoMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    public function processar(Requisicao $requisicao, callable $proximo): Resposta
    {
        if ($this->autenticacao->usuarioAtual() === null) {
            Sessao::flash('aviso', 'Entre na sua conta para continuar.');
            return $requisicao->ehHtmx() ? Resposta::redirecionarHtmx('/entrar') : Resposta::redirecionar('/entrar');
        }

        return $proximo($requisicao);
    }
}
