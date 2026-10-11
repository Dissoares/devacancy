<?php

/**
 * Cria no PayPal o produto e os planos de assinatura (semanal, mensal e trimestral, em BRL e USD)
 * com os preços de config/app.php, e mostra as linhas para colar no .env.
 * Uso: php database/paypal-planos.php
 */

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\ClienteHttp;
use App\Services\PaypalService;

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';

Ambiente::carregar(RAIZ . '/.env');
$paypal = new PaypalService(new ClienteHttp());

if (!$paypal->configurado()) {
    exit("Preencha PAYPAL_CLIENT_ID e PAYPAL_SECRET no .env antes de rodar.\n");
}

$intervalos = ['semana' => ['WEEK', 1], 'mes' => ['MONTH', 1]];
$produto = $paypal->criarProduto('Devacancy');
echo "Produto criado: {$produto['id']} (modo " . configuracao('paypal.modo') . ")\n\n";

$linhas = [];
foreach (configuracao('planos') as $chave => $plano) {
    [$unidade, $quantidade] = $intervalos[$plano['periodo']];
    $quantidade *= $plano['divisor'];

    foreach ($plano['precos'] as $moeda => $valor) {
        $criado = $paypal->criarPlano($produto['id'], "Devacancy {$chave} ({$moeda})", $valor, $moeda, $unidade, $quantidade);
        $linhas[] = 'PAYPAL_PLANO_' . strtoupper($chave) . '_' . $moeda . '=' . $criado['id'];
    }
}

echo "Cole no .env:\n\n" . implode("\n", $linhas) . "\n";
