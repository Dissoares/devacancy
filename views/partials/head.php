<?php
/** @var string $titulo @var ?string $descricao @var string[] $estilos @var ?string $imagemOg @var ?string $urlCanonica */
$descricao ??= t('landing.descricao_pagina');
$imagemOg ??= url('/assets/img/og-padrao.png');
$urlCanonica ??= null;
$dadosEstruturados ??= null;
$imagemAlt ??= $titulo ?? 'Devacancy';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($titulo ?? 'Devacancy') ?></title>
<meta name="description" content="<?= e($descricao) ?>">
<meta name="theme-color" content="#ffffff">
<script>document.documentElement.classList.add('js')</script>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Devacancy">
<meta property="og:title" content="<?= e($titulo ?? 'Devacancy') ?>">
<meta property="og:description" content="<?= e($descricao) ?>">
<meta property="og:image" content="<?= e($imagemOg) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($imagemAlt) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($titulo ?? 'Devacancy') ?>">
<meta name="twitter:description" content="<?= e($descricao) ?>">
<meta name="twitter:image" content="<?= e($imagemOg) ?>">
<meta name="twitter:image:alt" content="<?= e($imagemAlt) ?>">
<?php if ($urlCanonica): ?>
<meta property="og:url" content="<?= e($urlCanonica) ?>">
<link rel="canonical" href="<?= e($urlCanonica) ?>">
<?php endif; ?>
<meta property="og:locale" content="<?= \App\Core\Idioma::atual() === 'pt' ? 'pt_BR' : 'en_US' ?>">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<?php if ($dadosEstruturados): ?>
<script type="application/ld+json"><?= json_encode($dadosEstruturados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/componentes.css') ?>">
<?php foreach ($estilos ?? [] as $estilo): ?>
<link rel="stylesheet" href="<?= asset('css/' . $estilo) ?>">
<?php endforeach; ?>
<script>window.TEXTOS = <?= json_encode(textos_js(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>; window.ICONES_STACK = <?= json_encode(\App\Core\IconesStack::mapaParaScripts(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<script src="<?= asset('js/vendor/htmx.min.js') ?>" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/componentes/compartilhar.js') ?>" defer></script>
