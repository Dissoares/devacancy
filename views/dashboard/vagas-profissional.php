<?php
/**
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes
 * @var ?string $assinaturaAte @var array<int, \App\Enums\StatusCandidatura> $statusCandidaturas
 */
$total = count($compativeis);
$semCandidatura = array_values(array_filter($compativeis, fn (array $item) => !isset($statusCandidaturas[$item['vaga']->id])));
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
    <?php if ($semCandidatura !== []): ?>
      <form method="post" action="/candidaturas" hx-post="/candidaturas">
        <?= csrf_campo() ?>
        <?php foreach ($semCandidatura as $item): ?><input type="hidden" name="vaga_ids[]" value="<?= $item['vaga']->id ?>"><?php endforeach; ?>
        <button class="botao botao-primario botao-grande" type="submit">
          <span class="carregando" aria-hidden="true"></span>
          <span class="rotulo-botao"><?= icone('raio', 'icone icone-sm') ?> <?= e(t('painel.candidatar_todas', ['n' => count($semCandidatura)])) ?></span>
        </button>
      </form>
    <?php endif; ?>
  </section>

  <section class="secao-vagas">
    <div class="grade-vagas">
      <?php foreach (array_slice($compativeis, 0, 60) as $item): ?>
        <?= parcial('partials/cartao-vaga', [
            'vaga' => $item['vaga'],
            'compatibilidade' => $item['compatibilidade'],
            'bloqueada' => !$assinaturaAte,
            'comAcao' => true,
            'status' => $statusCandidaturas[$item['vaga']->id] ?? null,
        ]) ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php else: ?>
  <section class="cartao cartao-painel estado-vazio">
    <span class="estado-vazio-icone"><?= icone('relogio', 'icone icone-lg') ?></span>
    <h2><?= e(t('funil.monitorando_titulo')) ?></h2>
    <p><?= e(t('funil.monitorando_texto')) ?></p>
  </section>
<?php endif; ?>
