<?php
/** @var array $metodos @var array $pendentes @var ?string $sucesso @var ?string $pixProblema */
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Pagamentos</h1>
    <p>Liga e desliga as formas de pagamento mostradas no checkout dos assinantes.</p>
  </div>
</section>

<?php if ($pixProblema && $pixProblema !== \App\Services\PixManualService::SEM_CONFIGURACAO): ?>
  <div class="alerta alerta-erro alerta-topo" role="alert"><?= icone('x') ?><span>PIX manual: <?= e($pixProblema) ?></span></div>
<?php endif; ?>

<?php if (!empty($sucesso)): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead>
        <tr><th>Método</th><th>Região</th><th>Moedas</th><th>Configurado</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($metodos as $metodo): ?>
          <tr>
            <td><strong class="texto-celula"><?= e($metodo['nome']) ?></strong></td>
            <td><?= e($metodo['regiao']) ?></td>
            <td><?= e(implode(', ', $metodo['moedas'])) ?></td>
            <td>
              <?php if ($metodo['configurado']): ?>
                <span class="selo selo-sucesso selo-ponto">Chave no .env</span>
              <?php else: ?>
                <span class="selo selo-alerta selo-ponto">Sem chave</span>
              <?php endif; ?>
            </td>
            <td><span class="selo <?= $metodo['ativo'] ? 'selo-sucesso' : 'selo-neutro' ?>"><?= $metodo['ativo'] ? 'Ligado' : 'Desligado' ?></span></td>
            <td>
              <form method="post" action="/admin/pagamentos/<?= e($metodo['chave']) ?>/alternar">
                <?= csrf_campo() ?>
                <button class="botao botao-fantasma botao-pequeno" type="submit"><?= $metodo['ativo'] ? 'Desligar' : 'Ligar' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="texto-suave nota-rodape-tabela">
    Mesmo ligado, um método só aparece pra quem tem a chave configurada no <code>.env</code> (ou em modo simulação local).
    PIX só aparece pra quem está no Brasil; PayPal aparece pros dois.
  </p>
</section>

<?php if ($pendentes): ?>
  <section class="cartao tabela-cartao">
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="texto-suave" style="text-align:left;padding:14px 16px">
          <strong>Pagamentos aguardando confirmação.</strong>
          Confira no app do banco (PIX) ou do PayPal se o dinheiro chegou e confirme para liberar o plano. No PayPal só com e-mail, o aviso costuma chegar sozinho.
        </caption>
        <thead><tr><th>Cliente</th><th>Forma</th><th>Plano</th><th>Valor</th><th>Pedido em</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pendentes as $pagamento): ?>
            <tr>
              <td><strong class="texto-celula"><?= e($pagamento['nome']) ?></strong><small><?= e($pagamento['email']) ?></small></td>
              <td><span class="selo selo-neutro"><?= $pagamento['metodo'] === 'pix_manual' ? 'PIX' : 'PayPal' ?></span></td>
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
  </section>
<?php endif; ?>
