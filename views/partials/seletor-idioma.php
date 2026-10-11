<?php use App\Core\Idioma; ?>
<nav class="seletor-idioma" aria-label="<?= e(t('nav.idioma')) ?>">
  <?php foreach (['pt' => ['PT', 'br', 'Português (Brasil)'], 'en' => ['EN', 'us', 'English']] as $codigo => [$rotulo, $bandeira, $nome]): ?>
    <a href="<?= e(url_idioma($codigo)) ?>" hreflang="<?= $codigo ?>" title="<?= e($nome) ?>" class="<?= Idioma::atual() === $codigo ? 'ativo' : '' ?>"
       <?= Idioma::atual() === $codigo ? 'aria-current="true"' : '' ?>>
      <img class="bandeira" src="<?= e(asset('img/bandeiras/' . $bandeira . '.svg')) ?>" alt="" width="18" height="13"><?= $rotulo ?>
    </a>
  <?php endforeach; ?>
</nav>
