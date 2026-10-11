<?php
/** @var array $comissoes @var ?string $sucesso */
use App\Enums\StatusComissao;

$pendentes = array_filter($comissoes, fn ($linha) => $linha['status'] === StatusComissao::Pendente->value);
$totalPendentePorMoeda = [];
foreach ($pendentes as $linha) {
    $totalPendentePorMoeda[$linha['moeda']] = ($totalPendentePorMoeda[$linha['moeda']] ?? 0) + (float) $linha['valor'];
}
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Comissões</h1>
    <p>40% do primeiro pagamento de cada indicado, pago uma única vez.</p>
  </div>
</section>

<?php if ($sucesso): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<section class="indicadores">
  <div class="indicador cartao">
    <span class="indicador-icone"><?= icone('relogio', 'icone icone-sm') ?></span>
    <span class="indicador-rotulo">Comissões pendentes</span>
    <strong class="indicador-valor"><?= count($pendentes) ?></strong>
  </div>
  <?php foreach ($totalPendentePorMoeda as $codigoMoeda => $total): ?>
    <div class="indicador cartao">
      <span class="indicador-icone"><?= icone('grafico', 'icone icone-sm') ?></span>
      <span class="indicador-rotulo">A pagar (<?= e($codigoMoeda) ?>)</span>
      <strong class="indicador-valor"><?= moeda($total, $codigoMoeda) ?></strong>
    </div>
  <?php endforeach; ?>
</section>

<section class="tabela-cartao cartao">
  <?php if ($comissoes === []): ?>
    <div class="estado-vazio">
      <span class="estado-vazio-icone"><?= icone('caixa', 'icone icone-lg') ?></span>
      <h2>Nenhuma comissão ainda</h2>
      <p>Aparece aqui assim que um indicado fizer o primeiro pagamento.</p>
    </div>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr><th>Indicador</th><th>Indicado</th><th>Valor</th><th>Status</th><th>Data</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($comissoes as $linha): ?>
            <?php $paga = $linha['status'] === StatusComissao::Paga->value; ?>
            <tr>
              <td><strong class="texto-celula"><?= e($linha['indicador_nome']) ?></strong><small><?= e($linha['indicador_email']) ?></small></td>
              <td><strong class="texto-celula"><?= e($linha['indicado_nome']) ?></strong><small><?= e($linha['indicado_email']) ?></small></td>
              <td><?= moeda((float) $linha['valor'], $linha['moeda']) ?></td>
              <td><span class="selo <?= $paga ? 'selo-sucesso' : 'selo-alerta' ?> selo-ponto"><?= e(StatusComissao::from($linha['status'])->rotulo()) ?></span></td>
              <td class="texto-suave"><?= data_br($linha['criado_em']) ?></td>
              <td>
                <?php if (!$paga): ?>
                  <form method="post" action="/admin/comissoes/<?= (int) $linha['id'] ?>/pagar">
                    <?= csrf_campo() ?>
                    <button class="botao botao-fantasma botao-pequeno" type="submit">Marcar como paga</button>
                  </form>
                <?php else: ?>
                  <span class="texto-suave">Paga em <?= data_br($linha['pago_em']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
