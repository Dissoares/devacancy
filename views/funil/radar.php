<?php
/**
 * Radar do questionário. O próprio elemento se recarrega (HTMX) a cada resposta, com o formulário inteiro na consulta.
 * Sem vaga ainda, fica vazio (não mostra "0 vagas").
 * @var int $total
 */
?>
<div id="radar" class="questionario-radar-alvo" aria-live="polite"
     hx-get="/comecar/radar" hx-trigger="change from:closest form delay:300ms" hx-include="closest form" hx-swap="outerHTML">
  <?php if ($total > 0): ?>
    <p class="questionario-radar">
      <span class="feed-busca-radar" aria-hidden="true"></span>
      <span><?= t($total === 1 ? 'funil.radar_um' : 'funil.radar_varios', ['n' => '<b class="radar-numero">' . numero($total) . '</b>']) ?></span>
    </p>
  <?php endif; ?>
</div>
