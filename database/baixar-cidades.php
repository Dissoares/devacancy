<?php

/**
 * Baixa a lista oficial de municípios do Brasil (API do IBGE) e salva como JSON estático,
 * para o autocomplete de cidade funcionar no navegador sem chamar nenhuma API durante o cadastro.
 * As cidades do Brasil quase nunca mudam: rodar de novo só é preciso se o IBGE criar algum município.
 * Uso: php database/baixar-cidades.php
 */

declare(strict_types=1);

use App\Core\ClienteHttp;

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';

$http = new ClienteHttp();
$municipios = $http->get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios', [], 30);

if (!is_array($municipios) || $municipios === []) {
    exit("Resposta vazia ou inesperada do IBGE.\n");
}

$cidades = [];
foreach ($municipios as $municipio) {
    $uf = $municipio['microrregiao']['mesorregiao']['UF']['sigla'] ?? $municipio['regiao-imediata']['regiao-intermediaria']['UF']['sigla'] ?? null;
    if (!isset($municipio['nome']) || $uf === null) {
        continue;
    }
    $cidades[] = [$municipio['nome'], $uf];
}

usort($cidades, fn (array $a, array $b) => $a[1] <=> $b[1] ?: $a[0] <=> $b[0]);

$destino = RAIZ . '/public/assets/data/cidades-br.json';
if (!is_dir(dirname($destino))) {
    mkdir(dirname($destino), 0755, true);
}
file_put_contents($destino, json_encode($cidades, JSON_UNESCAPED_UNICODE));

echo count($cidades) . " cidades salvas em public/assets/data/cidades-br.json\n";
