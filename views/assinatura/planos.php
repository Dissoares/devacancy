<?php
/**
 * @var array $planos @var string $moeda @var string[] $metodos @var array $valores @var array $erros
 * @var int $totalCompativeis @var int $reservadas @var ?string $vigenteAte
 * @var array{simulacao: bool, clienteId: string, planos: array<string, ?string>} $paypal
 * @var \App\Entities\Usuario $usuario
 */
use App\Core\Idioma;

$escolhido = $valores['plano'] ?? 'trimestral';

$resumoPlanos = [];
foreach ($planos as $chave => $plano) {
    $precoBruto = $plano['precos'][$moeda];
    $resumoPlanos[$chave] = [
        'nome' => t('plano.' . $chave),
        'preco' => moeda($precoBruto / $plano['divisor'], $moeda),
        'periodo' => t('planos.por_' . $plano['periodo']),
    ];
}
?>
<section class="cabecalho-planos">
  <?php if ($reservadas > 0): ?>
    <span class="selo selo-alerta selo-ponto"><?= e(t($reservadas === 1 ? 'planos.reservada_um' : 'planos.reservadas_varios', ['n' => $reservadas])) ?></span>
    <h1><?= t('planos.titulo_reservadas', ['destaque' => '<span class="texto-gradiente">' . e(t('planos.titulo_reservadas_destaque')) . '</span>']) ?></h1>
  <?php else: ?>
    <span class="selo selo-primario selo-ponto"><?= e(t('planos.selo')) ?></span>
    <h1><?= e(t('marca.slogan_1')) ?> <span class="texto-gradiente"><?= e(t('marca.slogan_2')) ?></span></h1>
  <?php endif; ?>
  <p>
    <?php if ($totalCompativeis > 0): ?><?= e(t('planos.compativeis', ['n' => $totalCompativeis])) ?><?php endif; ?>
    <?= e(t('planos.texto')) ?>
  </p>
  <?php if ($vigenteAte): ?>
    <div class="alerta alerta-sucesso"><?= icone('check') ?><span><?= e(t('planos.ja_ativa', ['data' => data_br($vigenteAte)])) ?></span></div>
  <?php endif; ?>
</section>

