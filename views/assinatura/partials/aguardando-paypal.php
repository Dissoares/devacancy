<?php /** @var string $assinaturaId */ ?>
<div class="cartao cartao-painel status-pagamento"
     hx-get="/assinatura/paypal/<?= e(rawurlencode($assinaturaId)) ?>/status" hx-trigger="every 4s" hx-swap="outerHTML">
  <p class="aguardando"><span class="carregando" aria-hidden="true"></span> <?= e(t('pagamento.paypal_aguardando')) ?></p>
  <small class="texto-suave"><?= e(t('pagamento.paypal_aguardando_texto')) ?></small>
</div>
