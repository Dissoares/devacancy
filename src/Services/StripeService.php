<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;

/** Cliente da API REST do Stripe: Checkout em modo assinatura e conferência dos avisos (webhooks). */
final class StripeService
{
    /** Versão da API fixada: o formato das respostas não muda sem a gente decidir. */
    private const VERSAO_API = '2024-06-20';
    /** Tolerância do relógio na conferência da assinatura do webhook, em segundos. */
    private const TOLERANCIA = 300;

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function configurado(): bool
    {
        return configuracao('stripe.chave_secreta') !== '';
    }

    /**
     * Cria a página de pagamento hospedada pelo Stripe, cobrando o plano de forma recorrente.
     * @param array{usuario_id: int, plano: string, moeda: string} $metadados identificam o dono da assinatura nos avisos
     * @return array{id: string, url: string}
     */
    public function criarCheckout(
        string $nome,
        int $centavos,
        string $moeda,
        string $unidade,
        int $quantidade,
        string $email,
        array $metadados,
        string $urlSucesso,
        string $urlCancelamento,
        string $idioma,
    ): array {
        $sessao = $this->http->postFormulario($this->url('/v1/checkout/sessions'), [
            'mode' => 'subscription',
            'client_reference_id' => (string) $metadados['usuario_id'],
            'customer_email' => $email,
            'locale' => $idioma,
            'success_url' => $urlSucesso,
            'cancel_url' => $urlCancelamento,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($moeda),
                    'unit_amount' => $centavos,
                    'product_data' => ['name' => $nome],
                    'recurring' => ['interval' => $unidade, 'interval_count' => $quantidade],
                ],
            ]],
            'metadata' => $metadados,
            'subscription_data' => ['metadata' => $metadados],
        ], $this->cabecalhos());

        return ['id' => (string) $sessao['id'], 'url' => (string) $sessao['url']];
    }

    /**
     * Diagnóstico para o admin: confere a chave no Stripe e diz se a conta já pode cobrar de verdade.
     * @return array{ok: bool, passos: array<int, array{rotulo: string, ok: bool, detalhe: string}>}
     */
    public function testar(): array
    {
        $chave = (string) configuracao('stripe.chave_secreta');
        $modo = str_starts_with($chave, 'sk_live_') || str_starts_with($chave, 'rk_live_') ? 'Produção' : 'Testes';
        try {
            $conta = $this->http->get($this->url('/v1/account'), $this->cabecalhos(), 20);
        } catch (\RuntimeException $erro) {
            $dica = str_contains($erro->getMessage(), '(401)') ? 'Chave inválida. Copie de novo a "Chave secreta" em dashboard.stripe.com/apikeys (começa com sk_live_ ou sk_test_).' : null;
            return ['ok' => false, 'passos' => [['rotulo' => 'Chave do Stripe', 'ok' => false, 'detalhe' => ($dica ? $dica . "\n" : '') . 'Resposta do Stripe: ' . $erro->getMessage()]]];
        }

        $passos = [['rotulo' => "Chave aceita (modo {$modo})", 'ok' => true, 'detalhe' => 'Conta: ' . ($conta['business_profile']['name'] ?? $conta['email'] ?? $conta['id'] ?? '—') . ' · país: ' . ($conta['country'] ?? '—')]];
        $cobra = (bool) ($conta['charges_enabled'] ?? false);
        $passos[] = [
            'rotulo' => 'Conta pode cobrar',
            'ok' => $cobra || $modo === 'Testes',
            'detalhe' => $cobra ? 'Cobranças habilitadas.' : 'Cobranças ainda não habilitadas. Complete o cadastro da conta em dashboard.stripe.com (dados do titular/empresa e conta bancária).',
        ];
        $passos[] = [
            'rotulo' => 'Webhook (renovações)',
            'ok' => (string) configuracao('stripe.segredo_webhook') !== '',
            'detalhe' => (string) configuracao('stripe.segredo_webhook') !== '' ? 'Segredo salvo.' : 'Sem o segredo do webhook, o primeiro pagamento funciona, mas as renovações não são registradas. Crie o webhook em dashboard.stripe.com/webhooks.',
        ];

        return ['ok' => !in_array(false, array_column($passos, 'ok'), true), 'passos' => $passos];
    }

    public function sessao(string $id): array
    {
        return $this->http->get($this->url('/v1/checkout/sessions/' . rawurlencode($id)), $this->cabecalhos());
    }

    /** A assinatura com a última fatura já expandida (status, valor e moeda). */
    public function assinatura(string $id): array
    {
        $consulta = http_build_query(['expand' => ['latest_invoice']]);
        return $this->http->get($this->url('/v1/subscriptions/' . rawurlencode($id) . '?' . $consulta), $this->cabecalhos());
    }

    public function fatura(string $id): array
    {
        return $this->http->get($this->url('/v1/invoices/' . rawurlencode($id)), $this->cabecalhos());
    }

    /**
     * Confere a assinatura criptográfica do aviso (cabeçalho Stripe-Signature) com o segredo do webhook.
     * Sem segredo configurado, nenhum aviso é aceito.
     */
    public function avisoAutentico(string $corpo, string $cabecalho, ?int $agora = null): bool
    {
        $segredo = (string) configuracao('stripe.segredo_webhook');
        if ($segredo === '' || $cabecalho === '') {
            return false;
        }

        $momento = 0;
        $assinaturas = [];
        foreach (explode(',', $cabecalho) as $parte) {
            [$chave, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');
            if ($chave === 't') {
                $momento = (int) $valor;
            } elseif ($chave === 'v1') {
                $assinaturas[] = $valor;
            }
        }

        if ($momento === 0 || abs(($agora ?? time()) - $momento) > self::TOLERANCIA) {
            return false;
        }

        $esperada = hash_hmac('sha256', $momento . '.' . $corpo, $segredo);
        foreach ($assinaturas as $assinatura) {
            if (hash_equals($esperada, $assinatura)) {
                return true;
            }
        }
        return false;
    }

    private function cabecalhos(): array
    {
        return [
            'Authorization: Bearer ' . configuracao('stripe.chave_secreta'),
            'Stripe-Version: ' . self::VERSAO_API,
        ];
    }

    private function url(string $caminho): string
    {
        return rtrim((string) configuracao('stripe.url'), '/') . $caminho;
    }
}
