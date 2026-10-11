<?php
/** @var string $termo @var string $nivel @var int $pagina @var array $linhas @var ?string $erro */
use App\Enums\Senioridade;
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Importar vagas da Adzuna</h1>
    <p>Busque vagas reais e escolha quais entram para o sistema.</p>
  </div>
  <a class="botao botao-secundario" href="/admin/vagas"><?= icone('chevron-esq', 'icone icone-sm') ?> Voltar para Vagas</a>
</section>

<form class="filtros cartao" method="get" action="/admin/vagas/adzuna"
      hx-get="/admin/vagas/adzuna" hx-target="#resultados-adzuna" hx-push-url="true">
  <label class="filtro-busca">
    <?= icone('busca', 'icone icone-sm') ?>
    <input class="entrada" type="text" name="termo" value="<?= e($termo) ?>" placeholder="Tecnologia ou cargo: React, Python, DevOps..." required autocomplete="off">
  </label>
  <select class="selecao" name="nivel" aria-label="Nível">
    <option value="">Qualquer nível</option>
    <?php foreach (Senioridade::cases() as $nivelOpcao): ?>
      <option value="<?= e($nivelOpcao->value) ?>" <?= $nivel === $nivelOpcao->value ? 'selected' : '' ?>><?= e($nivelOpcao->rotulo()) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="botao botao-primario" type="submit">Buscar</button>
</form>

<div id="resultados-adzuna">
  <?= parcial('admin/partials/resultados-adzuna', ['linhas' => $linhas, 'termo' => $termo, 'nivel' => $nivel, 'pagina' => $pagina, 'erro' => $erro]) ?>
</div>
