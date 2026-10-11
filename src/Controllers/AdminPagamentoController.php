<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;

/** Liga/desliga as formas de pagamento disponíveis no checkout. */
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
            'titulo' => 'Pagamentos — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'metodos' => $this->assinaturas->catalogoDeMetodos(),
            'pendentes' => $this->assinaturas->paypalEmailPendentes(),
            'sucesso' => Sessao::obterFlash('sucesso'),
        ], 'app'));
    }

    /** O admin conferiu no app do PayPal que o dinheiro chegou e libera o plano. */
    public function confirmar(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->confirmarPaypalEmailManualmente((int) $requisicao->parametro('id'));
        Sessao::flash('sucesso', 'Pagamento confirmado: o plano foi liberado.');
        return Resposta::redirecionar('/admin/pagamentos');
    }

    public function alternar(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->alternarMetodo((string) $requisicao->parametro('chave'));
        return Resposta::redirecionar('/admin/pagamentos');
    }
}
