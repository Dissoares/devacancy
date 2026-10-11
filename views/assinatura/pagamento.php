<?php
/** @var \App\Entities\Pagamento $pagamento @var bool $simulacao @var ?array{url: string, campos: array<string, string>} $paypalEmail */
?>
<section class="pagamento">
  <a class="link" href="/planos"><?= icone('chevron-esq', 'icone icone-sm') ?> <?= e(t('pagamento.trocar_plano')) ?></a>
  <h1><?= e(t($pagamento->metodo === 'paypal_email' ? 'pagamento.paypal_titulo' : 'pagamento.pix_titulo')) ?></h1>
  <p class="texto-suave"><?= e(t('plano.' . $pagamento->plano)) ?> · <?= e(moeda($pagamento->valor, $pagamento->moeda)) ?></p>

  <?php if ($paypalEmail): ?>
    <div class="cartao cartao-painel">
      <form method="post" action="<?= e($paypalEmail['url']) ?>">
        <?php foreach ($paypalEmail['campos'] as $nome => $valor): ?>
          <input type="hidden" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
        <?php endforeach; ?>
        <button class="botao botao-primario botao-grande botao-bloco" type="submit">
          <span class="rotulo-botao"><?= e(t('pagamento.paypal_pagar', ['valor' => moeda($pagamento->valor, $pagamento->moeda)])) ?></span>
          <?= icone('externo', 'icone icone-sm') ?>
        </button>
      </form>
      <p class="texto-suave"><?= e(t('pagamento.paypal_depois')) ?></p>
    </div>
  <?php endif; ?>

  <?= parcial('assinatura/partials/status-pagamento', ['pagamento' => $pagamento, 'simulacao' => $simulacao]) ?>
</section>
