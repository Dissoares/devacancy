<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Enums\TipoUsuario;
use App\Exceptions\AutenticacaoException;
use App\Services\AutenticacaoService;
use App\Services\OAuthService;

final class AutenticacaoController
{
    public function __construct(
        private readonly AutenticacaoService $autenticacao,
        private readonly OAuthService $oauth,
    ) {
    }

    public function formularioEntrar(Requisicao $requisicao): Resposta
    {
        return Resposta::html(Visao::renderizar('auth/entrar', [
            'titulo' => 'Entrar — Devacancy',
            'erro' => Sessao::obterFlash('erro'),
            'aviso' => Sessao::obterFlash('aviso'),
            'email' => '',
        ], 'auth'));
    }

    public function entrar(Requisicao $requisicao): Resposta
    {
        $email = (string) $requisicao->entrada('email', '');

        try {
            $usuario = $this->autenticacao->entrar($email, (string) $requisicao->entrada('senha', ''));
        } catch (AutenticacaoException $erro) {
            return Resposta::html(Visao::parcial('auth/formulario-entrar', [
                'erro' => $erro->getMessage(),
                'email' => $email,
            ]));
        }

        $this->autenticacao->iniciarSessao($usuario);
        return Resposta::redirecionarHtmx($this->autenticacao->destinoInicial($usuario));
    }

    public function sair(Requisicao $requisicao): Resposta
    {
        $this->autenticacao->sair();
        return Resposta::redirecionar('/');
    }

    public function redirecionarParaProvedor(Requisicao $requisicao): Resposta
    {
        $tipo = TipoUsuario::tryFrom((string) $requisicao->consulta('tipo', '')) ?? TipoUsuario::Profissional;

        try {
            return Resposta::redirecionar($this->oauth->iniciar((string) $requisicao->parametro('provedor'), $tipo));
        } catch (AutenticacaoException $erro) {
            Sessao::flash('erro', $erro->getMessage());
            $origem = $requisicao->consulta('origem') === 'entrar' ? '/entrar' : '/cadastro?tipo=' . $tipo->value;
            return Resposta::redirecionar($origem);
        }
    }

    public function retornoDoProvedor(Requisicao $requisicao): Resposta
    {
        try {
            [$social, $tipo] = $this->oauth->concluir(
                (string) $requisicao->parametro('provedor'),
                $requisicao->consulta('code'),
                $requisicao->consulta('state'),
            );
        } catch (AutenticacaoException $erro) {
            Sessao::flash('erro', $erro->getMessage());
            return Resposta::redirecionar('/entrar');
        }

        $usuario = $this->autenticacao->autenticarComProvedor($social, $tipo);
        $this->autenticacao->iniciarSessao($usuario);

        return Resposta::redirecionar($this->autenticacao->destinoInicial($usuario));
    }
}
