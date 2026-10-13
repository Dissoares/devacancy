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
            'erro' => Sessao::obterFlash('erro'),
            'pixProblema' => $this->assinaturas->pixManualProblema(),
        ], 'admin'));
    }

    /** Mostra, passo a passo, o que a Asaas respondeu (chave e criação do checkout). */
    public function testarAsaas(): Resposta
    {
        $resultado = $this->assinaturas->testarAsaas();
        $linhas = array_map(fn (array $passo) => ($passo['ok'] ? '✓ ' : '✗ ') . $passo['rotulo'] . "\n" . $passo['detalhe'], $resultado['passos']);
        Sessao::flash($resultado['ok'] ? 'sucesso' : 'erro', ($resultado['ok'] ? "Asaas funcionando.\n" : "Asaas com problema.\n") . implode("\n", $linhas));
        return Resposta::redirecionar('/admin/pagamentos');
    }

    public function excluir(Requisicao $requisicao): Resposta
    {
        try {
            $this->assinaturas->excluirPagamentoNaoConcluido((int) $requisicao->parametro('id'));
            Sessao::flash('sucesso', 'Pagamento excluído.');
        } catch (\App\Exceptions\ValidacaoException $erro) {
            Sessao::flash('sucesso', $erro->erros()['pagamento'] ?? 'Não foi possível excluir.');
        }
        return Resposta::redirecionar('/admin/pagamentos');
    }

    public function limparNaoConcluidos(): Resposta
    {
        $n = $this->assinaturas->limparPagamentosNaoConcluidos();
        Sessao::flash('sucesso', $n === 1 ? '1 pagamento não concluído foi excluído.' : "{$n} pagamentos não concluídos foram excluídos.");
        return Resposta::redirecionar('/admin/pagamentos');
    }

    /** O admin conferiu no app do banco ou do PayPal que o dinheiro chegou e libera o plano. */
    public function confirmar(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->confirmarManualmente((int) $requisicao->parametro('id'));
        Sessao::flash('sucesso', 'Pagamento confirmado: o plano foi liberado.');
        return Resposta::redirecionar('/admin/pagamentos');
    }
}