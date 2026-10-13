<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\PerfilProfissional;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\CurriculoService;
use App\Services\ImportacaoPerfilService;
use App\Services\UsuarioService;
use App\Services\VagaService;

/** Página de perfil do profissional e importação do currículo (PDF ou PDF do LinkedIn). */
final class PerfilController
{
    public function __construct(
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
        private readonly VagaService $vagas,
        private readonly ImportacaoPerfilService $importacao,
        private readonly CurriculoService $curriculos,
    ) {
    }

    public function ver(Requisicao $requisicao): Resposta
    {
        [$usuario, $perfil] = $this->profissional();

        return Resposta::html(Visao::renderizar('perfil/pagina', [
            'titulo' => t('perfilpg.titulo_pagina'),
            'usuario' => $usuario,
            'perfil' => $perfil,
            'forca' => $perfil->forca(),
            'totalCompativeis' => count($this->vagas->compativeis($perfil)),
            'emAlta' => $this->vagas->tecnologiasEmAlta($perfil),
            'revisao' => $this->importacao->revisao($perfil, $usuario),
            'sucesso' => Sessao::obterFlash('sucesso'),
        ], 'app'));
    }

    /** Recebe o texto lido do PDF (e o próprio PDF) e devolve a tela de revisão, para a página colocar no lugar. */
    public function importar(Requisicao $requisicao): Resposta
    {
        [$usuario, $perfil] = $this->profissional();

        try {
            $this->importacao->analisar(
                $usuario,
                (string) $requisicao->entrada('texto', ''),
                (string) $requisicao->entrada('origem', 'pdf'),
                $requisicao->arquivo('curriculo'),
            );
        } catch (ValidacaoException $erro) {
            return Resposta::html(Visao::parcial('perfil/partials/importar-erro', ['mensagem' => $erro->erros()['texto'] ?? t('importar.nao_reconheceu')]), 422);
        }

        return Resposta::html(Visao::parcial('perfil/partials/revisao', ['revisao' => $this->importacao->revisao($perfil, $usuario)]));
    }

    public function aplicar(Requisicao $requisicao): Resposta
    {
        [$usuario, $perfil] = $this->profissional();

        try {
            $campos = $this->importacao->aplicar($usuario, $perfil, array_map('strval', (array) $requisicao->entrada('campos', [])));
        } catch (NaoEncontradoException) {
            return Resposta::redirecionar('/perfil');
        } catch (ValidacaoException $erro) {
            Sessao::flash('erro_importacao', implode(' ', $erro->erros()));
            return Resposta::redirecionar('/perfil');
        }

        Sessao::flash('sucesso', $campos > 0 ? t('importar.aplicado', ['n' => $campos]) : t('importar.nada_aplicado'));
        return Resposta::redirecionar('/perfil');
    }

    public function descartar(): Resposta
    {
        $this->importacao->descartar();
        return Resposta::redirecionar('/perfil');
    }

    /** O próprio PDF do profissional, para ele conferir o que está enviado. */
    public function curriculo(): Resposta
    {
        [, $perfil] = $this->profissional();
        if (!$perfil->possuiCurriculo()) {
            throw new NaoEncontradoException();
        }

        return Resposta::download($this->curriculos->caminho($perfil->curriculoArquivo), $perfil->curriculoNomeOriginal ?? 'curriculo.pdf', 'application/pdf');
    }

    /** @return array{0: \App\Entities\Usuario, 1: PerfilProfissional} */
    private function profissional(): array
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $perfil = $usuario !== null && $usuario->ehProfissional() ? $this->usuarios->perfilDe($usuario) : null;
        if (!$perfil instanceof PerfilProfissional) {
            throw new NaoEncontradoException();
        }
        return [$usuario, $perfil];
    }
}
