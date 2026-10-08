<?php
/**
 * @var \App\Entities\Vaga $vaga @var int $compatibilidade
 * @var bool $bloqueada Sem assinatura, empresa e link nem chegam ao HTML.
 */
$bloqueada ??= true;
?>
<article class="cartao-vaga<?= $bloqueada ? ' bloqueada' : '' ?>">
  <div class="cartao-vaga-topo">
    <span class="compatibilidade" style="--valor: <?= (int) $compatibilidade ?>%"><?= (int) $compatibilidade ?>% compatível</span>
    <?php if (strtotime($vaga->criadoEm) >= time() - 86400): ?>
      <span class="selo selo-primario">Nova</span>
    <?php endif; ?>
  </div>

  <h3><?= e($vaga->titulo) ?></h3>

  <p class="cartao-vaga-empresa">
    <?= icone('predio', 'icone icone-sm') ?>
    <?php if ($bloqueada): ?>
      <span class="texto-borrado" aria-label="Empresa disponível para assinantes">Empresa confidencial</span>
      <?= icone('cadeado', 'icone icone-sm') ?>
    <?php else: ?>
      <span><?= e($vaga->empresa) ?></span>
    <?php endif; ?>
  </p>

  <ul class="cartao-vaga-dados">
    <li><?= icone('local', 'icone icone-sm') ?> <?= e($vaga->local()) ?></li>
    <li><?= icone('maleta', 'icone icone-sm') ?> <?= e($vaga->senioridade->rotulo()) ?></li>
    <?php if ($faixa = $vaga->faixaSalarial()): ?>
      <li><?= icone('grafico', 'icone icone-sm') ?> <?= e($faixa) ?></li>
    <?php endif; ?>
  </ul>

  <div class="lista-etiquetas">
    <?php foreach (array_slice($vaga->stack, 0, 5) as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
  </div>

  <?php if (!$bloqueada): ?>
    <p class="cartao-vaga-status"><span class="selo selo-sucesso selo-ponto">Na fila de candidatura</span></p>
  <?php endif; ?>
</article>
