<?php
/**
 * Cartão "Minha assinatura" do perfil: plano, validade, forma de pagamento e o limite diário de candidaturas.
 * @var array{plano: ?string, ate: ?string, forma: ?string, valor: ?float, moeda: ?string, pago_em: ?string} $assinatura @var int $limiteDiario
 */
$ativa = $assinatura['plano'] !== null;
$jaPagou = $assinatura['forma'] !== null;
?>
<section class="cartao perfil-secao perfil-assinatura" id="assinatura">
  <h2>
    <?= e(t('perfilpg.assinatura_titulo')) ?>
    <?php if ($ativa): ?><span class="selo selo-sucesso selo-ponto"><?= e(t('perfilpg.assinatura_ativa')) ?></span><?php endif; ?>
  </h2>

  <?php if ($ativa): ?>
    <dl class="perfil-assinatura-dados">
      <div><dt><?= e(t('perfilpg.assinatura_plano')) ?></dt><dd><?= e(t('plano.' . $assinatura['plano'])) ?></dd></div>
      <div><dt><?= e(t('perfilpg.assinatura_ate')) ?></dt><dd><?= e(data_br($assinatura['ate'])) ?></dd></div>
      <?php if ($jaPagou): ?>
        <div><dt><?= e(t('perfilpg.assinatura_forma')) ?></dt><dd><?= e(t('email.forma_' . $assinatura['forma'])) ?></dd></div>
        <div><dt><?= e(t('perfilpg.assinatura_valor')) ?></dt><dd><?= e(moeda((float) $assinatura['valor'], (string) $assinatura['moeda'])) ?></dd></div>
      <?php endif; ?>
    </dl>
    <p class="texto-suave perfil-assinatura-limite"><?= icone('raio', 'icone icone-sm') ?> <?= e(t('perfilpg.assinatura_limite', ['limite' => $limiteDiario])) ?></p>
    <a class="botao botao-secundario botao-bloco" href="/planos"><?= e(t('perfilpg.assinatura_renovar')) ?></a>
  <?php else: ?>
    <p class="texto-suave"><?= e(t($jaPagou ? 'perfilpg.assinatura_venceu' : 'perfilpg.assinatura_sem')) ?></p>
    <a class="botao botao-primario botao-bloco" href="/planos"><?= e(t('perfilpg.assinatura_assinar')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
  <?php endif; ?>
</section>
