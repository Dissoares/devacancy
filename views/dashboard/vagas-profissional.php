<?php
/**
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes
 * @var ?string $assinaturaAte @var array<int, \App\Enums\StatusCandidatura> $statusCandidaturas
 * @var int $restantesHoje candidaturas que ainda cabem hoje @var int $limiteDiario @var int $paginaVagas
 */
$total = count($compativeis);
$semCandidatura = array_values(array_filter($compativeis, fn (array $item) => !isset($statusCandidaturas[$item['vaga']->id])));
// Só as melhores que cabem no limite de hoje vão no envio em lote.
$deHoje = array_slice($semCandidatura, 0, $restantesHoje);

// Lista em páginas de 9 (grade de 3 por 3): a tela não vira uma rolagem sem fim.
$porPagina = 9;
$paginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($paginaVagas ?? 1, $paginas);
$daPagina = array_slice($compativeis, ($pagina - 1) * $porPagina, $porPagina);
$linkPagina = fn (int $n): string => '/painel' . ($n > 1 ? '?pagina=' . $n : '') . '#vagas';
?>
<?php if ($total > 0): ?>
  <section class="cartao banner-assinatura">
    <div>
      <h2><?= t('painel.vagas_titulo', ['total' => '<span class="texto-gradiente">' . e(t($total === 1 ? 'funil.vagas_encontradas_um' : 'funil.vagas_encontradas_varios', ['n' => $total])) . '</span>']) ?></h2>
      <p>
        <?php if ($recentes > 0): ?><strong><?= e(t('painel.recentes', ['n' => $recentes])) ?></strong><?php endif; ?>
        <?= e(t($assinaturaAte ? 'painel.vagas_texto_assinante' : 'painel.vagas_texto')) ?>
      </p>
    </div>
    <?php if ($deHoje !== []): ?>
      <form method="post" action="/candidaturas" hx-post="/candidaturas">
        <?= csrf_campo() ?>
        <?php foreach ($deHoje as $item): ?><input type="hidden" name="vaga_ids[]" value="<?= $item['vaga']->id ?>"><?php endforeach; ?>
        <button class="botao botao-primario botao-grande" type="submit">
          <span class="carregando" aria-hidden="true"></span>
          <span class="rotulo-botao"><?= icone('raio', 'icone icone-sm') ?> <?= e(t(count($deHoje) < count($semCandidatura) ? 'painel.candidatar_melhores' : 'painel.candidatar_todas', ['n' => count($deHoje)])) ?></span>
        </button>
        <small class="texto-suave banner-limite"><?= e(t('painel.limite_diario', ['restantes' => $restantesHoje, 'limite' => $limiteDiario])) ?></small>
      </form>
    <?php elseif ($semCandidatura !== []): ?>
      <p class="texto-suave banner-limite"><?= icone('relogio', 'icone icone-sm') ?> <?= e(t('painel.limite_usado', ['limite' => $limiteDiario])) ?></p>
    <?php endif; ?>
  </section>

  <section class="secao-vagas" id="vagas">
    <div class="grade-vagas">
      <?php foreach ($daPagina as $item): ?>
        <?= parcial('partials/cartao-vaga', [
            'vaga' => $item['vaga'],
            'compatibilidade' => $item['compatibilidade'],
            'proxima' => $item['proxima'] ?? false,
            'bloqueada' => !$assinaturaAte,
            'comAcao' => true,
            'status' => $statusCandidaturas[$item['vaga']->id] ?? null,
        ]) ?>
      <?php endforeach; ?>
    </div>

    <?php if ($paginas > 1):
        // Mostra no máximo 7 números: o primeiro, o último e os vizinhos da página atual.
        $numeros = array_values(array_unique(array_filter(range(1, $paginas), fn (int $n) => $paginas <= 7 || $n === 1 || $n === $paginas || abs($n - $pagina) <= 1)));
    ?>
      <nav class="paginacao-vagas" aria-label="<?= e(t('painel.paginas_vagas')) ?>">
        <?php if ($pagina > 1): ?><a class="paginacao-seta" rel="prev" href="<?= e($linkPagina($pagina - 1)) ?>">← <?= e(t('vagas.anterior')) ?></a><?php else: ?><span class="paginacao-seta desativada">← <?= e(t('vagas.anterior')) ?></span><?php endif; ?>
        <span class="paginacao-numeros">
          <?php $anterior = 0; foreach ($numeros as $n): ?>
            <?php if ($anterior && $n - $anterior > 1): ?><span class="paginacao-reticencias">…</span><?php endif; ?>
            <a class="<?= $n === $pagina ? 'ativa' : '' ?>" href="<?= e($linkPagina($n)) ?>" <?= $n === $pagina ? 'aria-current="page"' : '' ?>><?= $n ?></a>
          <?php $anterior = $n; endforeach; ?>
        </span>
        <?php if ($pagina < $paginas): ?><a class="paginacao-seta" rel="next" href="<?= e($linkPagina($pagina + 1)) ?>"><?= e(t('vagas.proxima')) ?> →</a><?php else: ?><span class="paginacao-seta desativada"><?= e(t('vagas.proxima')) ?> →</span><?php endif; ?>
      </nav>
      <p class="paginacao-resumo texto-suave"><?= e(t('painel.vagas_mostrando', ['de' => ($pagina - 1) * $porPagina + 1, 'ate' => min($total, $pagina * $porPagina), 'total' => $total])) ?></p>
    <?php endif; ?>
  </section>
<?php else: ?>
  <section class="cartao cartao-painel estado-vazio">
    <span class="estado-vazio-icone"><?= icone('relogio', 'icone icone-lg') ?></span>
    <h2><?= e(t('funil.monitorando_titulo')) ?></h2>
    <p><?= e(t('funil.monitorando_texto')) ?></p>
  </section>
<?php endif; ?>
