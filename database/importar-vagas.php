<?php

/**
 * Importa vagas reais dos repositórios de vagas da comunidade no GitHub (lista em config/app.php).
 * Pode rodar quantas vezes quiser: vagas já importadas são puladas.
 * Uso: php database/importar-vagas.php   (agende 1x por dia no Agendador de Tarefas / cron)
 */

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\Container;
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

$resumo = $container->obter(ImportadorVagasGithub::class)->importar();

echo "Importadas: {$resumo['importadas']} | já existentes: {$resumo['repetidas']} | ignoradas (antigas): {$resumo['ignoradas']}\n";
foreach ($resumo['erros'] as $erro) {
    echo "Erro: {$erro}\n";
}
