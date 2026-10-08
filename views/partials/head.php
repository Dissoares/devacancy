<?php
/** @var string $titulo @var ?string $descricao @var string[] $estilos */
$descricao ??= 'Cadastre seu perfil uma vez e receba candidaturas diárias às vagas que combinam com a sua stack e o seu nível.';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($titulo ?? 'Devacancy') ?></title>
<meta name="description" content="<?= e($descricao) ?>">
<meta name="theme-color" content="#07080c">
<script>document.documentElement.classList.add('js')</script>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Devacancy">
<meta property="og:title" content="<?= e($titulo ?? 'Devacancy') ?>">
<meta property="og:description" content="<?= e($descricao) ?>">
<meta property="og:locale" content="pt_BR">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/componentes.css') ?>">
<?php foreach ($estilos ?? [] as $estilo): ?>
<link rel="stylesheet" href="<?= asset('css/' . $estilo) ?>">
<?php endforeach; ?>
<script src="<?= asset('js/vendor/htmx.min.js') ?>" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
