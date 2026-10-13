<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;

/** Financeiro: pagamentos esperando confirmação manual e o histórico recente. */
final class AdminPagamentoController
{
    public function __construct(
        private readonly AssinaturaService $assinaturas,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function listar(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/pagamentos', [
            'titulo' => 'Pagamentos — Admin',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'pendentes' => $this->assinaturas->pagamentosManuaisPendentes(),
            'ultimos' => $this->assinaturas->ultimosPagamentos(30),
            'sucesso' => Sessao::obterFlash('sucesso'),
            'pixProblema' => $this->assinaturas->pixManualProblema(),
        ], 'admin'));
    }

    /** O admin conferiu no app do banco ou do PayPal que o dinheiro chegou e libera o plano. */
    public function confirmar(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->confirmarManualmente((int) $requisicao->parametro('id'));
        Sessao::flash('sucesso', 'Pagamento confirmado: o plano foi liberado.');
        return Resposta::redirecionar('/admin/pagamentos');
    }
}