<div class="grade-checkout">
  <section class="cartao cartao-painel checkout">
    <div class="<?= classe_campo($erros, 'plano') ?>">
      <span class="campo-rotulo"><?= e(t('planos.escolha')) ?></span>
      <div class="opcoes-plano">
        <?php foreach ($planos as $chave => $plano): ?>
          <?php $preco = $plano['precos'][$moeda]; ?>
          <label class="opcao-plano<?= !empty($plano['destaque']) ? ' destaque' : '' ?>">
            <input type="radio" name="plano" value="<?= e($chave) ?>" form="formulario-pix" <?= $escolhido === $chave ? 'checked' : '' ?>>
            <span class="opcao-plano-corpo">
              <span class="opcao-plano-nome">
                <?= e(t('plano.' . $chave)) ?>
                <?php if (!empty($plano['destaque'])): ?><span class="selo selo-sucesso"><?= e(t('planos.mais_escolhido')) ?></span><?php endif; ?>
              </span>
              <span class="opcao-plano-preco">
                <strong><?= e(moeda($preco / $plano['divisor'], $moeda)) ?></strong><small>/<?= e(t('planos.por_' . $plano['periodo'])) ?></small>
              </span>
              <small class="texto-suave">
                <?= e($plano['divisor'] > 1
                    ? t('planos.cobrado_a_cada', ['valor' => moeda($preco, $moeda), 'n' => $plano['divisor']])
                    : t('planos.cobrado_' . $plano['periodo'])) ?>
              </small>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <?= erro_campo($erros, 'plano') ?>
    </div>

    <div class="resumo-plano" data-resumo-plano data-planos="<?= e(json_encode($resumoPlanos)) ?>">
      <div>
        <span class="resumo-plano-rotulo"><?= e(t('planos.resumo_total')) ?></span>
        <strong class="resumo-plano-nome" data-resumo-nome><?= e($resumoPlanos[$escolhido]['nome']) ?></strong>
      </div>
      <div class="resumo-plano-valor">
        <strong data-resumo-preco><?= e($resumoPlanos[$escolhido]['preco']) ?></strong><small>/<span data-resumo-periodo><?= e($resumoPlanos[$escolhido]['periodo']) ?></span></small>
      </div>
    </div>

    <?php if ($metodos === []): ?>
      <div class="alerta alerta-aviso"><?= icone('relogio') ?><span><?= e(t('pagamento.indisponivel')) ?></span></div>
    <?php else: ?>
      <div class="metodos" data-metodos>
        <?php if (count($metodos) > 1): ?>
          <div class="metodos-abas" role="tablist">
            <?php foreach ($metodos as $indice => $metodo): ?>
              <button type="button" role="tab" class="<?= $indice === 0 ? 'ativo' : '' ?>" data-aba="<?= $metodo ?>" aria-selected="<?= $indice === 0 ? 'true' : 'false' ?>">
                <?= e(t('planos.metodo_' . $metodo)) ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (in_array('pix', $metodos, true)): ?>
          <div class="metodo-painel" data-painel="pix" <?= $metodos[0] !== 'pix' ? 'hidden' : '' ?>>
            <?= parcial('assinatura/partials/formulario-pix', ['valores' => $valores, 'erros' => $erros]) ?>
          </div>
        <?php endif; ?>

        <?php if (in_array('paypal', $metodos, true)): ?>
          <div class="metodo-painel" data-painel="paypal" <?= $metodos[0] !== 'paypal' ? 'hidden' : '' ?>>
            <?php if ($paypal['simulacao']): ?>
              <div class="alerta alerta-aviso"><?= icone('ajustes') ?><span><?= e(t('planos.paypal_simulacao')) ?></span></div>
              <button class="botao botao-primario botao-grande botao-bloco" type="button"
                      hx-post="/assinar/paypal/simular" hx-include="[name='plano']">
                <span class="carregando" aria-hidden="true"></span>
                <span class="rotulo-botao"><?= e(t('planos.paypal_simular')) ?></span>
              </button>
            <?php else: ?>
              <div id="paypal-botoes"
                   data-paypal
                   data-cliente-id="<?= e($paypal['clienteId']) ?>"
                   data-moeda="<?= e($moeda) ?>"
                   data-usuario="<?= (int) $usuario->id ?>"
                   data-idioma="<?= Idioma::atual() === 'pt' ? 'pt_BR' : 'en_US' ?>"
                   data-planos="<?= e(json_encode($paypal['planos'])) ?>"
                   data-erro="<?= e(t('planos.paypal_erro')) ?>"></div>
            <?php endif; ?>
            <p class="nota-seguranca"><?= icone('escudo', 'icone icone-sm') ?> <?= e(t('planos.paypal_nota')) ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <aside class="cartao cartao-painel como-funciona">
    <h2 class="cartao-titulo"><?= e(t('planos.como_funciona')) ?></h2>
    <ol class="linha-tempo">
      <li class="concluido">
        <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
        <div><strong><?= e(t('planos.passo_perfil')) ?></strong><small><?= e(t('planos.passo_perfil_texto')) ?></small></div>
      </li>
      <li class="atual">
        <span class="linha-tempo-marcador"></span>
        <div><strong><?= e(t('planos.passo_assinatura')) ?></strong><small><?= e(t('planos.passo_assinatura_texto')) ?></small></div>
      </li>
      <li>
        <span class="linha-tempo-marcador"></span>
        <div><strong><?= e(t('planos.passo_candidaturas')) ?></strong><small><?= e(t('planos.passo_candidaturas_texto')) ?></small></div>
      </li>
      <li>
        <span class="linha-tempo-marcador"></span>
        <div><strong><?= e(t('planos.passo_painel')) ?></strong><small><?= e(t('planos.passo_painel_texto')) ?></small></div>
      </li>
    </ol>
  </aside>
</div>
