<?php
/**
 * Radar do questionário. O próprio elemento se recarrega (HTMX) a cada resposta, com o formulário inteiro na consulta.
 * Sem vaga ainda, fica vazio (não mostra "0 vagas").
 * @var int $total @var bool $noBrasil @var string $resumo @var bool $filtrado @var bool $porArea @var int $brasil @var int $exterior
 */
$numero = '<b class="radar-numero">' . numero($total) . '</b>';
$tipo = $total === 1 ? '_um' : '_varios';
$texto = match (true) {
    $filtrado => t('funil.radar_combinam' . $tipo, ['n' => $numero, 'filtro' => e($resumo)]),
    $porArea => t('funil.radar_area' . $tipo, ['n' => $numero, 'filtro' => e($resumo)]),
    default => t('funil.radar_regiao' . $tipo . ($noBrasil ? '_br' : '_fora'), ['n' => $numero, 'br' => '<b>' . numero($brasil) . '</b>', 'ext' => '<b>' . numero($exterior) . '</b>']),
};
?>
<div id="radar" class="questionario-radar-alvo" aria-live="polite"
     hx-get="/comecar/radar" hx-trigger="change from:closest form delay:300ms" hx-include="closest form" hx-swap="outerHTML">
  <?php if ($total > 0): ?>
    <p class="questionario-radar">
      <span class="feed-busca-radar" aria-hidden="true"></span>
      <span><?= $texto ?></span>
    </p>
  <?php endif; ?>
</div>
