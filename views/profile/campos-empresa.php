<?php
/** @var array $valores @var array $erros */
use App\Enums\TamanhoEmpresa;

$valor = fn (string $campo): string => e($valores[$campo] ?? '');
?>
<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('predio', 'icone icone-sm') ?></span>
    <div><h2>Sua empresa</h2><p>Informações básicas para apresentar a empresa aos devs.</p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'nome_empresa') ?>">
      <label class="campo-rotulo" for="nome_empresa">Nome da empresa</label>
      <input class="entrada" id="nome_empresa" name="nome_empresa" maxlength="150" autocomplete="organization" placeholder="Ex.: Acme Tecnologia" value="<?= $valor('nome_empresa') ?>">
      <?= erro_campo($erros, 'nome_empresa') ?>
    </div>
    <div class="<?= classe_campo($erros, 'site') ?>">
      <label class="campo-rotulo" for="site">Site <small>opcional</small></label>
      <input class="entrada" id="site" name="site" type="url" placeholder="https://suaempresa.com.br" value="<?= $valor('site') ?>">
      <?= erro_campo($erros, 'site') ?>
    </div>
  </div>

  <div class="<?= classe_campo($erros, 'tamanho') ?>">
    <span class="campo-rotulo">Tamanho da empresa</span>
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
    <div><h2>Quem vocês contratam</h2><p>Para mostrar os perfis certos assim que o acesso abrir.</p></div>
  </div>

  <div class="<?= classe_campo($erros, 'stacks_contratacao') ?>" data-campo-tags>
    <label class="campo-rotulo" for="stacks-digitacao">Tecnologias que vocês usam <small>Enter ou vírgula para adicionar</small></label>
    <input type="hidden" name="stacks_contratacao" value="<?= $valor('stacks_contratacao') ?>">
    <div class="entrada-tags">
      <input id="stacks-digitacao" type="text" autocomplete="off" placeholder="Ex.: Java, Spring Boot, AWS">
    </div>
    <div class="sugestoes-tags" aria-label="Sugestões"></div>
    <?= erro_campo($erros, 'stacks_contratacao') ?>
  </div>

  <div class="<?= classe_campo($erros, 'vagas_abertas') ?>">
    <label class="campo-rotulo" for="vagas_abertas">Vagas abertas hoje <small>aproximadamente</small></label>
    <input class="entrada" id="vagas_abertas" name="vagas_abertas" type="number" min="0" max="999" inputmode="numeric" placeholder="Ex.: 3" value="<?= $valor('vagas_abertas') ?>">
    <?= erro_campo($erros, 'vagas_abertas') ?>
  </div>
</section>

<section class="bloco-formulario">
  <div class="bloco-titulo">
    <span><?= icone('usuario', 'icone icone-sm') ?></span>
    <div><h2>Seu contato</h2><p>Quem vai conversar com a gente sobre as contratações.</p></div>
  </div>

  <div class="linha-campos">
    <div class="<?= classe_campo($erros, 'cargo_contato') ?>">
      <label class="campo-rotulo" for="cargo_contato">Seu cargo</label>
      <input class="entrada" id="cargo_contato" name="cargo_contato" maxlength="120" autocomplete="organization-title" placeholder="Ex.: Tech Recruiter, CTO" value="<?= $valor('cargo_contato') ?>">
      <?= erro_campo($erros, 'cargo_contato') ?>
    </div>
    <div class="<?= classe_campo($erros, 'telefone') ?>">
      <label class="campo-rotulo" for="telefone">WhatsApp</label>
      <input class="entrada" id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="(11) 91234-5678" value="<?= $valor('telefone') ?>" data-mascara-telefone>
      <?= erro_campo($erros, 'telefone') ?>
    </div>
  </div>

  <div class="<?= classe_campo($erros, 'mensagem') ?>">
    <label class="campo-rotulo" for="mensagem">Conte o que vocês precisam <small>opcional</small></label>
    <textarea class="area-texto" id="mensagem" name="mensagem" maxlength="1500" placeholder="Ex.: buscamos 2 devs back-end pleno para um produto SaaS, 100% remoto."><?= $valor('mensagem') ?></textarea>
    <?= erro_campo($erros, 'mensagem') ?>
  </div>
</section>
