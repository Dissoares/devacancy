<?php
/** PIX copia e cola do dono: gera o código com o valor do plano. Os rádios de plano entram por hx-include. @var array $erros */
?>
<form id="formulario-pix-manual" class="formulario-plano" method="post" action="/assinar/pix-manual"
      hx-post="/assinar/pix-manual" hx-target="this" hx-swap="outerHTML" hx-include="[name='plano']" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['geral']) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erros['plano'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['plano']) ?></span></div>
  <?php endif; ?>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('pagamento.pix_manual_continuar')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-seguranca"><?= icone('escudo', 'icone icone-sm') ?> <?= e(t('pagamento.pix_manual_nota')) ?></p>
</form>
