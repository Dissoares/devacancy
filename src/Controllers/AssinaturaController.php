<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\Pagamento;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\StatusCandidatura;
use App\Enums\StatusPagamento;
use App\Exceptions\ValidacaoException;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;
use App\Services\CandidaturaService;
use App\Services\LocalizacaoService;
use App\Services\UsuarioService;
use App\Services\VagaService;

/**
 * Checkout da assinatura. Brasil: PIX + PayPal em reais. Resto do mundo: PayPal em dólar.
 * O país vem da geolocalização do IP, não do idioma do navegador.
 */
final class AssinaturaController
{
    public function __construct(
        private readonly AssinaturaService $assinaturas,
        private readonly VagaService $vagas,
        private readonly CandidaturaService $candidaturas,
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
        private readonly LocalizacaoService $localizacao,
    ) {
    }

    public function planos(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if (!$usuario->ehProfissional()) {
            return Resposta::redirecionar('/painel');
        }

        return Resposta::html(Visao::renderizar('assinatura/planos', [
            'titulo' => t('planos.titulo_pagina'),
            ...$this->dadosDoCheckout($requisicao, $usuario),
            'valores' => ['plano' => (string) $requisicao->consulta('plano', 'trimestral')],
            'erros' => [],
        ], 'app'));
    }

    /** PIX: gera a cobrança e leva para a página do QR code. */
    public function assinar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $dados = $requisicao->todos();

        try {
            $pagamento = $this->assinaturas->iniciarPagamento($usuario, $dados, $this->localizacao->noBrasil($requisicao));
        } catch (ValidacaoException $erro) {
            return Resposta::html(Visao::parcial('assinatura/partials/formulario-pix', [
                ...$this->dadosDoCheckout($requisicao, $usuario),
                'valores' => $dados,
                'erros' => $erro->erros(),
            ]));
        }

