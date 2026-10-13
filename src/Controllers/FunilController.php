<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Idioma;
use App\Core\LimiteDeTentativas;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\PerfilProfissional;
use App\Entities\Vaga;
use App\Enums\AreaAtuacao;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\FunilService;
use App\Services\LocalizacaoService;
use App\Services\UsuarioService;
use App\Services\VagaService;

/**
 * Funil do profissional: questionário anônimo → análise e vagas compatíveis → criar conta.
 * A assinatura só aparece depois, quando a pessoa clica em "Candidatar-me".
 */
final class FunilController
{
    public function __construct(
        private readonly FunilService $funil,
        private readonly VagaService $vagas,
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
        private readonly LocalizacaoService $localizacao,
    ) {
    }

    public function questionario(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if ($usuario !== null && ($usuario->cadastroCompleto || !$usuario->ehProfissional())) {
            return Resposta::redirecionar($this->autenticacao->destinoInicial($usuario));
        }

        // Vindo do hero da landing com a área já escolhida (?area=frontend): começa na segunda pergunta.
        $valores = $this->funil->respostas() ?? [];
        $area = AreaAtuacao::tryFrom((string) $requisicao->consulta('area', ''));
        if ($area !== null) {
            $valores['area'] = $area->value;
        }

        return $this->paginaQuestionario($requisicao, $valores, [], $area !== null ? 1 : 0);
    }

    public function responder(Requisicao $requisicao): Resposta
    {
        $dados = $requisicao->todos();
        $noBrasil = $this->localizacao->noBrasil($requisicao);

        try {
            $this->funil->salvarRespostas($dados, $noBrasil);
        } catch (ValidacaoException $erro) {
            return $this->paginaQuestionario($requisicao, $dados, $erro->erros());
        }

        // Quem já está logado (entrou pelo Google sem perfil, por exemplo) ganha o perfil na hora.
        $usuario = $this->autenticacao->usuarioAtual();
        if ($usuario !== null) {
            $this->funil->concluirCadastro($usuario);
        }

        return Resposta::redirecionar('/vagas-compativeis');
    }

    /** Radar do questionário (HTMX): recalcula a cada resposta quantas vagas batem com o que já foi respondido. */
    public function radar(Requisicao $requisicao): Resposta
    {
        return Resposta::html(Visao::parcial('funil/radar', [
        ] + $this->dadosDoRadar($requisicao->consultas(), $this->localizacao->noBrasil($requisicao))));
    }

    /** Análise animada e resultado. Funciona para visitantes (respostas na sessão) e para profissionais logados. */
    public function vagasCompativeis(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $perfil = $usuario?->ehProfissional() ? $this->usuarios->perfilDe($usuario) : null;
        $perfil ??= $this->funil->perfilProvisorio();

        if (!$perfil instanceof PerfilProfissional) {
            return Resposta::redirecionar($usuario ? $this->autenticacao->destinoInicial($usuario) : '/comecar');
        }

        $compativeis = $this->vagas->compativeis($perfil);

        // Feed da animação: as vagas compatíveis aparecem uma a uma, sem a empresa.
        $feed = array_map(fn (array $item) => [
            'vaga' => $item['vaga'],
            'compatibilidade' => $item['compatibilidade'],
            'proxima' => $item['proxima'] ?? false,
            'stack' => $this->vagas->stackEmComum($perfil, $item['vaga']),
        ], array_slice($compativeis, 0, 8));

        return Resposta::html(Visao::renderizar('funil/vagas-compativeis', [
            'feed' => $feed,
            'titulo' => t('funil.vagas_titulo_pagina'),
            'usuario' => $usuario,
            'perfil' => $perfil,
            'compativeis' => $compativeis,
            'recentes' => $this->vagas->contarRecentes($compativeis),
            'panorama' => $this->vagas->panorama($perfil, $compativeis),
            'local' => $this->localizacao->localizar($requisicao, $perfil),
            'destino' => $usuario ? '/painel' : '/criar-conta',
        ], 'funil'));
    }

    public function formularioConta(Requisicao $requisicao): Resposta
    {
        if ($this->autenticacao->usuarioAtual() !== null) {
            return Resposta::redirecionar('/painel');
        }
        $perfil = $this->funil->perfilProvisorio();
        if ($perfil === null) {
            return Resposta::redirecionar('/comecar');
        }

        return Resposta::html(Visao::renderizar('funil/criar-conta', [
            'titulo' => t('funil.conta_titulo_pagina'),
            'total' => count($this->vagas->compativeis($perfil)),
            'valores' => [],
            'erros' => [],
            'erro' => Sessao::obterFlash('erro'),
            'vagaEscolhida' => $this->vagaEscolhida(),
        ], 'funil'));
    }

