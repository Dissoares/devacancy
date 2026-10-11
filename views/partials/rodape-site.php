<footer class="rodape">
  <div class="container rodape-interno">
    <div class="rodape-marca">
      <?= parcial('partials/marca') ?>
      <p><?= e(t('marca.slogan_1')) ?> <?= e(t('marca.slogan_2')) ?><br><?= e(t('rodape.descricao')) ?></p>
    </div>
    <div class="rodape-colunas">
      <div>
        <h4><?= e(t('rodape.plataforma')) ?></h4>
        <a href="/#como-funciona"><?= e(t('nav.como_funciona')) ?></a>
        <a href="/#precos"><?= e(t('nav.precos')) ?></a>
        <a href="/#perguntas"><?= e(t('nav.duvidas')) ?></a>
      </div>
      <div>
        <h4><?= e(t('rodape.conta')) ?></h4>
        <a href="/comecar"><?= e(t('rodape.sou_profissional')) ?></a>
        <a href="/cadastro?tipo=empresa"><?= e(t('landing.sou_empresa')) ?></a>
        <a href="/entrar"><?= e(t('auth.entrar')) ?></a>
      </div>
    </div>
  </div>
  <div class="container rodape-base">
    <span>© <?= date('Y') ?> Devacancy. <?= e(t('rodape.direitos')) ?></span>
    <?= parcial('partials/seletor-idioma') ?>
  </div>
</footer>
