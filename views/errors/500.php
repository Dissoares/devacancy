<section class="erro-pagina">
  <div>
    <div class="erro-codigo texto-gradiente">500</div>
    <h1>Algo deu errado do nosso lado</h1>
    <p>Já registramos o problema. Tente novamente em alguns instantes.</p>
    <?php if (!empty($detalhe)): ?>
      <div class="erro-detalhe"><?= e($detalhe) ?></div>
    <?php endif; ?>
    <a class="botao botao-primario" href="/">Voltar para o início <?= icone('seta', 'icone icone-seta') ?></a>
  </div>
</section>
