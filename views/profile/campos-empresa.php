<?php
/** @var array $valores @var array $erros */
use App\Enums\TamanhoEmpresa;

$valor = fn (string $campo): string => e($valores[$campo] ?? '');
?>
<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('predio', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('empresa.bloco_empresa')) ?></h2><p><?= e(t('empresa.bloco_empresa_texto')) ?></p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'nome_empresa') ?>">
      <label class="campo-rotulo" for="nome_empresa"><?= e(t('empresa.nome')) ?></label>
      <input class="entrada" id="nome_empresa" name="nome_empresa" maxlength="150" autocomplete="organization" placeholder="<?= e(t('empresa.nome_placeholder')) ?>" value="<?= $valor('nome_empresa') ?>">
      <?= erro_campo($erros, 'nome_empresa') ?>
    </div>
    <div class="<?= classe_campo($erros, 'site') ?>">
      <label class="campo-rotulo" for="site"><?= e(t('empresa.site')) ?> <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="site" name="site" type="url" placeholder="https://suaempresa.com.br" value="<?= $valor('site') ?>">
      <?= erro_campo($erros, 'site') ?>
    </div>
  </div>

  <div class="<?= classe_campo($erros, 'tamanho') ?>">
    <span class="campo-rotulo"><?= e(t('empresa.tamanho')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (TamanhoEmpresa::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="tamanho" value="<?= e($chave) ?>" <?= ($valores['tamanho'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'tamanho') ?>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('codigo', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('empresa.bloco_contratacao')) ?></h2><p><?= e(t('empresa.bloco_contratacao_texto')) ?></p></div>
  </div>

  <div class="<?= classe_campo($erros, 'stacks_contratacao') ?>" data-campo-tags>
    <label class="campo-rotulo" for="stacks-digitacao"><?= e(t('empresa.tecnologias')) ?> <small><?= e(t('perfil.tags_dica')) ?></small></label>
    <input type="hidden" name="stacks_contratacao" value="<?= $valor('stacks_contratacao') ?>">
    <div class="entrada-tags">
      <input id="stacks-digitacao" type="text" autocomplete="off" placeholder="<?= e(t('empresa.tecnologias_placeholder')) ?>">
    </div>
    <div class="sugestoes-tags" aria-label="<?= e(t('perfil.sugestoes')) ?>"></div>
    <?= erro_campo($erros, 'stacks_contratacao') ?>
  </div>

  <div class="<?= classe_campo($erros, 'vagas_abertas') ?>">
    <label class="campo-rotulo" for="vagas_abertas"><?= e(t('empresa.vagas_abertas')) ?> <small><?= e(t('empresa.aproximadamente')) ?></small></label>
    <input class="entrada" id="vagas_abertas" name="vagas_abertas" type="number" min="0" max="999" inputmode="numeric" placeholder="3" value="<?= $valor('vagas_abertas') ?>">
    <?= erro_campo($erros, 'vagas_abertas') ?>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('usuario', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('empresa.bloco_contato')) ?></h2><p><?= e(t('empresa.bloco_contato_texto')) ?></p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cargo_contato') ?>">
      <label class="campo-rotulo" for="cargo_contato"><?= e(t('empresa.cargo')) ?></label>
      <input class="entrada" id="cargo_contato" name="cargo_contato" maxlength="120" autocomplete="organization-title" placeholder="Tech Recruiter, CTO" value="<?= $valor('cargo_contato') ?>">
      <?= erro_campo($erros, 'cargo_contato') ?>
    </div>
    <div class="<?= classe_campo($erros, 'telefone') ?>">
      <label class="campo-rotulo" for="telefone">WhatsApp</label>
      <input class="entrada" id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="<?= e(t('perfil.telefone_placeholder')) ?>" value="<?= $valor('telefone') ?>" data-mascara-telefone>
      <?= erro_campo($erros, 'telefone') ?>
    </div>
  </div>

  <div class="<?= classe_campo($erros, 'mensagem') ?>">
    <label class="campo-rotulo" for="mensagem"><?= e(t('empresa.mensagem')) ?> <small><?= e(t('perfil.opcional')) ?></small></label>
    <textarea class="area-texto" id="mensagem" name="mensagem" maxlength="1500" placeholder="<?= e(t('empresa.mensagem_placeholder')) ?>"><?= $valor('mensagem') ?></textarea>
    <?= erro_campo($erros, 'mensagem') ?>
  </div>
</section>
