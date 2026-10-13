<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\LimiteDeTentativas;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\PerfilEmpresa;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\TipoUsuario;
use App\Exceptions\ValidacaoException;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;
use App\Services\CandidaturaService;
use App\Services\ComissaoService;
use App\Services\UsuarioService;
use App\Services\VagaService;

final class UsuarioController
{
    public function __construct(
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
        private readonly VagaService $vagas,
        private readonly AssinaturaService $assinaturas,
        private readonly CandidaturaService $candidaturas,
        private readonly ComissaoService $comissoes,
    ) {
    }

    public function formularioCadastro(Requisicao $requisicao): Resposta
    {
        $tipo = TipoUsuario::tryFrom((string) $requisicao->consulta('tipo', '')) ?? TipoUsuario::Profissional;

        // Profissionais entram pelo questionário; este formulário fica para empresas.
        if ($tipo === TipoUsuario::Profissional) {
            return Resposta::redirecionar('/comecar');
        }

        return Resposta::html(Visao::renderizar('auth/cadastro', [
            'titulo' => t('cadastro.titulo_pagina'),
            'tipoLateral' => $tipo->value,
            'tipo' => $tipo,
            'valores' => ['tipo' => $tipo->value],
            'erros' => [],
            'erro' => Sessao::obterFlash('erro'),
        ], 'auth'));
    }

    public function cadastrar(Requisicao $requisicao): Resposta
    {
        $dados = $requisicao->todos();
        $chaveIp = 'cadastro-ip:' . $requisicao->ip();

        if (LimiteDeTentativas::excedeu($chaveIp, 20)) {
            return Resposta::html(Visao::parcial('auth/formulario-cadastro', [
                'tipo' => TipoUsuario::tryFrom((string) ($dados['tipo'] ?? '')) ?? TipoUsuario::Profissional,
                'valores' => array_diff_key($dados, ['senha' => 1]),
                'erros' => ['email' => t('auth.muitas_tentativas')],
            ]));
        }
        LimiteDeTentativas::registrar($chaveIp, 3600);

        try {
            $usuario = $this->usuarios->cadastrar($dados);
        } catch (ValidacaoException $erro) {
            $tipo = TipoUsuario::tryFrom((string) ($dados['tipo'] ?? '')) ?? TipoUsuario::Profissional;
            unset($dados['senha']);

            return Resposta::html(Visao::parcial('auth/formulario-cadastro', [
                'tipo' => $tipo,
                'valores' => $dados,
                'erros' => $erro->erros(),
            ]));
        }

        $this->autenticacao->iniciarSessao($usuario);
        return Resposta::redirecionarHtmx('/completar-perfil');
    }

    /** Verificação ao vivo enquanto a pessoa digita o e-mail (HTMX). */
    public function verificarEmail(Requisicao $requisicao): Resposta
    {
        $email = (string) $requisicao->entrada('email', '');

        $mensagem = match (true) {
            $email === '' => '',
            !filter_var($email, FILTER_VALIDATE_EMAIL) => '',
            !$this->usuarios->emailDisponivel($email) => '<span class="dica-campo dica-erro">' . e(t('cadastro.email_existe_curto')) . ' <a href="/entrar">' . e(t('auth.entrar')) . '</a></span>',
            default => '<span class="dica-campo dica-ok">' . icone('check', 'icone icone-sm') . ' ' . e(t('cadastro.email_disponivel')) . '</span>',
        };

        return Resposta::html($mensagem);
    }

    public function formularioPerfil(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if ($usuario->ehAdmin()) {
            return Resposta::redirecionar('/admin');
        }

        $perfil = $this->usuarios->perfilDe($usuario);

        return Resposta::html(Visao::renderizar('profile/completar', [
            'titulo' => t('perfil.titulo_pagina'),
            'tipoLateral' => $usuario->tipo->value,
            'usuario' => $usuario,
            'valores' => $this->valoresIniciaisDoPerfil($usuario, $perfil),
            'perfil' => $perfil,
            'erros' => [],
        ], 'auth'));
    }

    public function salvarPerfil(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $dados = $requisicao->todos();

        try {
            if ($usuario->ehEmpresa()) {
                $this->usuarios->salvarPerfilEmpresa($usuario, $dados);
            } else {
                $this->usuarios->salvarPerfilProfissional($usuario, $dados, $requisicao->arquivo('curriculo'));
            }
        } catch (ValidacaoException $erro) {
            return Resposta::html(Visao::parcial('profile/formulario', [
                'usuario' => $usuario,
                'valores' => $dados,
                'perfil' => $this->usuarios->perfilDe($usuario),
                'erros' => $erro->erros(),
            ]));
        }

        Sessao::remover('sugestoes_perfil');

        // Fim do cadastro do profissional: mostra as vagas compatíveis antes do painel.
        if (!$usuario->cadastroCompleto && $usuario->ehProfissional()) {
            return Resposta::redirecionarHtmx('/vagas-compativeis');
        }

        Sessao::flash('sucesso', t($usuario->cadastroCompleto ? 'perfil.atualizado' : 'perfil.concluido'));
        return Resposta::redirecionarHtmx('/painel');
    }

    public function painel(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $perfil = $this->usuarios->perfilDe($usuario);
        $compativeis = $perfil instanceof PerfilProfissional ? $this->vagas->compativeis($perfil) : [];
        $codigoIndicacao = $usuario->ehProfissional() ? $this->usuarios->garantirCodigoIndicacao($usuario) : null;

        return Resposta::html(Visao::renderizar('dashboard/inicio', [
            'titulo' => t('painel.titulo_pagina'),
            'usuario' => $usuario,
            'perfil' => $perfil,
            'compativeis' => $compativeis,
            'recentes' => $this->vagas->contarRecentes($compativeis),
            'assinaturaAte' => $usuario->ehProfissional() ? $this->assinaturas->vigenteAte($usuario) : null,
            'statusCandidaturas' => $usuario->ehProfissional() ? $this->candidaturas->statusPorVaga($usuario) : [],
            'restantesHoje' => $usuario->ehProfissional() ? $this->candidaturas->restantesHoje($usuario) : 0,
            'limiteDiario' => $this->candidaturas->limiteDiario(),
            'linkIndicacao' => $codigoIndicacao ? $this->comissoes->linkDeIndicacao($codigoIndicacao) : null,
            'indicacoes' => $usuario->ehProfissional() ? $this->comissoes->painelDoUsuario($usuario) : null,
            'sucesso' => Sessao::obterFlash('sucesso'),
            'aviso' => Sessao::obterFlash('aviso'),
        ], 'app'));
    }

    /** Preenche o formulário com o perfil salvo ou com o que veio do login social. */
    private function valoresIniciaisDoPerfil(Usuario $usuario, PerfilProfissional|PerfilEmpresa|null $perfil): array
    {
        $valores = ['telefone' => $usuario->telefone, ...Sessao::obter('sugestoes_perfil', [])];

        if ($perfil instanceof PerfilProfissional) {
            return [...$valores, ...$perfil->comoFormulario()];
        }

        if ($perfil instanceof PerfilEmpresa) {
            return [
                ...$valores,
                'nome_empresa' => $perfil->nomeEmpresa,
                'site' => $perfil->site,
                'tamanho' => $perfil->tamanho->value,
                'cargo_contato' => $perfil->cargoContato,
                'stacks_contratacao' => implode(', ', $perfil->stacksContratacao),
                'vagas_abertas' => $perfil->vagasAbertas,
                'mensagem' => $perfil->mensagem,
            ];
        }

        return $valores;
    }
}
