<?php /** @var string $tituloDocumento @var string $conteudo_legal @var string $atualizadoEm */ ?>
<section class="legal">
  <div class="container legal-interno">
    <header class="legal-cabecalho">
      <nav class="legal-abas" aria-label="<?= e(t('legal.documentos')) ?>">
        <a class="<?= ativo('/privacidade') ?>" href="/privacidade"><?= e(t('legal.privacidade_titulo')) ?></a>
        <a class="<?= ativo('/termos') ?>" href="/termos"><?= e(t('legal.termos_titulo')) ?></a>
      </nav>
      <h1><?= e($tituloDocumento) ?></h1>
      <p class="texto-suave"><?= e(t('legal.atualizado', ['data' => $atualizadoEm])) ?></p>
    </header>

    <article class="legal-texto">
      <?= parcial($conteudo_legal) ?>
    </article>
  </div>
</section>
