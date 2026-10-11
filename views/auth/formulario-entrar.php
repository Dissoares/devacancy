<form id="formulario-entrar" class="formulario" method="post" action="/entrar"
      hx-post="/entrar" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <div class="campo">
    <label class="campo-rotulo" for="email"><?= e(t('auth.email')) ?></label>
    <input class="entrada" id="email" name="email" type="email" autocomplete="email" placeholder="<?= e(t('auth.email_placeholder')) ?>"
           value="<?= e($email ?? '') ?>" required <?= empty($email) ? 'autofocus' : '' ?>>
  </div>

  <div class="campo">
    <label class="campo-rotulo" for="senha"><?= e(t('auth.senha')) ?></label>
    <div class="entrada-grupo">
      <input class="entrada" id="senha" name="senha" type="password" autocomplete="current-password" placeholder="<?= e(t('auth.senha_placeholder')) ?>" required
             <?= !empty($email) ? 'autofocus' : '' ?>>
      <button class="entrada-acao" type="button" aria-label="<?= e(t('auth.mostrar_senha')) ?>" data-alternar-senha><?= icone('olho') ?></button>
    </div>
  </div>

  <a class="link-esqueci" href="/esqueci-senha<?= !empty($email) ? '?email=' . urlencode($email) : '' ?>"><?= e(t('recuperar.link')) ?></a>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('auth.entrar')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>
</form>
