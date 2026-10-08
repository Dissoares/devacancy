<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'descricao' => $descricao ?? null, 'estilos' => ['paginas/landing.css']]) ?>
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
