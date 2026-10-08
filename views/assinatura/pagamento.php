<?php
/** @var \App\Entities\Pagamento $pagamento @var ?array $plano @var bool $simulacao */
?>
<section class="pagamento">
  <a class="link" href="/planos"><?= icone('chevron-esq', 'icone icone-sm') ?> Trocar plano</a>
  <h1>Pague com PIX para ativar</h1>
  <p class="texto-suave">
    Plano <?= e($plano['nome'] ?? $pagamento->plano) ?> · R$ <?= number_format($pagamento->valor, 2, ',', '.') ?>
  </p>

  <?= parcial('assinatura/partials/status-pagamento', ['pagamento' => $pagamento, 'simulacao' => $simulacao]) ?>
</section>
