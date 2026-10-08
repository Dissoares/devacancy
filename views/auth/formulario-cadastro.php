<?php
/** @var \App\Enums\TipoUsuario $tipo @var array $valores @var array<string, string> $erros */
use App\Enums\TipoUsuario;

?>
<form id="formulario-cadastro" class="formulario" method="post" action="/cadastro"
      hx-post="/cadastro" hx-target="this" hx-swap="outerHTML"
      data-formulario-tipo novalidate>
  <?= csrf_campo() ?>

  <div class="segmentado" role="radiogroup" aria-label="Tipo de conta">
    <span class="indicador" aria-hidden="true"></span>
    <?php foreach (TipoUsuario::publicos() as $opcao): ?>
      <label>
        <input type="radio" name="tipo" value="<?= e($opcao->value) ?>" <?= $tipo === $opcao ? 'checked' : '' ?>>
        <?= icone($opcao === TipoUsuario::Empresa ? 'predio' : 'terminal', 'icone icone-sm') ?>
        <?= $opcao === TipoUsuario::Empresa ? 'Sou empresa' : 'Sou profissional' ?>
      </label>
    <?php endforeach; ?>
  </div>

  <?= parcial('partials/botoes-sociais', ['origem' => 'cadastro', 'tipo' => $tipo->value]) ?>

  <div class="divisor">ou com e-mail</div>

  <div class="<?= classe_campo($erros, 'nome') ?>">
    <label class="campo-rotulo" for="nome">
      <span class="somente-profissional">Nome completo</span>
      <span class="somente-empresa">Seu nome (responsável)</span>
    </label>
    <input class="entrada" id="nome" name="nome" type="text" autocomplete="name" maxlength="120"
           placeholder="Como você quer ser chamado" value="<?= e($valores['nome'] ?? '') ?>" required>
    <?= erro_campo($erros, 'nome') ?>
  </div>

  <div class="<?= classe_campo($erros, 'email') ?>">
    <label class="campo-rotulo" for="email">
      <span class="somente-profissional">E-mail</span>
      <span class="somente-empresa">E-mail corporativo</span>
    </label>
    <input class="entrada" id="email" name="email" type="email" autocomplete="email" maxlength="190"
           placeholder="voce@email.com" value="<?= e($valores['email'] ?? '') ?>" required
           hx-post="/cadastro/verificar-email" hx-trigger="input changed delay:600ms" hx-target="#dica-email" hx-swap="innerHTML" hx-sync="this:replace">
    <div id="dica-email" aria-live="polite"><?= erro_campo($erros, 'email') ?></div>
  </div>

  <div class="<?= classe_campo($erros, 'senha') ?>">
    <label class="campo-rotulo" for="senha">Senha <small>mínimo de 8 caracteres</small></label>
    <div class="entrada-grupo">
      <input class="entrada" id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" maxlength="72"
             placeholder="Crie uma senha forte" required data-forca-senha-alvo>
      <button class="entrada-acao" type="button" aria-label="Mostrar senha" data-alternar-senha>
        <?= icone('olho') ?>
      </button>
    </div>
    <div class="forca-senha" data-forca-senha aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <?= erro_campo($erros, 'senha') ?>
  </div>

  <div class="<?= classe_campo($erros, 'termos') ?>">
    <label class="caixa-selecao">
      <input type="checkbox" name="termos" value="1" <?= !empty($valores['termos']) ? 'checked' : '' ?>>
      <span>
        <span class="somente-profissional">Concordo com os termos de uso e autorizo a Devacancy a usar meus dados para encontrar vagas e enviar candidaturas em meu nome.</span>
        <span class="somente-empresa">Concordo com os termos de uso e autorizo a Devacancy a usar os dados da empresa para conectá-la a profissionais.</span>
      </span>
    </label>
    <?= erro_campo($erros, 'termos') ?>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao">Criar minha conta</span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>
</form>
