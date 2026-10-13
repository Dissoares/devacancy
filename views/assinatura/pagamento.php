<?php
/** @var \App\Entities\Pagamento $pagamento @var bool $simulacao @var ?array{url: string, campos: array<string, string>} $paypalEmail @var bool $pixManual */
?>
<section class="pagamento">
  <a class="link" href="/planos"><?= icone('chevron-esq', 'icone icone-sm') ?> <?= e(t('pagamento.trocar_plano')) ?></a>
  <h1><?= e(t(match ($pagamento->metodo) { 'paypal_email' => 'pagamento.paypal_titulo', 'asaas' => 'pagamento.asaas_titulo', default => 'pagamento.pix_titulo' })) ?></h1>
  <p class="texto-suave"><?= e(t('plano.' . $pagamento->plano)) ?> · <?= e(moeda($pagamento->valor, $pagamento->moeda)) ?></p>

  <?php if (!empty($pixManual) && $pagamento->pixCopiaCola): ?>
    <div class="cartao cartao-painel pix-manual">
      <p><?= e(t('pagamento.pix_manual_passos', ['valor' => moeda($pagamento->valor, $pagamento->moeda)])) ?></p>
      <div class="qr-pix" data-qr="<?= e($pagamento->pixCopiaCola) ?>" role="img" aria-label="<?= e(t('pagamento.qr_alt')) ?>"></div>
      <small class="texto-suave"><?= e(t('pagamento.qr_leia')) ?></small>
    </div>
    <script src="<?= asset('js/vendor/qrcode.js') ?>" defer></script>
    <script src="<?= asset('js/componentes/qr.js') ?>" defer></script>
  <?php endif; ?>

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
<?php if (!empty($pixManual)): ?>
  <div class="pix-manual-aviso" id="aviso-pagamento">
    <p class="texto-suave"><?= e(t('pagamento.pix_manual_depois')) ?></p>
    <button class="botao botao-secundario botao-grande botao-bloco" type="button"
            hx-post="/pagamento/<?= (int) $pagamento->id ?>/avisar" hx-target="#aviso-pagamento" hx-swap="innerHTML">
      <span class="carregando" aria-hidden="true"></span>
      <span class="rotulo-botao"><?= icone('check', 'icone icone-sm') ?> <?= e(t('pagamento.ja_paguei')) ?></span>
    </button>
  </div>
  <?php endif; ?>
</section>
