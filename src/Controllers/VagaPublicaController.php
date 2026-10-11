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
