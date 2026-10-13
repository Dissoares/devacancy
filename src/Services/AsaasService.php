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
        $corpo = [
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
        ];

        // Se o bloco do cliente existe, a Asaas exige TODOS os campos (CPF, telefone, endereço...), mesmo que a documentação
        // diga que são opcionais. Sem ele, a própria página de pagamento da Asaas pede esses dados ao comprador.
        $completo = ['cpfCnpj', 'phoneNumber', 'address', 'addressNumber', 'province', 'postalCode'];
        if (count(array_filter($completo, fn (string $campo) => !empty($cliente[$campo]))) === count($completo)) {
            $corpo['customerData'] = array_filter($cliente);
        }

        $resposta = $this->http->postJson($this->url('/checkouts'), $corpo, $this->cabecalhos());

        return ['id' => (string) $resposta['id'], 'link' => (string) $resposta['link']];
    }

    /**
     * Diagnóstico para o admin: confere se a chave é aceita no ambiente escolhido e tenta abrir um checkout de verdade
     * (some em 60 minutos e não cobra nada). Devolve, passo a passo, o que a Asaas respondeu.
     * @return array{ok: bool, passos: array<int, array{rotulo: string, ok: bool, detalhe: string}>}
     */
    public function testar(): array
    {
        $passos = [];
        $ambiente = configuracao('asaas.modo') === 'sandbox' ? 'Sandbox (testes)' : 'Produção';

        try {
            $this->http->get($this->url('/customers?limit=1'), $this->cabecalhos(), 20);
            $passos[] = ['rotulo' => "Chave aceita em {$ambiente}", 'ok' => true, 'detalhe' => 'A Asaas reconheceu a chave de API.'];
        } catch (\RuntimeException $erro) {
            $passos[] = ['rotulo' => "Chave em {$ambiente}", 'ok' => false, 'detalhe' => $this->resumoDoErro($erro)];
            return ['ok' => false, 'passos' => $passos];
        }

        try {
            $checkout = $this->criarCheckout('Teste Devacancy', 'Teste de conexão do painel (pode ignorar)', 5.00, 'teste-' . time(), ['name' => 'Teste Devacancy', 'email' => 'teste@devacancy.online'], url('/planos'), url('/planos'));
            $passos[] = ['rotulo' => 'Criação do checkout', 'ok' => true, 'detalhe' => 'A Asaas abriu a página de pagamento: ' . $checkout['link']];
        } catch (\RuntimeException $erro) {
            $passos[] = ['rotulo' => 'Criação do checkout', 'ok' => false, 'detalhe' => $this->resumoDoErro($erro)];
            return ['ok' => false, 'passos' => $passos];
        }

        return ['ok' => true, 'passos' => $passos];
    }

    /** Traduz as respostas de erro mais comuns da Asaas e mantém o texto original logo depois. */
    private function resumoDoErro(\RuntimeException $erro): string
    {
        $mensagem = $erro->getMessage();
        $dica = match (true) {
            str_contains($mensagem, '(401)') => 'Chave inválida para este ambiente. Confira se a chave é de Produção ($aact_prod…) e se "Conta" está em Produção, ou o contrário no Sandbox.',
            str_contains($mensagem, '(403)') => 'A conta não tem permissão para isso. O checkout hospedado precisa estar liberado na sua conta Asaas (fale com o suporte deles) e a conta precisa estar aprovada.',
            str_contains($mensagem, '(400)') => 'A Asaas recusou os dados enviados. O motivo exato está no texto abaixo.',
            str_contains($mensagem, '(404)') => 'Endereço da API não encontrado. Confira o ambiente (Produção ou Sandbox).',
            default => null,
        };
        return ($dica ? $dica . "\n" : '') . 'Resposta da Asaas: ' . $mensagem;
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
