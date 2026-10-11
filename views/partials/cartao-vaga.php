<?php
/**
 * @var \App\Entities\Vaga $vaga @var int $compatibilidade
 * @var bool $bloqueada Sem assinatura, empresa e link nem chegam ao HTML.
 * @var bool $comAcao Mostra o botão "Candidatar-me" (painel) ou o status da candidatura.
 * @var ?\App\Enums\StatusCandidatura $status
 */
$bloqueada ??= true;
$comAcao ??= false;
$status ??= null;
$recemEnviada ??= false;
$proxima ??= false; // vaga da "reserva": próxima do perfil, não estritamente compatível
?>
<article class="cartao-vaga<?= $bloqueada ? ' bloqueada' : '' ?><?= $recemEnviada ? ' recem-enviada' : '' ?>">
  <div class="cartao-vaga-topo">
    <?php if ($proxima): ?>
      <span class="compatibilidade compatibilidade-proxima"><?= e(t('vaga.proxima_do_perfil')) ?></span>
    <?php else: ?>
      <span class="compatibilidade"><?= e(t('vaga.compatibilidade', ['n' => (int) $compatibilidade])) ?></span>
    <?php endif; ?>
    <span class="cartao-vaga-direita">
      <?= icones_stack($vaga->stack) ?>
      <?php if (strtotime($vaga->criadoEm) >= time() - 86400): ?>
        <span class="selo selo-primario"><?= e(t('vaga.nova')) ?></span>
      <?php endif; ?>
    </span>
  </div>

  <h3><?= e($vaga->titulo) ?></h3>

  <p class="cartao-vaga-empresa">
    <?= icone('predio', 'icone icone-sm') ?>
    <?php if ($bloqueada): ?>
      <span class="texto-borrado" aria-label="<?= e(t('vaga.empresa_assinantes')) ?>"><?= e(t('vaga.empresa_confidencial')) ?></span>
      <?= icone('cadeado', 'icone icone-sm') ?>
    <?php else: ?>
      <span><?= e($vaga->empresa) ?></span>
    <?php endif; ?>
  </p>

  <ul class="cartao-vaga-dados">
    <li><?= icone('local', 'icone icone-sm') ?> <?= e($vaga->local()) ?></li>
    <li><?= icone('maleta', 'icone icone-sm') ?> <?= e($vaga->senioridade->rotulo()) ?></li>
    <?php if ($faixa = $vaga->faixaSalarial()): ?>
      <li><?= icone('grafico', 'icone icone-sm') ?> <?= e($faixa) ?></li>
    <?php endif; ?>
  </ul>

  <div class="lista-etiquetas">
    <?php foreach (array_slice($vaga->stack, 0, 5) as $tecnologia): ?><span class="etiqueta"><?= icone_stack($tecnologia) ?><?= e($tecnologia) ?></span><?php endforeach; ?>
  </div>

  <?php if ($comAcao): ?>
    <div class="cartao-vaga-acao">
      <?php if ($status !== null): ?>
        <span class="selo <?= $status->classeSelo() ?> selo-ponto"><?= e($status->rotulo()) ?></span>
        <?php if ($recemEnviada): ?><small class="cartao-vaga-aviso"><?= icone('check', 'icone icone-sm') ?> <?= e(t('candidatura.com_a_equipe')) ?></small><?php endif; ?>
        <?php if ($bloqueada): ?><a class="link" href="/planos"><?= e(t('vaga.ativar_para_enviar')) ?></a><?php endif; ?>
      <?php else: ?>
        <form method="post" action="/candidaturas" hx-post="/candidaturas" hx-target="closest .cartao-vaga" hx-swap="outerHTML">
          <?= csrf_campo() ?>
          <input type="hidden" name="vaga_id" value="<?= $vaga->id ?>">
          <button class="botao botao-acao-suave botao-bloco" type="submit">
            <span class="carregando" aria-hidden="true"></span>
            <span class="rotulo-botao"><?= icone('raio', 'icone icone-sm') ?> <?= e(t('vaga.candidatar')) ?></span>
          </button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</article>
