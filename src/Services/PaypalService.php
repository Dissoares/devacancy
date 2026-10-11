<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;

/** Cliente da API REST do PayPal para assinaturas recorrentes (Subscriptions v1). */
final class PaypalService
{
    private ?string $token = null;

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function configurado(): bool
    {
        return configuracao('paypal.cliente_id') !== '' && configuracao('paypal.segredo') !== '';
    }

    public function clienteId(): string
    {
        return (string) configuracao('paypal.cliente_id');
    }

    // ---------------------------------------------------------------- Pagamento direto por e-mail (sem API)

    /** E-mail da conta PayPal que recebe os pagamentos avulsos (PAYPAL_EMAIL). */
    public function emailRecebedor(): string
    {
        return (string) configuracao('paypal.email');
    }

    public function pagamentoPorEmailConfigurado(): bool
    {
        return $this->emailRecebedor() !== '';
    }

    /** Endereço para onde o formulário "Pagar com PayPal" envia o cliente. */
    public function urlPagamentoDireto(): string
    {
        return configuracao('paypal.modo') === 'live'
            ? 'https://www.paypal.com/cgi-bin/webscr'
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }

    /**
     * Confere com o próprio PayPal se o aviso (IPN) é verdadeiro: devolve o corpo recebido e espera "VERIFIED".
     * Em ambiente local, um aviso de teste com ipn_teste=1 passa direto (nunca em produção).
     */
    public function ipnVerdadeiro(string $corpo): bool
    {
        parse_str($corpo, $campos);
        if (ambiente_local() && ($campos['ipn_teste'] ?? '') === '1') {
            return true;
        }

        $url = configuracao('paypal.modo') === 'live'
            ? 'https://ipnpb.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'cmd=_notify-validate&' . $corpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: Devacancy-IPN'],
        ]);
        $resposta = curl_exec($curl);
        curl_close($curl);

        return is_string($resposta) && trim($resposta) === 'VERIFIED';
    }

    /** ID do plano no PayPal para o plano interno e a moeda, ou null se não foi criado. */
    public function idDoPlano(string $plano, string $moeda): ?string
    {
        $id = configuracao("paypal.planos.{$plano}.{$moeda}");
        return $id ?: null;
    }

    /** Caminho inverso: plano e moeda internos a partir do ID do plano no PayPal. @return array{0: string, 1: string}|null */
    public function planoInterno(string $idPaypal): ?array
    {
        foreach ((array) configuracao('paypal.planos', []) as $plano => $moedas) {
            foreach ($moedas as $moeda => $id) {
                if ($id !== '' && $id === $idPaypal) {
                    return [$plano, $moeda];
                }
            }
        }
        return null;
    }

    public function assinatura(string $id): array
    {
        return $this->http->get($this->url('/v1/billing/subscriptions/' . rawurlencode($id)), $this->cabecalhos());
    }

    public function criarProduto(string $nome): array
    {
        return $this->http->postJson($this->url('/v1/catalogs/products'), ['name' => $nome, 'type' => 'SERVICE', 'category' => 'SOFTWARE'], $this->cabecalhos());
    }

    /** Plano recorrente: cobra $valor a cada $quantidade unidades de $unidade (DAY, WEEK, MONTH). */
    public function criarPlano(string $produtoId, string $nome, float $valor, string $moeda, string $unidade, int $quantidade): array
    {
        return $this->http->postJson($this->url('/v1/billing/plans'), [
            'product_id' => $produtoId,
            'name' => $nome,
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => $unidade, 'interval_count' => $quantidade],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => ['fixed_price' => ['value' => number_format($valor, 2, '.', ''), 'currency_code' => $moeda]],
            ]],
            'payment_preferences' => ['auto_bill_outstanding' => true, 'payment_failure_threshold' => 1],
        ], $this->cabecalhos());
    }

    private function cabecalhos(): array
    {
        return ['Authorization: Bearer ' . $this->obterToken()];
    }

    private function obterToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $resposta = $this->http->postFormulario($this->url('/v1/oauth2/token'), ['grant_type' => 'client_credentials'], [
            'Authorization: Basic ' . base64_encode(configuracao('paypal.cliente_id') . ':' . configuracao('paypal.segredo')),
        ]);

        return $this->token = (string) $resposta['access_token'];
    }

    private function url(string $caminho): string
    {
        $base = configuracao('paypal.modo') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        return $base . $caminho;
    }
}
