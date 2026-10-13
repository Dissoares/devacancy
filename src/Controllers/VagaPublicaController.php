<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\NaoEncontradoException;
use App\Services\AutenticacaoService;
use App\Services\FunilService;
use App\Services\ImagemVagaService;
use App\Services\VagaService;

/** Página pública de uma vaga (o que abre quando alguém clica num link compartilhado) e a imagem de pré-visualização. */
final class VagaPublicaController
{
    /** Vaga que a pessoa escolheu em um link compartilhado ou no feed; usada para lembrar dela até o cadastro. */
    public const CHAVE_SESSAO_VAGA = 'vaga_escolhida';

    public function __construct(
        private readonly VagaService $vagas,
        private readonly ImagemVagaService $imagens,
        private readonly AutenticacaoService $autenticacao,
        private readonly FunilService $funil,
    ) {
    }

    public function mostrar(Requisicao $requisicao): Resposta
    {
        $vaga = $this->vagas->publica($this->idDoParametro($requisicao));
        $usuario = $this->autenticacao->usuarioAtual();

        $descricao = t('compartilhar.og_descricao', [
            'nivel' => $vaga->senioridade->rotulo(),
            'local' => $vaga->local(),
            'stack' => implode(', ', array_slice($vaga->stack, 0, 4)) ?: $vaga->area->rotulo(),
        ]);

        return Resposta::html(Visao::renderizar('vaga/publica', [
            'titulo' => t('compartilhar.og_titulo', ['titulo' => $vaga->titulo]),
            'descricao' => $descricao,
            'imagemOg' => url('/vaga/' . $vaga->id . '/imagem.png'),
            'urlCanonica' => url_vaga($vaga),
            'dadosEstruturados' => $this->dadosEstruturados($vaga),
            'vaga' => $vaga,
            'usuario' => $usuario,
        ], 'site'));
    }

    /** O link de "aplicar" de uma vaga compartilhada: lembra da vaga e começa o questionário. */
    public function aplicar(Requisicao $requisicao): Resposta
    {
        $vaga = $this->vagas->publica($this->idDoParametro($requisicao));
        Sessao::definir(self::CHAVE_SESSAO_VAGA, $vaga->id);

        $usuario = $this->autenticacao->usuarioAtual();
        // Quem já respondeu o questionário vai direto criar a conta; quem chegou por um link compartilhado começa por ele.
        return Resposta::redirecionar($usuario
            ? $this->autenticacao->destinoInicial($usuario)
            : ($this->funil->respostas() !== null ? '/criar-conta' : '/comecar'));
    }

    public function imagem(Requisicao $requisicao): Resposta
    {
        $caminho = null;
        try {
            $caminho = $this->imagens->caminhoDaVaga($this->vagas->publica($this->idDoParametro($requisicao)));
        } catch (NaoEncontradoException) {
            // vaga inexistente ou pausada: cai na imagem padrão
        }

        $caminho ??= RAIZ . '/public/assets/img/og-padrao.png';
        return Resposta::download($caminho, 'vaga.png', 'image/png')
            ->comCabecalho('Cache-Control', 'public, max-age=86400');
    }

    /** Marcação schema.org/JobPosting: é o que faz a vaga aparecer no Google Vagas. A empresa continua oculta. */
    private function dadosEstruturados(\App\Entities\Vaga $vaga): array
    {
        $dados = [
            '@type' => 'JobPosting',
            'title' => $vaga->titulo,
            'description' => mb_substr($vaga->descricaoPublica(), 0, 5000),
            'datePosted' => date('c', strtotime($vaga->criadoEm)),
            'validThrough' => date('c', strtotime($vaga->criadoEm . ' +60 days')),
            'employmentType' => 'FULL_TIME',
            'directApply' => false,
            'hiringOrganization' => ['@type' => 'Organization', 'name' => t('vaga_publica.empresa_oculta')],
            'url' => url_vaga($vaga),
        ];

        if ($vaga->modeloTrabalho === \App\Enums\ModeloTrabalho::Remoto) {
            $dados['jobLocationType'] = 'TELECOMMUTE';
            $dados['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => $vaga->regiao === \App\Enums\RegiaoVagas::Brasil ? 'BR' : 'US'];
        }
        if ($vaga->cidade) {
            $dados['jobLocation'] = ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $vaga->cidade, 'addressCountry' => $vaga->regiao === \App\Enums\RegiaoVagas::Brasil ? 'BR' : 'US']];
        }
        // Trilha de navegação: o Google a mostra no resultado de busca, no lugar do endereço.
        $trilha = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => t('vagas.migalha_inicio'), 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => t('vagas.migalha_vagas'), 'item' => url('/vagas')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $vaga->area->rotulo(), 'item' => url('/vagas/' . $vaga->area->value)],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $vaga->titulo],
        ];

        return ['@context' => 'https://schema.org', '@graph' => [$dados, ['@type' => 'BreadcrumbList', 'itemListElement' => $trilha]]];
    }

    /** "123" ou "123-dev-backend-pleno": só o número importa. */
    private function idDoParametro(Requisicao $requisicao): int
    {
        $id = (int) $requisicao->parametro('id');
        if ($id <= 0) {
            throw new NaoEncontradoException();
        }
        return $id;
    }
}
