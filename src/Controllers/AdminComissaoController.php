<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\AutenticacaoService;
use App\Services\ComissaoService;

/** Comissões de indicação: 40% do primeiro pagamento do amigo indicado, paga uma única vez. */
final class AdminComissaoController
{
    public function __construct(
        private readonly ComissaoService $comissoes,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function listar(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/comissoes', [
            'titulo' => 'Comissões — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'comissoes' => $this->comissoes->listarParaAdmin(),
            'sucesso' => Sessao::obterFlash('sucesso'),
        ], 'app'));
    }

    public function marcarComoPaga(Requisicao $requisicao): Resposta
    {
        if ($this->comissoes->marcarComoPaga((int) $requisicao->parametro('id'))) {
            Sessao::flash('sucesso', 'Comissão marcada como paga.');
        }

        return Resposta::redirecionar('/admin/comissoes');
    }
}
