<?php
/** @var array $pendentes @var array $ultimos @var ?string $sucesso @var ?string $pixProblema */
$formas = ['pix_manual' => 'PIX manual', 'paypal_email' => 'PayPal (e-mail)', 'asaas' => 'Asaas', 'pix' => 'PIX (ExPay)', 'paypal' => 'PayPal', 'stripe' => 'Cartão (Stripe)'];
$classes = ['pago' => 'selo-sucesso', 'pendente' => 'selo-alerta'];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Pagamentos</h1>
    <p>O que está esperando a sua confirmação e o histórico recente. Para ligar ou desligar uma forma de pagamento, use <a class="link" href="/admin/configuracoes/pagamentos">Configurações → Formas de pagamento</a>.</p>
  </div>
</section>

<?php if ($pixProblema && $pixProblema !== \App\Services\PixManualService::SEM_CONFIGURACAO): ?>
  <div class="alerta alerta-erro alerta-topo" role="alert"><?= icone('x') ?><span>PIX manual: <?= e($pixProblema) ?></span></div>
<?php endif; ?>

<?php if (!empty($sucesso)): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<section class="admin-secao">
  <h2>Aguardando confirmação <?= $pendentes ? '<span class="selo selo-alerta">' . count($pendentes) . '</span>' : '' ?></h2>
  <p class="texto-suave">PIX manual e PayPal só com e-mail: confira no app do banco ou do PayPal se o dinheiro chegou e confirme para liberar o plano. No PayPal o aviso costuma chegar sozinho.</p>

  <?php if ($pendentes): ?>
    <div class="cartao tabela-cartao">
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Cliente</th><th>Forma</th><th>Plano</th><th>Valor</th><th>Pedido em</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($pendentes as $pagamento): ?>
              <tr>
                <td><strong class="texto-celula"><?= e($pagamento['nome']) ?></strong><small><?= e($pagamento['email']) ?></small></td>
                <td><span class="selo selo-neutro"><?= e($formas[$pagamento['metodo']] ?? $pagamento['metodo']) ?></span></td>
                <td><?= e(t('plano.' . $pagamento['plano'])) ?></td>
                <td><?= e(moeda((float) $pagamento['valor'], $pagamento['moeda'])) ?></td>
                <td class="texto-suave"><?= data_br($pagamento['criado_em'], true) ?></td>
                <td>
                  <form method="post" action="/admin/pagamentos/<?= (int) $pagamento['id'] ?>/confirmar"
                        onsubmit="return confirm('Confirme que o valor já aparece na sua conta. Liberar o plano agora?')">
                    <?= csrf_campo() ?>
                    <button class="botao botao-primario botao-pequeno" type="submit">Confirmar pagamento</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="cartao estado-vazio">
      <span class="estado-vazio-icone"><?= icone('check', 'icone icone-lg') ?></span>
      <h2>Nada esperando por você</h2>
      <p>Quando alguém pagar por PIX manual ou PayPal só com e-mail, aparece aqui.</p>
    </div>
  <?php endif; ?>
</section>

<section class="admin-secao">
  <h2>Últimos pagamentos</h2>

  <?php if ($ultimos): ?>
    <div class="cartao tabela-cartao">
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Cliente</th><th>Forma</th><th>Plano</th><th>Valor</th><th>Situação</th><th>Data</th></tr></thead>
          <tbody>
            <?php foreach ($ultimos as $pagamento): ?>
              <tr>
                <td><strong class="texto-celula"><?= e($pagamento['nome']) ?></strong><small><?= e($pagamento['email']) ?></small></td>
                <td><span class="selo selo-neutro"><?= e($formas[$pagamento['metodo']] ?? $pagamento['metodo']) ?></span></td>
                <td><?= e(t('plano.' . $pagamento['plano'])) ?></td>
                <td><?= e(moeda((float) $pagamento['valor'], $pagamento['moeda'])) ?></td>
                <td><span class="selo <?= $classes[$pagamento['status']] ?? 'selo-neutro' ?>"><?= e(\App\Enums\StatusPagamento::from($pagamento['status'])->rotulo()) ?></span></td>
                <td class="texto-suave"><?= data_br($pagamento['pago_em'] ?? $pagamento['criado_em'], true) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="cartao estado-vazio">
      <span class="estado-vazio-icone"><?= icone('caixa', 'icone icone-lg') ?></span>
      <h2>Nenhum pagamento ainda</h2>
      <p>Os pagamentos de todas as formas aparecem aqui, do mais recente para o mais antigo.</p>
    </div>
  <?php endif; ?>
</section>
