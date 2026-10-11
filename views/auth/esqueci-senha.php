<?php /** @var string $email @var ?string $erro @var bool $enviado */ ?>
<div class="auth-cartao">
  <div class="auth-cabecalho">
    <h1><?= e(t('recuperar.titulo')) ?></h1>
    <p><?= e(t('recuperar.texto')) ?></p>
  </div>

  <?php if ($enviado): ?>
    <div class="alerta alerta-sucesso" role="status"><?= icone('check') ?><span><?= e(t('recuperar.enviado', ['email' => $email])) ?></span></div>
    <p class="auth-rodape"><?= e(t('recuperar.nao_chegou')) ?></p>
  <?php else: ?>
    <form class="formulario" method="post" action="/esqueci-senha" novalidate>
      <?= csrf_campo() ?>

      <?php if (!empty($erro)): ?>
        <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erro) ?></span></div>
      <?php endif; ?>

      <div class="campo">
        <label class="campo-rotulo" for="email"><?= e(t('auth.email')) ?></label>
        <input class="entrada" id="email" name="email" type="email" autocomplete="email" placeholder="<?= e(t('auth.email_placeholder')) ?>"
               value="<?= e($email) ?>" required autofocus>
      </div>

      <button class="botao botao-primario botao-grande botao-bloco" type="submit">
        <span class="rotulo-botao"><?= e(t('recuperar.enviar')) ?></span>
        <?= icone('seta', 'icone icone-seta') ?>
      </button>
    </form>
  <?php endif; ?>

  <p class="auth-rodape"><a href="/entrar"><?= e(t('recuperar.voltar')) ?></a></p>
</div>
