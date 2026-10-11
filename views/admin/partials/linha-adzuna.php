<?php
/** @var string $id @var ?\App\DTOs\VagaDTO $vaga @var ?string $link @var bool $jaImportada */
$faixa = fn (): ?string => match (true) {
    (bool) ($vaga->salarioMin && $vaga->salarioMax) => moeda($vaga->salarioMin, $vaga->moeda->value, 0) . ' – ' . moeda($vaga->salarioMax, $vaga->moeda->value, 0),
    (bool) $vaga->salarioMin => 'a partir de ' . moeda($vaga->salarioMin, $vaga->moeda->value, 0),
    (bool) $vaga->salarioMax => 'até ' . moeda($vaga->salarioMax, $vaga->moeda->value, 0),
    default => null,
};
?>
<article class="cartao resultado-adzuna" id="resultado-adzuna-<?= e($id) ?>">
  <?php if ($vaga === null): ?>
    <p class="texto-vazio">Essa vaga não está mais disponível (faça uma nova busca).</p>
  <?php else: ?>
    <div class="resultado-adzuna-topo">
      <div>
        <strong><?= e($vaga->titulo) ?></strong>
        <small><?= e($vaga->empresa) ?><?= $vaga->cidade ? ' · ' . e($vaga->cidade) : '' ?></small>
      </div>
      <?php if ($jaImportada): ?>
        <span class="selo selo-sucesso selo-ponto">Importada</span>
      <?php else: ?>
        <form hx-post="/admin/vagas/adzuna/<?= e($id) ?>/importar" hx-target="#resultado-adzuna-<?= e($id) ?>" hx-swap="outerHTML">
          <?= csrf_campo() ?>
          <button class="botao botao-primario botao-pequeno" type="submit"><?= icone('download', 'icone icone-sm') ?> Importar</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="lista-etiquetas">
      <span class="etiqueta"><?= e($vaga->senioridade->rotulo()) ?></span>
      <span class="etiqueta"><?= e($vaga->area->rotulo()) ?></span>
      <span class="etiqueta"><?= e($vaga->modeloTrabalho->rotulo()) ?></span>
      <?php foreach (array_slice($vaga->stack, 0, 6) as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
    </div>

    <?php if ($faixa()): ?><p class="texto-suave"><?= e($faixa()) ?></p><?php endif; ?>
    <?php if ($vaga->descricao): ?><p class="resultado-adzuna-descricao"><?= e($vaga->descricao) ?></p><?php endif; ?>

    <?php if ($link): ?>
      <a class="link" href="<?= e($link) ?>" target="_blank" rel="noopener">Ver anúncio original <?= icone('externo', 'icone icone-sm') ?></a>
    <?php endif; ?>
  <?php endif; ?>
</article>
