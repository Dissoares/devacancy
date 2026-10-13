<?php
/** Asaas: leva para o checkout da Asaas (PIX ou cartão). Os rádios de plano entram por hx-include. @var array $erros */
?>
<form id="formulario-asaas" class="formulario-plano" method="post" action="/assinar/asaas"
      hx-post="/assinar/asaas" hx-target="this" hx-swap="outerHTML" hx-include="[name='plano']" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['geral']) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erros['plano'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['plano']) ?></span></div>
  <?php endif; ?>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('pagamento.asaas_continuar')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-seguranca"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('pagamento.asaas_nota')) ?></p>
</form>
