<?php
/** Cartão pelo Stripe: leva para a página de pagamento do Stripe. Os rádios de plano entram por hx-include. @var array $erros @var bool $simulacao */
$destino = !empty($simulacao) ? '/assinar/stripe/simular' : '/assinar/stripe';
?>
<form id="formulario-stripe" class="formulario-plano" method="post" action="<?= e($destino) ?>"
      hx-post="<?= e($destino) ?>" hx-target="this" hx-swap="outerHTML" hx-include="[name='plano']" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($simulacao)): ?>
    <div class="alerta alerta-aviso"><?= icone('ajustes') ?><span><?= e(t('planos.stripe_simulacao')) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['geral']) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erros['plano'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['plano']) ?></span></div>
  <?php endif; ?>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t(!empty($simulacao) ? 'planos.stripe_simular' : 'planos.stripe_continuar')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-seguranca"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('planos.stripe_nota')) ?></p>
</form>
