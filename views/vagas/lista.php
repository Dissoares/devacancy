<?php
/**
 * Listagem pública de vagas por tema (área, nível, tecnologia ou remoto). Página de entrada vinda dos buscadores.
 * @var ?array $categoria @var string $tipo @var string $rotulo @var string $caminho @var \App\Entities\Vaga[] $vagas
 * @var int $total @var int $pagina @var int $paginas @var array $categorias @var array<string, int> $contagens
 */
$grupos = [
    'area' => t('vagas.por_area'),
    'nivel' => t('vagas.por_nivel'),
    'remoto' => t('vagas.por_modelo'),
    'stack' => t('vagas.por_tecnologia'),
];
$h1 = t('vagas.h1_' . $tipo, ['x' => $rotulo]);
$link = fn (int $n): string => $caminho . ($n > 1 ? '?pagina=' . $n : '');
?>
<section class="vagas-lista">
  <div class="container vagas-lista-interno">
    <nav class="vagas-migalhas" aria-label="<?= e(t('vagas.migalha_rotulo')) ?>">
      <a href="/"><?= e(t('vagas.migalha_inicio')) ?></a>
      <span aria-hidden="true">/</span>
      <?php if ($categoria): ?><a href="/vagas"><?= e(t('vagas.migalha_vagas')) ?></a><span aria-hidden="true">/</span><strong><?= e($rotulo) ?></strong>
      <?php else: ?><strong><?= e(t('vagas.migalha_vagas')) ?></strong><?php endif; ?>
    </nav>

    <header class="vagas-lista-topo">
      <h1><?= e($h1) ?></h1>
      <p class="vagas-lista-contagem"><?= e(t($total === 1 ? 'vagas.contagem_um' : 'vagas.contagem_varios', ['n' => numero($total)])) ?></p>
      <p class="vagas-lista-intro"><?= e(t('vagas.intro_' . $tipo, ['x' => $rotulo])) ?></p>
    </header>

    <div class="vagas-lista-corpo">
      <div>
        <?php if ($vagas): ?>
          <ul class="vagas-lista-itens">
            <?php foreach ($vagas as $vaga): ?>
              <li>
                <a class="vagas-lista-item" href="<?= e(url_vaga($vaga)) ?>">
                  <span class="vagas-lista-icones"><?= icones_stack($vaga->stack) ?></span>
                  <span class="vagas-lista-texto">
                    <strong><?= e($vaga->titulo) ?></strong>
                    <small>
                      <?= e($vaga->senioridade->rotulo()) ?> · <?= e($vaga->local()) ?>
                      <?php if ($faixa = $vaga->faixaSalarial()): ?> · <?= e($faixa) ?><?php endif; ?>
                    </small>
                    <?php if ($vaga->stack): ?><small class="vagas-lista-stack"><?= e(implode(' · ', array_slice($vaga->stack, 0, 4))) ?></small><?php endif; ?>
                  </span>
                  <span class="vagas-lista-tempo"><?= e(tempo_relativo($vaga->criadoEm)) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($paginas > 1): ?>
            <nav class="vagas-paginacao" aria-label="<?= e(t('vagas.paginacao')) ?>">
              <?php if ($pagina > 1): ?><a rel="prev" href="<?= e($link($pagina - 1)) ?>">← <?= e(t('vagas.anterior')) ?></a><?php endif; ?>
              <span><?= e(t('vagas.pagina_n', ['n' => $pagina, 'total' => $paginas])) ?></span>
              <?php if ($pagina < $paginas): ?><a rel="next" href="<?= e($link($pagina + 1)) ?>"><?= e(t('vagas.proxima')) ?> →</a><?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php else: ?>
          <p class="vagas-lista-vazio"><?= e(t('vagas.vazio')) ?></p>
        <?php endif; ?>

        <aside class="vagas-lista-cta">
          <h2><?= e(t('vagas.cta_titulo')) ?></h2>
          <p><?= e(t('vagas.cta_texto')) ?></p>
          <a class="botao botao-primario botao-grande" href="/comecar<?= $categoria && $tipo === 'area' ? '?area=' . e($categoria['slug']) : '' ?>"><?= e(t('vagas.cta_botao')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
        </aside>
      </div>

      <aside class="vagas-lista-filtros" aria-label="<?= e(t('vagas.outras')) ?>">
        <?php if ($categoria): ?><a class="vagas-lista-todas" href="/vagas"><?= e(t('vagas.todas')) ?></a><?php endif; ?>
        <?php foreach ($grupos as $grupo => $titulo): ?>
          <?php $itens = array_filter($categorias, fn (array $c) => $c['tipo'] === $grupo && ($contagens[$c['slug']] ?? 0) > 0); ?>
          <?php if ($itens): ?>
            <h3><?= e($titulo) ?></h3>
            <ul>
              <?php foreach ($itens as $item): ?>
                <li><a href="/vagas/<?= e($item['slug']) ?>"<?= $categoria && $categoria['slug'] === $item['slug'] ? ' aria-current="page"' : '' ?>><?= e($item['rotulo']) ?> <span><?= (int) $contagens[$item['slug']] ?></span></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endforeach; ?>
      </aside>
    </div>
  </div>
</section>
