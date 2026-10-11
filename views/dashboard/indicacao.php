<?php
/** @var string $link @var array{itens: array, totais: array<string, array{pendente: float, paga: float}>} $indicacoes */
use App\Enums\StatusComissao;
?>
<section class="cartao cartao-painel secao-indicacao">
  <div class="bloco-titulo">
    <span><?= icone('usuarios', 'icone icone-sm') ?></span>
    <div>
      <h2 class="cartao-titulo"><?= e(t('painel.indicacao_titulo')) ?></h2>
      <p><?= e(t('painel.indicacao_texto')) ?></p>
    </div>
  </div>

  <label class="campo-rotulo" for="link-indicacao"><?= e(t('painel.indicacao_seu_link')) ?></label>
  <div class="copia-cola">
    <input class="entrada mono" id="link-indicacao" value="<?= e($link) ?>" readonly>
    <button class="botao botao-secundario" type="button" data-copiar="#link-indicacao" data-mensagem="<?= e(t('painel.indicacao_copiado')) ?>">
      <?= icone('arquivo', 'icone icone-sm') ?> <?= e(t('painel.copiar')) ?>
    </button>
  </div>

  <?php if ($indicacoes['itens']): ?>
    <div class="indicadores indicadores-indicacao">
      <?php foreach ($indicacoes['totais'] as $codigoMoeda => $valores): ?>
        <div class="indicador">
          <span class="indicador-rotulo"><?= e(t('painel.indicacao_pendente')) ?> (<?= e($codigoMoeda) ?>)</span>
          <strong class="indicador-valor"><?= moeda($valores['pendente'], $codigoMoeda) ?></strong>
        </div>
        <div class="indicador">
          <span class="indicador-rotulo"><?= e(t('painel.indicacao_recebida')) ?> (<?= e($codigoMoeda) ?>)</span>
          <strong class="indicador-valor"><?= moeda($valores['paga'], $codigoMoeda) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>

    <ul class="lista-indicacoes">
      <?php foreach ($indicacoes['itens'] as $item): ?>
        <?php $paga = $item['status'] === StatusComissao::Paga->value; ?>
        <li>
          <span><?= e($item['indicado_nome']) ?></span>
          <span class="selo <?= $paga ? 'selo-sucesso' : 'selo-alerta' ?> selo-ponto">
            <?= e(t($paga ? 'painel.indicacao_paga' : 'painel.indicacao_aguardando')) ?>
          </span>
          <strong><?= moeda((float) $item['valor'], $item['moeda']) ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="texto-vazio"><?= e(t('painel.indicacao_vazio')) ?></p>
  <?php endif; ?>
</section>
