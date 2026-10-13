<?php $logado = usuario_sessao(); ?>
<header class="cabecalho" data-cabecalho>
  <div class="container cabecalho-interno">
    <?= parcial('partials/marca') ?>

    <nav class="navegacao" id="navegacao" aria-label="<?= e(t('nav.principal')) ?>">
      <a href="/#como-funciona"><?= e(t('nav.como_funciona')) ?></a>
      <a href="/#precos"><?= e(t('nav.precos')) ?></a>
      <a href="/#empresas"><?= e(t('nav.empresas')) ?></a>
      <a href="/#perguntas"><?= e(t('nav.duvidas')) ?></a>
      <div class="navegacao-acoes-movel">
        <?= parcial('partials/seletor-idioma') ?>
        <?php if ($logado): ?>
          <a class="botao botao-primario botao-bloco" href="<?= $logado['tipo'] === 'admin' ? '/admin' : '/painel' ?>"><?= e(t('nav.meu_painel')) ?></a>
        <?php else: ?>
          <a class="botao botao-secundario botao-bloco" href="/entrar"><?= icone("usuario", "icone icone-sm") ?> <?= e(t("auth.entrar")) ?></a>
          <a class="botao botao-primario botao-bloco" href="/comecar"><?= e(t('landing.cta_curto')) ?></a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="cabecalho-acoes">
      <?= parcial('partials/seletor-idioma') ?>
      <?php if ($logado): ?>
        <a class="botao botao-primario botao-pequeno" href="<?= $logado['tipo'] === 'admin' ? '/admin' : '/painel' ?>"><?= e(t('nav.meu_painel')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
      <?php else: ?>
        <a class="botao botao-fantasma botao-pequeno" href="/entrar"><?= icone("usuario", "icone icone-sm") ?> <?= e(t("auth.entrar")) ?></a>
        <a class="botao botao-primario botao-pequeno" href="/comecar"><?= e(t('landing.cta_curto')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
      <?php endif; ?>
    </div>

    <button class="botao-menu" type="button" aria-label="<?= e(t('nav.abrir_menu')) ?>" aria-expanded="false" aria-controls="navegacao" data-botao-menu
            data-rotulo-abrir="<?= e(t('nav.abrir_menu')) ?>" data-rotulo-fechar="<?= e(t('nav.fechar_menu')) ?>">
      <?= icone('menu', 'icone icone-abrir') ?><?= icone('x', 'icone icone-fechar') ?>
    </button>
  </div>
</header>
