<div class="auth-cartao">
  <div class="auth-cabecalho">
    <h1><?= e(t('auth.entrar_titulo')) ?></h1>
    <p><?= e(t('auth.entrar_texto')) ?></p>
  </div>

  <?php if (!empty($sucesso)): ?>
    <div class="alerta alerta-sucesso" role="status"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($aviso)): ?>
    <div class="alerta alerta-aviso"><?= icone('cadeado') ?><span><?= e($aviso) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-erro"><?= icone('x') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <?= parcial('partials/botoes-sociais', ['origem' => 'entrar', 'tipo' => 'profissional']) ?>

  <div class="divisor"><?= e(t('auth.ou_email')) ?></div>

  <?= parcial('auth/formulario-entrar', ['erro' => null, 'email' => $email]) ?>

  <p class="auth-rodape"><?= e(t('auth.sem_conta')) ?> <a href="/comecar"><?= e(t('landing.cta_curto')) ?></a></p>
</div>
