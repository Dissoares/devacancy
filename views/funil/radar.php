<?php
/**
 * Radar do questionário. O próprio elemento se recarrega (HTMX) a cada resposta, com o formulário inteiro na consulta.
 * Mostra sempre onde a pessoa está e quantas vagas há; com zero vagas, avisa que o perfil entra no radar.
 * @var int $total @var bool $noBrasil @var string $resumo @var bool $filtrado @var bool $porArea @var int $brasil @var int $exterior @var ?string $local
 */
$numero = '<b class="radar-numero">' . numero($total) . '</b>';
$tipo = $total === 1 ? '_um' : '_varios';
$texto = match (true) {
    $total === 0 => t('funil.radar_zero', ['filtro' => e($resumo !== '' ? $resumo : ($local ?? ''))]),
    $filtrado => t('funil.radar_combinam' . $tipo, ['n' => $numero, 'filtro' => e($resumo)]),
    $porArea => t('funil.radar_area' . $tipo, ['n' => $numero, 'filtro' => e($resumo)]),
    default => t('funil.radar_regiao' . $tipo . ($noBrasil ? '_br' : '_fora'), ['n' => $numero, 'br' => '<b>' . numero($brasil) . '</b>', 'ext' => '<b>' . numero($exterior) . '</b>']),
};
?>
<div id="radar" class="questionario-radar-alvo" aria-live="polite"
     hx-get="/comecar/radar" hx-trigger="change from:closest form delay:300ms" hx-include="closest form" hx-swap="outerHTML">
    <p class="questionario-radar">
      <span class="feed-busca-radar" aria-hidden="true"></span>
      <span class="radar-texto">
        <?php if ($local): ?><small class="radar-local"><?= icone('local', 'icone icone-sm') ?> <?= e(t('funil.radar_voce_esta', ['local' => $local])) ?></small><?php endif; ?>
        <span><?= $texto ?></span>
      </span>
    </p>
</div>
