<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;

/**
 * Asaas: checkout hospedado (PIX e cartão) para clientes no Brasil, em reais.
 * Docs: https://docs.asaas.com/reference/criar-novo-checkout e /docs/eventos-para-checkout
 */
final class AsaasService
{
    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function configurado(): bool
    {
        return (string) configuracao('asaas.chave') !== '';
    }

    /**
     * Cria a página de pagamento da Asaas (cobrança avulsa, PIX ou cartão).
     * @return array{id: string, link: string}
     */
    public function criarCheckout(string $nome, string $descricao, float $valor, string $referencia, array $cliente, string $urlSucesso, string $urlCancelamento): array
    {
        $resposta = $this->http->postJson($this->url('/checkouts'), [
            'billingTypes' => ['PIX', 'CREDIT_CARD'],
            'chargeTypes' => ['DETACHED'],
            'minutesToExpire' => 60,
            'externalReference' => $referencia,
            'callback' => ['successUrl' => $urlSucesso, 'cancelUrl' => $urlCancelamento, 'expiredUrl' => $urlCancelamento],
            'items' => [[
                'name' => mb_substr($nome, 0, 30),
                'description' => mb_substr($descricao, 0, 150),
                'quantity' => 1,
                'value' => round($valor, 2),
                'imageBase64' => $this->imagemDoItem(),
            ]],
            'customerData' => array_filter($cliente),
        ], $this->cabecalhos());

        return ['id' => (string) $resposta['id'], 'link' => (string) $resposta['link']];
    }

    /** A Asaas manda o token configurado no webhook no cabeçalho asaas-access-token. Sem token salvo, nada é aceito. */
    public function avisoAutentico(string $tokenRecebido): bool
    {
        $esperado = (string) configuracao('asaas.token_webhook');
        return $esperado !== '' && hash_equals($esperado, $tokenRecebido);
    }

    private function cabecalhos(): array
    {
        return ['access_token: ' . configuracao('asaas.chave')];
    }

    private function url(string $caminho): string
    {
        $base = configuracao('asaas.modo') === 'sandbox' ? 'https://api-sandbox.asaas.com/v3' : 'https://api.asaas.com/v3';
        return (string) (configuracao('asaas.url') ?: $base) . $caminho;
    }

    /** A Asaas exige uma imagem no item: usa a marca do site, reduzida e guardada para não refazer a cada pagamento. */
    private function imagemDoItem(): string
    {
        $original = RAIZ . '/public/assets/img/og-padrao.png';
        $reduzida = RAIZ . '/storage/cache/asaas-item.png';

        if (!is_file($reduzida) && extension_loaded('gd') && is_file($original)) {
            $fonte = imagecreatefrompng($original);
            $destino = imagecreatetruecolor(400, 210);
            imagecopyresampled($destino, $fonte, 0, 0, 0, 0, 400, 210, imagesx($fonte), imagesy($fonte));
            if (!is_dir(dirname($reduzida))) {
                mkdir(dirname($reduzida), 0775, true);
            }
            imagepng($destino, $reduzida, 8);
        }

        $arquivo = is_file($reduzida) ? $reduzida : $original;
        return base64_encode((string) file_get_contents($arquivo));
    }
}
