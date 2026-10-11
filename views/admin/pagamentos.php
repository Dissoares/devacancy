<?php
/** @var array $metodos */
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Pagamentos</h1>
    <p>Liga e desliga as formas de pagamento mostradas no checkout dos assinantes.</p>
  </div>
</section>

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
