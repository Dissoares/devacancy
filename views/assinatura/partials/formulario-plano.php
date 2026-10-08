<?php
/** @var array $planos @var array $valores @var array<string, string> $erros */
$moeda = fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
$escolhido = $valores['plano'] ?? 'trimestral';
?>
<form class="cartao cartao-painel formulario-plano" method="post" action="/assinar"
      hx-post="/assinar" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>

  <?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erros['geral']) ?></span></div>
  <?php endif; ?>

  <div class="<?= classe_campo($erros, 'plano') ?>">
    <span class="campo-rotulo">Escolha o plano</span>
    <div class="opcoes-plano">
      <?php foreach ($planos as $chave => $plano): ?>
        <label class="opcao-plano<?= !empty($plano['destaque']) ? ' destaque' : '' ?>">
          <input type="radio" name="plano" value="<?= e($chave) ?>" <?= $escolhido === $chave ? 'checked' : '' ?>>
          <span class="opcao-plano-corpo">
            <span class="opcao-plano-nome">
              <?= e($plano['nome']) ?>
              <?php if (!empty($plano['destaque'])): ?><span class="selo selo-sucesso">Mais escolhido</span><?php endif; ?>
            </span>
            <span class="opcao-plano-preco">
              <strong><?= $moeda($plano['valor'] / $plano['meses']) ?></strong><small>/mês</small>
            </span>
            <small class="texto-suave">
              <?= $plano['meses'] > 1 ? $moeda($plano['valor']) . ' a cada ' . $plano['meses'] . ' meses' : 'Cobrado mensalmente' ?>
            </small>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'plano') ?>
  </div>

  <div class="<?= classe_campo($erros, 'cpf') ?>">
    <label class="campo-rotulo" for="cpf">CPF do pagador</label>
    <input class="entrada" id="cpf" name="cpf" inputmode="numeric" autocomplete="off" maxlength="14"
           placeholder="000.000.000-00" value="<?= e($valores['cpf'] ?? '') ?>" data-mascara-cpf>
    <?= erro_campo($erros, 'cpf') ?>
  </div>

  <button class="botao botao-primario botao-grande botao-bloco" type="submit">
    <span class="carregando" aria-hidden="true"></span>
    <span class="rotulo-botao">Gerar PIX e assinar</span>
    <?= icone('seta', 'icone icone-seta') ?>
  </button>

  <p class="nota-seguranca"><?= icone('escudo', 'icone icone-sm') ?> Pagamento processado pela ExPay. Sem fidelidade: você só renova se quiser.</p>
</form>
