<div class="auth-cartao">
  <div class="auth-cabecalho">
    <h1><?= e(t('cadastro.empresa_titulo')) ?></h1>
    <p><?= e(t('cadastro.empresa_texto')) ?></p>
  </div>

  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-aviso"><?= icone('relogio') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <?= parcial('auth/formulario-cadastro', ['tipo' => $tipo, 'valores' => $valores, 'erros' => $erros]) ?>

  <p class="auth-rodape"><?= e(t('cadastro.e_profissional')) ?> <a href="/comecar"><?= e(t('landing.cta_curto')) ?></a></p>
  <p class="auth-rodape"><?= e(t('auth.ja_tem_conta')) ?> <a href="/entrar"><?= e(t('auth.entrar')) ?></a></p>
</div>
