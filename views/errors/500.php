<section class="erro-pagina">
  <div>
    <div class="erro-codigo texto-gradiente">500</div>
    <h1><?= e(t('erro.500_h1')) ?></h1>
    <p><?= e(t('erro.500_texto')) ?></p>
    <?php if (!empty($detalhe)): ?>
      <div class="erro-detalhe"><?= e($detalhe) ?></div>
    <?php endif; ?>
    <a class="botao botao-primario" href="/"><?= e(t('erro.voltar')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
  </div>
</section>
