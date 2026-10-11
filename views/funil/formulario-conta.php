<?php /** @var array $valores @var array<string, string> $erros */ ?>
<form class="formulario" method="post" action="/criar-conta" hx-post="/criar-conta" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>

  <div class="<?= classe_campo($erros, 'email') ?>">
    <label class="campo-rotulo" for="email"><?= e(t('auth.email')) ?></label>
    <input class="entrada" id="email" name="email" type="email" autocomplete="email" maxlength="190"
           placeholder="<?= e(t('auth.email_placeholder')) ?>" value="<?= e($valores['email'] ?? '') ?>" required
           hx-post="/cadastro/verificar-email" hx-trigger="input changed delay:600ms" hx-target="#dica-email-conta" hx-swap="innerHTML" hx-sync="this:replace">
    <div id="dica-email-conta" aria-live="polite"><?= erro_campo($erros, 'email') ?></div>
  </div>

  <div class="<?= classe_campo($erros, 'senha') ?>">
    <label class="campo-rotulo" for="senha"><?= e(t('auth.senha')) ?> <small><?= e(t('auth.senha_minimo')) ?></small></label>
    <div class="entrada-grupo">
      <input class="entrada" id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" maxlength="72"
             placeholder="<?= e(t('auth.senha_criar_placeholder')) ?>" required>
      <button class="entrada-acao" type="button" aria-label="<?= e(t('auth.mostrar_senha')) ?>" data-alternar-senha><?= icone('olho') ?></button>
    </div>
    <?= erro_campo($erros, 'senha') ?>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('funil.conta_botao')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-termos"><?= e(t('funil.conta_termos')) ?></p>
</form>
