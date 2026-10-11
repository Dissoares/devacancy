<!DOCTYPE html>
<html lang="<?= \App\Core\Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'descricao' => $descricao ?? null, 'estilos' => ['paginas/landing.css', 'paginas/assinatura.css']]) ?>
  <script src="<?= asset('js/paginas/landing.js') ?>" defer></script>
</head>
<body class="pagina-site">
  <?= parcial('partials/icones') ?>
  <?= parcial('partials/cabecalho-site') ?>
  <main id="conteudo">
    <?= $conteudo ?>
  </main>
  <?= parcial('partials/rodape-site') ?>
</body>
</html>
