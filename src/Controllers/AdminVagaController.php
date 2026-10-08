<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\VagaService;

/** Cadastro interno das vagas que alimentam o match. */
final class AdminVagaController
{
    public function __construct(
        private readonly VagaService $vagas,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function listar(Requisicao $requisicao): Resposta
    {
        return $this->pagina([], [], Sessao::obterFlash('sucesso'));
    }

    public function cadastrar(Requisicao $requisicao): Resposta
    {
        $dados = $requisicao->todos();

        try {
            $this->vagas->cadastrar($dados);
        } catch (ValidacaoException $erro) {
            return $this->pagina($dados, $erro->erros(), null);
        }

        Sessao::flash('sucesso', 'Vaga cadastrada.');
        return Resposta::redirecionar('/admin/vagas');
    }

    public function alternar(Requisicao $requisicao): Resposta
    {
        $this->vagas->alternarAtiva((int) $requisicao->parametro('id'));
        return Resposta::redirecionar('/admin/vagas');
    }

    private function pagina(array $valores, array $erros, ?string $sucesso): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/vagas', [
            'titulo' => 'Vagas — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'vagas' => $this->vagas->listar(),
            'valores' => $valores,
            'erros' => $erros,
            'sucesso' => $sucesso,
        ], 'app'), $erros ? 422 : 200);
    }
}
