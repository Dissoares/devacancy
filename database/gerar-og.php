<?php

/**
 * Gera a imagem padrão de compartilhamento (public/assets/img/og-padrao.png), usada na landing
 * e como reserva quando o servidor não consegue desenhar a imagem de uma vaga.
 * Uso: php database/gerar-og.php
 */

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\Idioma;
use App\Services\ImagemVagaService;

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';

Ambiente::carregar(RAIZ . '/.env');
Idioma::definir('pt');

$imagens = new ImagemVagaService();
if (!$imagens->disponivel()) {
    exit("GD com FreeType e a fonte em storage/fontes são necessários.\n");
}

$imagens->gerarPadrao(RAIZ . '/public/assets/img/og-padrao.png');
echo "Imagem gerada em public/assets/img/og-padrao.png\n";
