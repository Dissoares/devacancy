<?php

/**
 * Importa vagas reais: repositórios de vagas da comunidade no GitHub (sempre) e Adzuna (se as
 * chaves ADZUNA_APP_ID/ADZUNA_APP_KEY estiverem no .env). Pode rodar quantas vezes quiser:
 * vagas já importadas são puladas.
 * Uso: php database/importar-vagas.php   (agende 1x por dia no Agendador de Tarefas / cron)
 */

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\Container;
use App\Services\ImportadorVagasAdzuna;
use App\Services\ImportadorVagasGithub;

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';

Ambiente::carregar(RAIZ . '/.env');
date_default_timezone_set('America/Sao_Paulo');

$container = new Container();
foreach (configuracao('vinculos') as $abstrato => $concreto) {
    $container->vincular($abstrato, $concreto);
}

$relatar = function (string $fonte, array $resumo): void {
    echo "[{$fonte}] importadas: {$resumo['importadas']} | já existentes: {$resumo['repetidas']} | ignoradas (antigas): {$resumo['ignoradas']}\n";
    foreach ($resumo['erros'] as $erro) {
        echo "[{$fonte}] erro: {$erro}\n";
    }
};

$relatar('GitHub', $container->obter(ImportadorVagasGithub::class)->importar());

$adzuna = $container->obter(ImportadorVagasAdzuna::class);
if ($adzuna->configurado()) {
    $relatar('Adzuna', $adzuna->importar());
} else {
    echo "[Adzuna] pulado: ADZUNA_APP_ID/ADZUNA_APP_KEY não configurados no .env.\n";
}
