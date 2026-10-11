<?php /** @var string $token @var bool $valido @var array<string, string> $erros */ ?>
<div class="auth-cartao">
  <?php if (!$valido): ?>
    <div class="auth-cabecalho">
      <h1><?= e(t('recuperar.link_invalido_titulo')) ?></h1>
      <p><?= e(t('recuperar.link_invalido')) ?></p>
    </div>
    <a class="botao botao-primario botao-grande botao-bloco" href="/esqueci-senha"><span class="rotulo-botao"><?= e(t('recuperar.pedir_outro')) ?></span></a>
  <?php else: ?>
    <div class="auth-cabecalho">
      <h1><?= e(t('recuperar.nova_titulo')) ?></h1>
      <p><?= e(t('recuperar.nova_texto')) ?></p>
    </div>

    <form class="formulario" method="post" action="/redefinir-senha/<?= e($token) ?>" novalidate>
      <?= csrf_campo() ?>

      <div class="<?= classe_campo($erros, 'senha') ?>">
        <label class="campo-rotulo" for="senha"><?= e(t('recuperar.nova_senha')) ?> <small><?= e(t('auth.senha_minimo')) ?></small></label>
        <div class="entrada-grupo">
          <input class="entrada" id="senha" name="senha" type="password" autocomplete="new-password" placeholder="<?= e(t('auth.senha_criar_placeholder')) ?>" required autofocus>
          <button class="entrada-acao" type="button" aria-label="<?= e(t('auth.mostrar_senha')) ?>" data-alternar-senha><?= icone('olho') ?></button>
        </div>
        <?= erro_campo($erros, 'senha') ?>
      </div>

      <div class="<?= classe_campo($erros, 'confirmacao') ?>">
        <label class="campo-rotulo" for="confirmacao"><?= e(t('recuperar.confirmar_senha')) ?></label>
        <input class="entrada" id="confirmacao" name="confirmacao" type="password" autocomplete="new-password" required>
        <?= erro_campo($erros, 'confirmacao') ?>
      </div>

      <button class="botao botao-primario botao-grande botao-bloco" type="submit">
        <span class="rotulo-botao"><?= e(t('recuperar.salvar')) ?></span>
        <?= icone('seta', 'icone icone-seta') ?>
      </button>
    </form>
  <?php endif; ?>
</div>
