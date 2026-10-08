<form id="formulario-entrar" class="formulario" method="post" action="/entrar"
      hx-post="/entrar" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <div class="campo">
    <label class="campo-rotulo" for="email">E-mail</label>
    <input class="entrada" id="email" name="email" type="email" autocomplete="email" placeholder="voce@email.com"
           value="<?= e($email ?? '') ?>" required <?= empty($email) ? 'autofocus' : '' ?>>
  </div>

  <div class="campo">
    <label class="campo-rotulo" for="senha">Senha</label>
    <div class="entrada-grupo">
      <input class="entrada" id="senha" name="senha" type="password" autocomplete="current-password" placeholder="Sua senha" required
             <?= !empty($email) ? 'autofocus' : '' ?>>
      <button class="entrada-acao" type="button" aria-label="Mostrar senha" data-alternar-senha><?= icone('olho') ?></button>
    </div>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao">Entrar</span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>
</form>
