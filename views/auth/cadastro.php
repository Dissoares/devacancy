<div class="auth-cartao">
  <div class="auth-cabecalho">
    <h1>Crie sua conta</h1>
    <p>Leva menos de um minuto. Sem cartão de crédito.</p>
  </div>

  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-aviso"><?= icone('relogio') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <?= parcial('auth/formulario-cadastro', ['tipo' => $tipo, 'valores' => $valores, 'erros' => $erros]) ?>

  <p class="auth-rodape">Já tem uma conta? <a href="/entrar">Entrar</a></p>
</div>
