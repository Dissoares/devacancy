<?php
/** @var array $planos @var array $valores @var array $erros @var int $totalCompativeis @var ?string $vigenteAte */
?>
<section class="cabecalho-planos">
  <span class="selo selo-primario selo-ponto">Assinatura Devacancy</span>
  <h1>Você programa. <span class="texto-gradiente">A gente se candidata.</span></h1>
  <p>
    <?php if ($totalCompativeis > 0): ?>
      Já separamos <strong><?= $totalCompativeis ?> <?= $totalCompativeis === 1 ? 'vaga compatível' : 'vagas compatíveis' ?></strong> com o seu perfil.
    <?php endif; ?>
    Escolha um plano e as candidaturas começam logo depois da confirmação do PIX.
  </p>
  <?php if ($vigenteAte): ?>
    <div class="alerta alerta-sucesso"><?= icone('check') ?><span>Sua assinatura está ativa até <?= data_br($vigenteAte) ?>. Pagar agora soma o novo período ao atual.</span></div>
  <?php endif; ?>
</section>

<div class="grade-checkout">
  <?= parcial('assinatura/partials/formulario-plano', ['planos' => $planos, 'valores' => $valores, 'erros' => $erros]) ?>

  <aside class="cartao cartao-painel como-funciona">
    <h2 class="cartao-titulo">Como funciona</h2>
    <ol class="linha-tempo">
      <li class="concluido">
        <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
        <div><strong>Perfil analisado</strong><small>Stack, nível e preferências já estão com a gente.</small></div>
      </li>
      <li class="atual">
        <span class="linha-tempo-marcador"></span>
        <div><strong>Assinatura via PIX</strong><small>A confirmação sai em segundos.</small></div>
      </li>
      <li>
        <span class="linha-tempo-marcador"></span>
        <div><strong>Candidaturas todo dia útil</strong><small>Até 2 por dia, nas vagas mais compatíveis com você.</small></div>
      </li>
      <li>
        <span class="linha-tempo-marcador"></span>
        <div><strong>Você acompanha pelo painel</strong><small>Empresa, vaga e status de cada envio.</small></div>
      </li>
    </ol>
  </aside>
</div>
