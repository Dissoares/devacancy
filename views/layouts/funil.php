<?php
/** Layout do funil (questionário, vagas compatíveis, criar conta): sem distrações, só marca, idioma e "Entrar". */
use App\Core\Idioma;

$logado = usuario_sessao();
?>
<!DOCTYPE html>
<html lang="<?= Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/painel.css', 'paginas/assinatura.css', 'paginas/funil.css']]) ?>
  <script src="<?= asset('js/componentes/formularios.js') ?>" defer></script>
  <script src="<?= asset('js/paginas/funil.js') ?>" defer></script>
  <script src="<?= asset('js/paginas/assinatura.js') ?>" defer></script>
</head>
<body class="pagina-funil">
  <?= parcial('partials/icones') ?>

  <header class="barra-funil">
    <div class="barra-funil-interno">
      <?= parcial('partials/marca') ?>
      <div class="barra-funil-acoes">
        <?= parcial('partials/seletor-idioma') ?>
        <?php if ($logado): ?>
          <a class="botao botao-fantasma botao-pequeno" href="/painel"><?= e(t('nav.meu_painel')) ?></a>
        <?php else: ?>
          <a class="botao botao-fantasma botao-pequeno" href="/entrar"><?= e(t('auth.entrar')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="conteudo-funil">
    <?= $conteudo ?>
  </main>
</body>
</html>
