<div class="auth-cartao">
  <div class="auth-cabecalho">
    <h1>Bem-vindo de volta</h1>
    <p>Entre para acompanhar suas candidaturas.</p>
  </div>

  <?php if (!empty($aviso)): ?>
    <div class="alerta alerta-aviso"><?= icone('cadeado') ?><span><?= e($aviso) ?></span></div>
  <?php endif; ?>
  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-erro"><?= icone('x') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <?= parcial('partials/botoes-sociais', ['origem' => 'entrar', 'tipo' => 'profissional']) ?>

  <div class="divisor">ou com e-mail</div>

  <?= parcial('auth/formulario-entrar', ['erro' => null, 'email' => $email]) ?>

  <p class="auth-rodape">Ainda não tem conta? <a href="/cadastro">Criar conta grátis</a></p>
</div>
