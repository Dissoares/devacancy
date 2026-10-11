<?php
/**
 * Vitrine "ao vivo" da landing: vagas reais que entraram por último, com a empresa censurada.
 * @var \App\Entities\Vaga[] $vagas @var int $novas
 */
use App\Enums\ModeloTrabalho;

$iconesArea = ['frontend' => 'camadas', 'backend' => 'terminal', 'fullstack' => 'codigo', 'mobile' => 'raio', 'dados' => 'grafico', 'devops' => 'ajustes', 'qa' => 'check'];
?>
<aside class="vitrine" aria-label="<?= e(t('landing.vitrine_titulo')) ?>"
       hx-get="/vitrine" hx-trigger="every 60s" hx-swap="outerHTML">
  <div class="vitrine-topo">
    <span class="vitrine-ao-vivo"><span aria-hidden="true"></span> <?= e(t('landing.vitrine_ao_vivo')) ?></span>
    <strong><?= e(t('landing.vitrine_titulo')) ?></strong>
    <?php if ($novas > 0): ?><span class="vitrine-novas"><?= e(t('landing.vitrine_novas', ['n' => $novas])) ?></span><?php endif; ?>
  </div>

  <ul class="vitrine-lista">
    <?php foreach ($vagas as $vaga): ?>
      <?php
      $local = $vaga->modeloTrabalho === ModeloTrabalho::Remoto ? t('modelo.remoto') : ($vaga->cidade ?? $vaga->modeloTrabalho->rotulo());
      $detalhe = $vaga->salarioMax ? t('funil.feed_pagando_ate', ['valor' => moeda($vaga->salarioMax, $vaga->moeda, 0)]) : $vaga->senioridade->rotulo();
      ?>
      <li class="vitrine-item">
        <span class="vitrine-icone"><?= icones_stack($vaga->stack, 2) ?: icone($iconesArea[$vaga->area->value] ?? 'codigo', 'icone icone-sm') ?></span>
        <div class="vitrine-texto">
          <strong><?= e($vaga->titulo) ?></strong>
          <small><span class="empresa-censurada" style="--largura: 4em"></span> · <?= e($local) ?> · <?= e($detalhe) ?></small>
        </div>
        <span class="vitrine-tempo"><?= e(tempo_relativo($vaga->criadoEm)) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>

  <a class="vitrine-cta" href="#comecar"><?= e(t('landing.vitrine_cta')) ?> <?= icone('seta', 'icone icone-sm') ?></a>
</aside>
