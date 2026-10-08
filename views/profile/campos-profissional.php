<?php
/** @var array $valores @var array $erros @var ?\App\Entities\PerfilProfissional $perfil */
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

$valor = fn (string $campo): string => e($valores[$campo] ?? '');
?>
<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('maleta', 'icone icone-sm') ?></span>
    <div><h2>Sua área e nível</h2><p>Para filtrar as vagas pelo que você faz hoje.</p></div>
  </div>

  <div class="<?= classe_campo($erros, 'area') ?>">
    <span class="campo-rotulo">Área de atuação</span>
    <div class="grupo-opcoes">
      <?php foreach (AreaAtuacao::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="area" value="<?= e($chave) ?>" <?= ($valores['area'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'area') ?>
  </div>

  <div class="<?= classe_campo($erros, 'senioridade') ?>">
    <span class="campo-rotulo">Senioridade</span>
    <div class="grupo-opcoes">
      <?php foreach (Senioridade::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="senioridade" value="<?= e($chave) ?>" <?= ($valores['senioridade'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'senioridade') ?>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cargo_desejado') ?>">
      <label class="campo-rotulo" for="cargo_desejado">Cargo que você busca</label>
      <input class="entrada" id="cargo_desejado" name="cargo_desejado" maxlength="120" placeholder="Ex.: Desenvolvedor Back-end PHP" value="<?= $valor('cargo_desejado') ?>">
      <?= erro_campo($erros, 'cargo_desejado') ?>
    </div>
    <div class="<?= classe_campo($erros, 'anos_experiencia') ?>">
      <label class="campo-rotulo" for="anos_experiencia">Anos de experiência</label>
      <input class="entrada" id="anos_experiencia" name="anos_experiencia" type="number" min="0" max="50" inputmode="numeric" placeholder="Ex.: 3" value="<?= $valor('anos_experiencia') ?>">
      <?= erro_campo($erros, 'anos_experiencia') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('codigo', 'icone icone-sm') ?></span>
    <div><h2>Sua stack</h2><p>As tecnologias que você domina. Comece pelas principais.</p></div>
  </div>

  <div class="<?= classe_campo($erros, 'stack') ?>" data-campo-tags>
    <label class="campo-rotulo" for="stack-digitacao">Tecnologias <small>Enter ou vírgula para adicionar</small></label>
    <input type="hidden" name="stack" value="<?= $valor('stack') ?>">
    <div class="entrada-tags">
      <input id="stack-digitacao" type="text" autocomplete="off" placeholder="Ex.: React, Node.js, PostgreSQL">
    </div>
    <div class="sugestoes-tags" aria-label="Sugestões"></div>
    <?= erro_campo($erros, 'stack') ?>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('ajustes', 'icone icone-sm') ?></span>
    <div><h2>Preferências</h2><p>Só enviamos candidaturas que respeitam o que você quer.</p></div>
  </div>

  <div class="<?= classe_campo($erros, 'modelo_trabalho') ?>">
    <span class="campo-rotulo">Modelo de trabalho</span>
    <div class="grupo-opcoes">
      <?php foreach (ModeloTrabalho::opcoes() as $chave => $rotulo): ?>
        <label class="opcao"><input type="radio" name="modelo_trabalho" value="<?= e($chave) ?>" <?= ($valores['modelo_trabalho'] ?? '') === $chave ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'modelo_trabalho') ?>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cidade') ?>">
      <label class="campo-rotulo" for="cidade">Cidade <small>opcional</small></label>
      <input class="entrada" id="cidade" name="cidade" maxlength="120" autocomplete="address-level2" placeholder="Ex.: Florianópolis, SC" value="<?= $valor('cidade') ?>">
      <?= erro_campo($erros, 'cidade') ?>
    </div>
    <div class="<?= classe_campo($erros, 'pretensao_salarial') ?>">
      <label class="campo-rotulo" for="pretensao_salarial">Pretensão mensal <small>opcional</small></label>
      <div class="prefixo-entrada">
        <span>R$</span>
        <input class="entrada" id="pretensao_salarial" name="pretensao_salarial" type="number" min="0" step="100" inputmode="numeric" placeholder="8000" value="<?= $valor('pretensao_salarial') ?>">
      </div>
      <?= erro_campo($erros, 'pretensao_salarial') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('usuario', 'icone icone-sm') ?></span>
    <div><h2>Contato e links</h2><p>O WhatsApp é usado para avisar sobre entrevistas.</p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'telefone') ?>">
      <label class="campo-rotulo" for="telefone">WhatsApp</label>
      <input class="entrada" id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="(11) 91234-5678" value="<?= $valor('telefone') ?>" data-mascara-telefone>
      <?= erro_campo($erros, 'telefone') ?>
    </div>
    <div class="<?= classe_campo($erros, 'linkedin_url') ?>">
      <label class="campo-rotulo" for="linkedin_url">LinkedIn <small>opcional</small></label>
      <input class="entrada" id="linkedin_url" name="linkedin_url" type="url" placeholder="https://linkedin.com/in/voce" value="<?= $valor('linkedin_url') ?>">
      <?= erro_campo($erros, 'linkedin_url') ?>
    </div>
    <div class="<?= classe_campo($erros, 'github_url') ?>">
      <label class="campo-rotulo" for="github_url">GitHub <small>opcional</small></label>
      <input class="entrada" id="github_url" name="github_url" type="url" placeholder="https://github.com/voce" value="<?= $valor('github_url') ?>">
      <?= erro_campo($erros, 'github_url') ?>
    </div>
    <div class="<?= classe_campo($erros, 'portfolio_url') ?>">
      <label class="campo-rotulo" for="portfolio_url">Portfólio <small>opcional</small></label>
      <input class="entrada" id="portfolio_url" name="portfolio_url" type="url" placeholder="https://seusite.dev" value="<?= $valor('portfolio_url') ?>">
      <?= erro_campo($erros, 'portfolio_url') ?>
    </div>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('arquivo', 'icone icone-sm') ?></span>
    <div><h2>Currículo</h2><p>Recomendado: com ele, montamos candidaturas muito mais fortes.</p></div>
  </div>

  <div class="<?= classe_campo($erros, 'curriculo') ?>">
    <label class="zona-arquivo <?= $perfil?->possuiCurriculo() ? 'com-arquivo' : '' ?>" data-zona-arquivo>
      <input type="file" name="curriculo" accept="application/pdf,.pdf">
      <span class="zona-arquivo-icone"><?= icone($perfil?->possuiCurriculo() ? 'check' : 'upload') ?></span>
      <span>
        <strong><?= $perfil?->possuiCurriculo() ? e($perfil->curriculoNomeOriginal) : 'Envie seu currículo em PDF' ?></strong>
        <small><?= $perfil?->possuiCurriculo() ? 'Enviado · clique para substituir' : 'Arraste o arquivo ou clique aqui · até 5 MB' ?></small>
      </span>
    </label>
    <?= erro_campo($erros, 'curriculo') ?>
  </div>

  <div class="<?= classe_campo($erros, 'resumo') ?>">
    <label class="campo-rotulo" for="resumo">Algo que devemos saber? <small>opcional</small></label>
    <textarea class="area-texto" id="resumo" name="resumo" maxlength="1500" placeholder="Ex.: busco a primeira vaga internacional; não quero vagas em consultorias; prefiro produto."><?= $valor('resumo') ?></textarea>
    <?= erro_campo($erros, 'resumo') ?>
  </div>
</section>
