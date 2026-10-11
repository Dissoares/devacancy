<?php
/** Formulário do PIX. Os rádios de plano ficam fora dele e entram pelo atributo form="formulario-pix". @var array $valores @var array $erros */
?>
<form id="formulario-pix" class="formulario-plano" method="post" action="/assinar"
      hx-post="/assinar" hx-target="this" hx-swap="outerHTML" hx-include="[name='plano']" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['geral']) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erros['plano'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['plano']) ?></span></div>
  <?php endif; ?>

  <div class="<?= classe_campo($erros, 'cpf') ?>">
    <label class="campo-rotulo" for="cpf"><?= e(t('pagamento.cpf')) ?></label>
    <input class="entrada" id="cpf" name="cpf" inputmode="numeric" autocomplete="off" maxlength="14"
           placeholder="000.000.000-00" value="<?= e($valores['cpf'] ?? '') ?>" data-mascara-cpf data-validar-cpf>
    <div id="dica-cpf" aria-live="polite"></div>
    <?= erro_campo($erros, 'cpf') ?>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('pagamento.gerar_pix')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-seguranca"><?= icone('escudo', 'icone icone-sm') ?> <?= e(t('pagamento.pix_nota')) ?></p>
</form>
