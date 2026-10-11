<?php
/** @var \App\Entities\Pagamento $pagamento @var bool $simulacao */
?>
<section class="pagamento">
  <a class="link" href="/planos"><?= icone('chevron-esq', 'icone icone-sm') ?> <?= e(t('pagamento.trocar_plano')) ?></a>
  <h1><?= e(t('pagamento.pix_titulo')) ?></h1>
  <p class="texto-suave"><?= e(t('plano.' . $pagamento->plano)) ?> · <?= e(moeda($pagamento->valor, $pagamento->moeda)) ?></p>

  <?= parcial('assinatura/partials/status-pagamento', ['pagamento' => $pagamento, 'simulacao' => $simulacao]) ?>
</section>
