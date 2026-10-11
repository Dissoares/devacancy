<?php
/** @var array $valores @var array $erros @var ?\App\Entities\PerfilProfissional $perfil */
use App\Enums\AreaAtuacao;
use App\Enums\IdiomaFalado;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

$valor = fn (string $campo): string => e($valores[$campo] ?? '');
$moeda = $valores['moeda_pretensao'] ?? 'BRL';
$idiomasMarcados = (array) ($valores['idiomas'] ?? []);
?>
<input type="hidden" name="moeda_pretensao" value="<?= e($moeda) ?>">

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('maleta', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('perfil.bloco_area')) ?></h2><p><?= e(t('perfil.bloco_area_texto')) ?></p></div>
  </div>

  <div class="<?= classe_campo($erros, 'area') ?>">
    <span class="campo-rotulo"><?= e(t('perfil.area')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (AreaAtuacao::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="area" value="<?= e($chave) ?>" <?= ($valores['area'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'area') ?>
  </div>

  <div class="<?= classe_campo($erros, 'senioridade') ?>">
    <span class="campo-rotulo"><?= e(t('perfil.senioridade')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (Senioridade::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="senioridade" value="<?= e($chave) ?>" <?= ($valores['senioridade'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'senioridade') ?>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cargo_desejado') ?>">
      <label class="campo-rotulo" for="cargo_desejado"><?= e(t('perfil.cargo')) ?></label>
      <input class="entrada" id="cargo_desejado" name="cargo_desejado" maxlength="120" placeholder="<?= e(t('perfil.cargo_placeholder')) ?>" value="<?= $valor('cargo_desejado') ?>">
      <?= erro_campo($erros, 'cargo_desejado') ?>
    </div>
    <div class="<?= classe_campo($erros, 'anos_experiencia') ?>">
      <label class="campo-rotulo" for="anos_experiencia"><?= e(t('perfil.anos')) ?></label>
      <input class="entrada" id="anos_experiencia" name="anos_experiencia" type="number" min="0" max="50" inputmode="numeric" placeholder="3" value="<?= $valor('anos_experiencia') ?>">
      <?= erro_campo($erros, 'anos_experiencia') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('codigo', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('perfil.bloco_stack')) ?></h2><p><?= e(t('perfil.bloco_stack_texto')) ?></p></div>
  </div>

  <div class="<?= classe_campo($erros, 'stack') ?>" data-campo-tags>
    <label class="campo-rotulo" for="stack-digitacao"><?= e(t('perfil.tecnologias')) ?> <small><?= e(t('perfil.tags_dica')) ?></small></label>
    <input type="hidden" name="stack" value="<?= $valor('stack') ?>">
    <div class="entrada-tags">
      <input id="stack-digitacao" type="text" autocomplete="off" placeholder="<?= e(t('funil.p_stack_placeholder')) ?>">
    </div>
    <div class="sugestoes-tags" aria-label="<?= e(t('perfil.sugestoes')) ?>"></div>
    <?= erro_campo($erros, 'stack') ?>
  </div>

  <div class="<?= classe_campo($erros, 'idiomas') ?>">
    <span class="campo-rotulo"><?= e(t('funil.p_idiomas')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (IdiomaFalado::cases() as $idioma): ?>
        <label class="opcao"><input type="checkbox" name="idiomas[]" value="<?= e($idioma->value) ?>" <?= in_array($idioma->value, $idiomasMarcados, true) ? 'checked' : '' ?>><span><?= e($idioma->rotulo()) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'idiomas') ?>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('ajustes', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('perfil.bloco_preferencias')) ?></h2><p><?= e(t('perfil.bloco_preferencias_texto')) ?></p></div>
  </div>

  <div class="<?= classe_campo($erros, 'modelo_trabalho') ?>">
    <span class="campo-rotulo"><?= e(t('perfil.modelo')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (ModeloTrabalho::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="modelo_trabalho" value="<?= e($chave) ?>" <?= ($valores['modelo_trabalho'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'modelo_trabalho') ?>
  </div>

  <div class="<?= classe_campo($erros, 'regiao_vagas') ?>">
    <span class="campo-rotulo"><?= e(t('funil.p_regiao')) ?></span>
    <div class="grupo-opcoes">
      <?php foreach (RegiaoVagas::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="regiao_vagas" value="<?= e($chave) ?>" <?= ($valores['regiao_vagas'] ?? 'brasil') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'regiao_vagas') ?>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cidade') ?>">
      <label class="campo-rotulo" for="cidade"><?= e(t('perfil.cidade')) ?> <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="cidade" name="cidade" maxlength="120" autocomplete="address-level2" placeholder="<?= e(t('funil.p_cidade_placeholder')) ?>" value="<?= $valor('cidade') ?>">
      <?= erro_campo($erros, 'cidade') ?>
    </div>
    <div class="<?= classe_campo($erros, 'pretensao_salarial') ?>">
      <label class="campo-rotulo" for="pretensao_salarial"><?= e(t('funil.p_pretensao')) ?> <small><?= e($moeda === 'BRL' ? t('funil.por_mes') : t('funil.por_ano')) ?></small></label>
      <div class="prefixo-entrada">
        <span><?= $moeda === 'BRL' ? 'R$' : '$' ?></span>
        <input class="entrada" id="pretensao_salarial" name="pretensao_salarial" type="number" min="0" step="100" inputmode="numeric" value="<?= $valor('pretensao_salarial') ?>">
      </div>
      <?= erro_campo($erros, 'pretensao_salarial') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('usuario', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('perfil.bloco_contato')) ?></h2><p><?= e(t('perfil.bloco_contato_texto')) ?></p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'telefone') ?>">
      <label class="campo-rotulo" for="telefone">WhatsApp <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel" placeholder="<?= e(t('perfil.telefone_placeholder')) ?>" value="<?= $valor('telefone') ?>">
      <?= erro_campo($erros, 'telefone') ?>
    </div>
    <div class="<?= classe_campo($erros, 'linkedin_url') ?>">
      <label class="campo-rotulo" for="linkedin_url">LinkedIn <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="linkedin_url" name="linkedin_url" type="url" placeholder="https://linkedin.com/in/..." value="<?= $valor('linkedin_url') ?>">
      <?= erro_campo($erros, 'linkedin_url') ?>
    </div>
    <div class="<?= classe_campo($erros, 'github_url') ?>">
      <label class="campo-rotulo" for="github_url">GitHub <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="github_url" name="github_url" type="url" placeholder="https://github.com/..." value="<?= $valor('github_url') ?>">
      <?= erro_campo($erros, 'github_url') ?>
    </div>
    <div class="<?= classe_campo($erros, 'portfolio_url') ?>">
      <label class="campo-rotulo" for="portfolio_url"><?= e(t('perfil.portfolio')) ?> <small><?= e(t('perfil.opcional')) ?></small></label>
      <input class="entrada" id="portfolio_url" name="portfolio_url" type="url" placeholder="https://..." value="<?= $valor('portfolio_url') ?>">
      <?= erro_campo($erros, 'portfolio_url') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('arquivo', 'icone icone-sm') ?></span>
    <div><h2><?= e(t('perfil.bloco_curriculo')) ?></h2><p><?= e(t('perfil.bloco_curriculo_texto')) ?></p></div>
  </div>

  <div class="<?= classe_campo($erros, 'curriculo') ?>">
    <label class="zona-arquivo <?= $perfil?->possuiCurriculo() ? 'com-arquivo' : '' ?>" data-zona-arquivo>
      <input type="file" name="curriculo" accept="application/pdf,.pdf">
      <span class="zona-arquivo-icone"><?= icone($perfil?->possuiCurriculo() ? 'check' : 'upload') ?></span>
      <span>
        <strong><?= $perfil?->possuiCurriculo() ? e($perfil->curriculoNomeOriginal) : e(t('perfil.curriculo_enviar')) ?></strong>
        <small><?= e(t($perfil?->possuiCurriculo() ? 'perfil.curriculo_substituir' : 'perfil.curriculo_dica')) ?></small>
      </span>
    </label>
    <?= erro_campo($erros, 'curriculo') ?>
  </div>

  <div class="<?= classe_campo($erros, 'resumo') ?>">
    <label class="campo-rotulo" for="resumo"><?= e(t('perfil.resumo')) ?> <small><?= e(t('perfil.opcional')) ?></small></label>
    <textarea class="area-texto" id="resumo" name="resumo" maxlength="1500" placeholder="<?= e(t('perfil.resumo_placeholder')) ?>"><?= $valor('resumo') ?></textarea>
    <?= erro_campo($erros, 'resumo') ?>
  </div>
</section>
