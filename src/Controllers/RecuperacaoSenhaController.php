<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\AutenticacaoException;
use App\Exceptions\ValidacaoException;
use App\Services\RecuperacaoSenhaService;

final class RecuperacaoSenhaController
{
    public function __construct(private readonly RecuperacaoSenhaService $recuperacao)
    {
    }

    public function formularioPedido(Requisicao $requisicao): Resposta
    {
        return $this->paginaPedido(['email' => (string) $requisicao->consulta('email', '')]);
    }

    public function pedir(Requisicao $requisicao): Resposta
    {
        $email = trim((string) $requisicao->entrada('email', ''));

        try {
            $this->recuperacao->solicitar($email);
        } catch (ValidacaoException $erro) {
            return $this->paginaPedido(['email' => $email, 'erro' => $erro->erros()['email'] ?? t('validacao.obrigatorio')], 422);
        } catch (AutenticacaoException $erro) {
            return $this->paginaPedido(['email' => $email, 'erro' => $erro->getMessage()], 429);
        }

        return $this->paginaPedido(['email' => $email, 'enviado' => true]);
    }

    public function formularioNovaSenha(Requisicao $requisicao): Resposta
    {
        $token = (string) $requisicao->parametro('token');
        return $this->paginaNovaSenha($token, $this->recuperacao->linkValido($token));
    }

    public function redefinir(Requisicao $requisicao): Resposta
    {
        $token = (string) $requisicao->parametro('token');

        try {
            $this->recuperacao->redefinir(
                $token,
                (string) $requisicao->entrada('senha', ''),
                (string) $requisicao->entrada('confirmacao', ''),
            );
        } catch (ValidacaoException $erro) {
            return $this->paginaNovaSenha($token, true, $erro->erros(), 422);
        } catch (AutenticacaoException) {
            return $this->paginaNovaSenha($token, false, [], 410);
        }

        Sessao::flash('sucesso', t('recuperar.senha_alterada'));
        return Resposta::redirecionar('/entrar');
    }

    private function paginaPedido(array $dados, int $status = 200): Resposta
    {
        return Resposta::html(Visao::renderizar('auth/esqueci-senha', [
            'titulo' => t('recuperar.titulo_pagina'),
            'erro' => null,
            'enviado' => false,
            ...$dados,
        ], 'auth'), $status);
    }

    private function paginaNovaSenha(string $token, bool $valido, array $erros = [], int $status = 200): Resposta
    {
        return Resposta::html(Visao::renderizar('auth/nova-senha', [
            'titulo' => t('recuperar.nova_titulo_pagina'),
            'token' => $token,
            'valido' => $valido,
            'erros' => $erros,
        ], 'auth'), $valido ? $status : 410);
    }
}