    public function criarConta(Requisicao $requisicao): Resposta
    {
        if ($this->funil->respostas() === null) {
            return Resposta::redirecionarHtmx('/comecar');
        }

        $dados = $requisicao->todos();
        $chaveIp = 'cadastro-ip:' . $requisicao->ip();
        if (LimiteDeTentativas::excedeu($chaveIp, 20)) {
            unset($dados['senha']);
            return Resposta::html(Visao::parcial('funil/formulario-conta', ['valores' => $dados, 'erros' => ['email' => t('auth.muitas_tentativas')]]));
        }
        LimiteDeTentativas::registrar($chaveIp, 3600);

        try {
            $usuario = $this->usuarios->cadastrarProfissional($dados, Idioma::atual(), $this->localizacao->pais($requisicao));
        } catch (ValidacaoException $erro) {
            unset($dados['senha']);
            return Resposta::html(Visao::parcial('funil/formulario-conta', ['valores' => $dados, 'erros' => $erro->erros()]));
        }

        $this->funil->concluirCadastro($usuario);
        Sessao::remover(VagaPublicaController::CHAVE_SESSAO_VAGA);
        $this->autenticacao->iniciarSessao($this->usuarios->buscarPorId($usuario->id));
        Sessao::flash('sucesso', t('funil.conta_criada'));

        return Resposta::redirecionarHtmx('/painel');
    }

    /** A vaga em que a pessoa clicou "Aplicar" (no feed ou num link compartilhado), se ainda estiver aberta. */
    private function vagaEscolhida(): ?Vaga
    {
        $id = (int) Sessao::obter(VagaPublicaController::CHAVE_SESSAO_VAGA, 0);
        if ($id === 0) {
            return null;
        }

        try {
            return $this->vagas->publica($id);
        } catch (NaoEncontradoException) {
            return null;
        }
    }

    /**
     * Número do radar e o que ele está contando: sem resposta nenhuma, todas as vagas abertas na região; com a área,
     * as vagas da área; com mais respostas, as que combinam com o resumo (área · nível · tecnologias).
     * @return array{total: int, noBrasil: bool, resumo: string, filtrado: bool}
     */
    private function dadosDoRadar(array $valores, bool $noBrasil): array
    {
        $partes = [];
        if ($area = AreaAtuacao::tryFrom((string) ($valores['area'] ?? ''))) {
            $partes[] = $area->rotulo();
        }
        $nivel = \App\Enums\Senioridade::tryFrom((string) ($valores['senioridade'] ?? ''));
        if ($nivel) {
            $partes[] = $nivel->rotulo();
        }
        $tecnologias = array_slice(\App\Core\ListaDeTags::normalizar((string) ($valores['stack'] ?? '')), 0, 2);
        $extras = $nivel !== null || $tecnologias !== [] || !empty($valores['modelos']) || !empty($valores['regiao_vagas']);

        return [
            'total' => $this->vagas->contarParaRespostas($valores, $noBrasil),
            'noBrasil' => $noBrasil,
            'resumo' => implode(' · ', [...$partes, ...$tecnologias]),
            'filtrado' => $extras,
            'porArea' => $area !== null,
        ];
    }

    private function paginaQuestionario(Requisicao $requisicao, array $valores, array $erros, int $passoInicial = 0): Resposta
    {
        $local = $this->localizacao->detectar($requisicao);
        $noBrasil = $this->localizacao->noBrasil($requisicao);
        $valores['cidade'] ??= $local['cidade'] ? $local['cidade'] . ($local['estado'] ? ', ' . $local['estado'] : '') : '';

        return Resposta::html(Visao::renderizar('funil/questionario', [
            'titulo' => t('funil.questionario_titulo_pagina'),
            'valores' => $valores,
            'erros' => $erros,
            'noBrasil' => $noBrasil,
            'moeda' => $noBrasil ? 'BRL' : 'USD',
            'radar' => $this->dadosDoRadar($valores, $noBrasil),
            'passoInicial' => $passoInicial,
        ], 'funil'), $erros ? 422 : 200);
    }
}