        return Resposta::redirecionarHtmx('/pagamento/' . $pagamento->id);
    }

    /** PayPal só com e-mail: registra o pagamento pendente e leva para a página com o botão "Pagar no PayPal". */
    public function assinarPaypalEmail(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $dados = $requisicao->todos();

        try {
            $pagamento = $this->assinaturas->iniciarPagamentoPaypalEmail($usuario, $dados, $this->localizacao->noBrasil($requisicao));
        } catch (ValidacaoException $erro) {
            return Resposta::html(Visao::parcial('assinatura/partials/formulario-paypal-email', [
                'erros' => $erro->erros(),
            ]));
        }

        return Resposta::redirecionarHtmx('/pagamento/' . $pagamento->id);
    }

    public function pagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $pagamento = $this->assinaturas->sincronizar(
            $this->assinaturas->pagamentoDoUsuario($usuario, (int) $requisicao->parametro('id'))
        );

        return Resposta::html(Visao::renderizar('assinatura/pagamento', [
            'titulo' => t('pagamento.titulo_pagina'),
            'usuario' => $usuario,
            'pagamento' => $pagamento,
            'simulacao' => $this->assinaturas->simulacaoPix(),
            'paypalEmail' => $pagamento->metodo === 'paypal_email' && $pagamento->estaPendente()
                ? $this->assinaturas->formularioPaypalEmail($pagamento)
                : null,
        ], 'app'));
    }

    /** Aviso automático (IPN) do PayPal. Não usa token: a autenticidade é conferida com o próprio PayPal. */
    public function ipnPaypal(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->processarIpn((string) file_get_contents('php://input'));
        return Resposta::texto('ok');
    }

    /** Consultado pelo HTMX a cada poucos segundos enquanto o PIX está pendente. */
    public function statusPagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $pagamento = $this->assinaturas->sincronizar(
            $this->assinaturas->pagamentoDoUsuario($usuario, (int) $requisicao->parametro('id'))
        );

        return $this->respostaDeStatus($pagamento);
    }

    public function simularPagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        return $this->respostaDeStatus($this->assinaturas->simularPagamento($usuario, (int) $requisicao->parametro('id')));
    }

    /** Retorno do botão do PayPal (fetch com JSON): confere a assinatura e diz para onde ir. */
    public function confirmarPaypal(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $assinaturaId = (string) ($requisicao->json()['assinatura_id'] ?? '');

        $ativa = $this->assinaturas->confirmarPaypal($usuario, $assinaturaId);
        if ($ativa) {
            Sessao::flash('sucesso', t('pagamento.confirmado'));
        }

        return Resposta::json(['destino' => $ativa ? url('/painel') : url('/assinatura/paypal/' . rawurlencode($assinaturaId))]);
    }

    /** Espera a primeira cobrança do PayPal (normalmente segundos depois da aprovação). */
    public function aguardandoPaypal(Requisicao $requisicao): Resposta
    {
        return Resposta::html(Visao::renderizar('assinatura/aguardando-paypal', [
            'titulo' => t('pagamento.titulo_pagina'),
            'usuario' => $this->autenticacao->usuarioAtual(),
            'assinaturaId' => (string) $requisicao->parametro('id'),
        ], 'app'));
    }

    public function statusPaypal(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if ($this->assinaturas->confirmarPaypal($usuario, (string) $requisicao->parametro('id'))) {
            Sessao::flash('sucesso', t('pagamento.confirmado'));
            return Resposta::redirecionarHtmx('/painel');
        }

        return Resposta::html(Visao::parcial('assinatura/partials/aguardando-paypal', ['assinaturaId' => (string) $requisicao->parametro('id')]));
    }

    public function simularPaypal(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $moeda = $this->assinaturas->moedaPara($this->localizacao->noBrasil($requisicao));
        $this->assinaturas->simularPaypal($usuario, (string) $requisicao->entrada('plano', ''), $moeda);

        Sessao::flash('sucesso', t('pagamento.confirmado'));
        return Resposta::redirecionarHtmx('/painel');
    }

    public function webhook(Requisicao $requisicao): Resposta
    {
        if (!$this->tokenValido($requisicao, (string) configuracao('expay.token_webhook'))) {
            return Resposta::texto('Unauthorized', 401);
        }

        $this->assinaturas->processarWebhook($requisicao->json());
        return Resposta::texto('ok');
    }

    public function webhookPaypal(Requisicao $requisicao): Resposta
    {
        if (!$this->tokenValido($requisicao, (string) configuracao('paypal.token_webhook'))) {
            return Resposta::texto('Unauthorized', 401);
        }

        $this->assinaturas->processarWebhookPaypal($requisicao->json());
        return Resposta::texto('ok');
    }

    private function tokenValido(Requisicao $requisicao, string $token): bool
    {
        return $token !== '' && hash_equals($token, (string) $requisicao->consulta('token', ''));
    }

    private function respostaDeStatus(Pagamento $pagamento): Resposta
    {
        if ($pagamento->status === StatusPagamento::Pago) {
            Sessao::flash('sucesso', t('pagamento.confirmado'));
            return Resposta::redirecionarHtmx('/painel');
        }

        return Resposta::html(Visao::parcial('assinatura/partials/status-pagamento', [
            'pagamento' => $pagamento,
            'simulacao' => $this->assinaturas->simulacaoPix(),
        ]));
    }

    private function dadosDoCheckout(Requisicao $requisicao, Usuario $usuario): array
    {
        $noBrasil = $this->localizacao->noBrasil($requisicao);
        $moeda = $this->assinaturas->moedaPara($noBrasil);
        $perfil = $this->usuarios->perfilDe($usuario);
        $reservadas = count(array_filter(
            $this->candidaturas->statusPorVaga($usuario),
            fn (StatusCandidatura $status) => $status === StatusCandidatura::NaFila,
        ));

        $planosPaypal = [];
        foreach (array_keys($this->assinaturas->planos()) as $plano) {
            $planosPaypal[$plano] = $this->assinaturas->idPlanoPaypal($plano, $moeda);
        }

        return [
            'usuario' => $usuario,
            'planos' => $this->assinaturas->planos(),
            'moeda' => $moeda,
            'metodos' => $this->assinaturas->metodosPara($noBrasil),
            'totalCompativeis' => $perfil instanceof PerfilProfissional ? count($this->vagas->compativeis($perfil)) : 0,
            'reservadas' => $reservadas,
            'vigenteAte' => $this->assinaturas->vigenteAte($usuario),
            'paypal' => [
                'simulacao' => $this->assinaturas->simulacaoPaypal(),
                'clienteId' => $this->assinaturas->paypalClienteId(),
                'planos' => $planosPaypal,
            ],
        ];
    }
}
