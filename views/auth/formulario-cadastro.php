<?php
/** Cadastro de empresas (profissionais entram pelo questionário em /comecar). @var array $valores @var array<string, string> $erros */
?>
<form id="formulario-cadastro" class="formulario" method="post" action="/cadastro"
      hx-post="/cadastro" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>
  <input type="hidden" name="tipo" value="empresa">

  <?= parcial('partials/botoes-sociais', ['origem' => 'cadastro', 'tipo' => 'empresa']) ?>

  <div class="<?= classe_campo($erros, 'nome') ?>">
    <label class="campo-rotulo" for="nome"><?= e(t('cadastro.nome_responsavel')) ?></label>
    <input class="entrada" id="nome" name="nome" type="text" autocomplete="name" maxlength="120"
           placeholder="<?= e(t('cadastro.nome_placeholder')) ?>" value="<?= e($valores['nome'] ?? '') ?>" required>
    <?= erro_campo($erros, 'nome') ?>
  </div>

  <div class="<?= classe_campo($erros, 'email') ?>">
    <label class="campo-rotulo" for="email"><?= e(t('cadastro.email_corporativo')) ?></label>
    <input class="entrada" id="email" name="email" type="email" autocomplete="email" maxlength="190"
           placeholder="<?= e(t('auth.email_placeholder')) ?>" value="<?= e($valores['email'] ?? '') ?>" required
           hx-post="/cadastro/verificar-email" hx-trigger="input changed delay:600ms" hx-target="#dica-email" hx-swap="innerHTML" hx-sync="this:replace">
    <div id="dica-email" aria-live="polite"><?= erro_campo($erros, 'email') ?></div>
  </div>

  <div class="<?= classe_campo($erros, 'senha') ?>">
    <label class="campo-rotulo" for="senha"><?= e(t('auth.senha')) ?> <small><?= e(t('auth.senha_minimo')) ?></small></label>
    <div class="entrada-grupo">
      <input class="entrada" id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" maxlength="72"
             placeholder="<?= e(t('auth.senha_criar_placeholder')) ?>" required data-forca-senha-alvo>
      <button class="entrada-acao" type="button" aria-label="<?= e(t('auth.mostrar_senha')) ?>" data-alternar-senha><?= icone('olho') ?></button>
    </div>
    <div class="forca-senha" data-forca-senha aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <?= erro_campo($erros, 'senha') ?>
  </div>

  <div class="<?= classe_campo($erros, 'termos') ?>">
    <label class="caixa-selecao">
      <input type="checkbox" name="termos" value="1" <?= !empty($valores['termos']) ? 'checked' : '' ?>>
      <span><?= e(t('cadastro.termos_empresa')) ?></span>
    </label>
    <?= erro_campo($erros, 'termos') ?>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao"><?= e(t('cadastro.criar_conta')) ?></span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>
</form>
