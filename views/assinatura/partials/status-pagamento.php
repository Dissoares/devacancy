<?php
/** @var \App\Entities\Pagamento $pagamento @var bool $simulacao */
use App\Enums\StatusPagamento;
?>
<div class="cartao cartao-painel status-pagamento" id="status-pagamento"
  <?php if ($pagamento->estaPendente()): ?>
     hx-get="/pagamento/<?= $pagamento->id ?>/status" hx-trigger="every 5s" hx-swap="outerHTML"
  <?php endif; ?>>

  <?php if ($pagamento->estaPendente()): ?>
    <?php if ($pagamento->pixQrBase64): ?>
      <img class="qr-pix" src="data:image/png;base64,<?= e($pagamento->pixQrBase64) ?>" alt="QR code do PIX" width="220" height="220">
    <?php endif; ?>

    <?php if ($pagamento->pixCopiaCola): ?>
      <label class="campo-rotulo" for="pix-copia-cola">PIX copia e cola</label>
      <div class="copia-cola">
        <input class="entrada mono" id="pix-copia-cola" value="<?= e($pagamento->pixCopiaCola) ?>" readonly>
        <button class="botao botao-secundario" type="button" data-copiar="#pix-copia-cola"><?= icone('arquivo', 'icone icone-sm') ?> Copiar</button>
      </div>
    <?php endif; ?>

    <p class="aguardando"><span class="carregando" aria-hidden="true"></span> Aguardando a confirmação do pagamento…</p>
    <?php if ($pagamento->expiraEm): ?>
      <small class="texto-suave">O código vale até <?= data_br($pagamento->expiraEm, true) ?>.</small>
    <?php endif; ?>

    <?php if ($simulacao): ?>
      <div class="alerta alerta-aviso">
        <?= icone('ajustes') ?>
        <span>Modo simulação (ambiente local, sem chave da ExPay).
          <button class="link" type="button" hx-post="/pagamento/<?= $pagamento->id ?>/simular" hx-target="#status-pagamento" hx-swap="outerHTML">Simular pagamento aprovado</button>
        </span>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="alerta <?= $pagamento->status === StatusPagamento::Pago ? 'alerta-sucesso' : 'alerta-erro' ?>">
      <?= icone($pagamento->status === StatusPagamento::Pago ? 'check' : 'x') ?>
      <span>Pagamento <?= e(mb_strtolower($pagamento->status->rotulo())) ?>.</span>
    </div>
    <a class="botao botao-primario" href="/planos?plano=<?= e($pagamento->plano) ?>">Gerar um novo PIX</a>
  <?php endif; ?>
</div>
