<?php
/**
 * @var \App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional $perfil
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes
 */
$total = count($compativeis);
?>
<section class="analise" data-analise>
  <span class="analise-icone"><?= icone('brilho', 'icone icone-lg') ?></span>
  <h1>Analisando seu perfil…</h1>
  <ol class="analise-etapas">
    <li><span class="analise-marcador"></span>Lendo sua stack: <?= e(implode(', ', array_slice($perfil->stack, 0, 4))) ?></li>
    <li><span class="analise-marcador"></span>Cruzando com as vagas abertas para <?= e($perfil->senioridade->rotulo()) ?> · <?= e($perfil->area->rotulo()) ?></li>
    <li><span class="analise-marcador"></span>Calculando a compatibilidade de cada vaga</li>
  </ol>
</section>

<div class="resultado-match" data-resultado>
  <section class="match-destaque">
    <?php if ($total > 0): ?>
      <span class="selo selo-sucesso selo-ponto">Análise concluída</span>
      <h1>
        <span class="texto-gradiente"><?= $total ?> <?= $total === 1 ? 'vaga compatível' : 'vagas compatíveis' ?></span>
        com o seu perfil, <?= e($usuario->primeiroNome()) ?>.
      </h1>
      <p>
        <?= e($perfil->cargoDesejado) ?> · <?= e($perfil->modeloTrabalho->rotulo()) ?>.
        <?php if ($recentes > 0): ?><strong><?= $recentes ?> <?= $recentes === 1 ? 'entrou' : 'entraram' ?> nas últimas 24 horas.</strong><?php endif; ?>
        Assine e a gente se candidata por você. Você não precisa fazer mais nada.
      </p>
    <?php else: ?>
      <span class="selo selo-primario selo-ponto">Perfil analisado</span>
      <h1>Seu radar de vagas está <span class="texto-gradiente">ligado</span>, <?= e($usuario->primeiroNome()) ?>.</h1>
      <p>Novas vagas para <?= e($perfil->cargoDesejado) ?> entram todos os dias. Assine para que a gente se candidate assim que elas aparecerem.</p>
    <?php endif; ?>

    <div class="match-acoes">
      <a class="botao botao-primario botao-grande" href="/planos">
        <?= icone('raio', 'icone icone-sm') ?> Assinar e começar as candidaturas
      </a>
      <a class="botao botao-fantasma" href="/painel">Agora não</a>
    </div>

    <ul class="match-garantias">
      <li><?= icone('check', 'icone icone-sm') ?> Até 2 candidaturas por dia útil</li>
      <li><?= icone('check', 'icone icone-sm') ?> Acompanhamento no painel</li>
      <li><?= icone('check', 'icone icone-sm') ?> Pagamento via PIX, sem fidelidade</li>
    </ul>
  </section>

  <?php if ($total > 0): ?>
    <section class="grade-vagas">
      <?php foreach (array_slice($compativeis, 0, 6) as $item): ?>
        <?= parcial('partials/cartao-vaga', ['vaga' => $item['vaga'], 'compatibilidade' => $item['compatibilidade'], 'bloqueada' => true]) ?>
      <?php endforeach; ?>
    </section>

    <?php if ($total > 6): ?>
      <a class="mais-vagas" href="/planos"><?= icone('cadeado', 'icone icone-sm') ?> Mais <?= $total - 6 ?> vagas para assinantes</a>
    <?php endif; ?>
  <?php endif; ?>
</div>
