<?php /** @var string $assinaturaId */ ?>
<section class="pagamento">
  <h1><?= e(t('pagamento.paypal_titulo')) ?></h1>
  <?= parcial('assinatura/partials/aguardando-paypal', ['assinaturaId' => $assinaturaId]) ?>
</section>
