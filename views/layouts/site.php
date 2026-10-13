<!DOCTYPE html>
<html lang="<?= \App\Core\Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'descricao' => $descricao ?? null, 'imagemOg' => $imagemOg ?? null, 'urlCanonica' => $urlCanonica ?? null, 'imagemAlt' => $imagemAlt ?? null, 'dadosEstruturados' => $dadosEstruturados ?? null, 'semIndexar' => $semIndexar ?? false, 'rastrear' => true, 'estilos' => ['paginas/landing.css', 'paginas/assinatura.css', 'paginas/legal.css', 'paginas/vagas-lista.css']]) ?>
  <script src="<?= asset('js/paginas/landing.js') ?>" defer></script>
</head>
<body class="pagina-site<?= !empty($classeCorpo) ? ' ' . e($classeCorpo) : '' ?>">
  <?= parcial('partials/icones') ?>
  <?= parcial('partials/cabecalho-site') ?>
  <main id="conteudo">
    <?= $conteudo ?>
  </main>
  <?= parcial('partials/rodape-site') ?>
</body>
</html>
