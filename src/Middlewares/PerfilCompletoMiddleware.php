<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Interfaces\MiddlewareInterface;
use App\Services\AutenticacaoService;

/** Usar depois do AutenticadoMiddleware: envia para a etapa de perfil quem ainda não terminou o cadastro. */
final class PerfilCompletoMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    public function processar(Requisicao $requisicao, callable $proximo): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();

        if ($usuario->ehAdmin()) {
            return Resposta::redirecionar('/admin');
        }
        if (!$usuario->cadastroCompleto) {
            return Resposta::redirecionar('/completar-perfil');
        }

        return $proximo($requisicao);
    }
}
