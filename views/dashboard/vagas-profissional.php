<?php
/** @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes @var ?string $assinaturaAte */
$total = count($compativeis);
?>
<?php if (!$assinaturaAte): ?>
  <section class="cartao banner-assinatura">
    <div>
      <h2>
        <?= $total > 0
            ? '<span class="texto-gradiente">' . $total . ' ' . ($total === 1 ? 'vaga compatível' : 'vagas compatíveis') . '</span> esperando por você'
            : 'Seu radar de vagas está <span class="texto-gradiente">ligado</span>' ?>
      </h2>
      <p>
        <?php if ($recentes > 0): ?><strong>+<?= $recentes ?> nas últimas 24 horas.</strong><?php endif; ?>
        Assine e a gente se candidata por você todos os dias úteis.
      </p>
    </div>
    <a class="botao botao-primario botao-grande" href="/planos"><?= icone('raio', 'icone icone-sm') ?> Assinar agora</a>
  </section>
<?php endif; ?>

<?php if ($total > 0): ?>
  <section class="secao-vagas">
    <div class="secao-vagas-cabecalho">
      <h2 class="cartao-titulo"><?= $assinaturaAte ? 'Suas vagas na fila' : 'Vagas compatíveis com você' ?></h2>
      <span class="texto-suave"><?= $total ?> no total</span>
    </div>
    <div class="grade-vagas">
      <?php foreach (array_slice($compativeis, 0, $assinaturaAte ? 30 : 6) as $item): ?>
        <?= parcial('partials/cartao-vaga', ['vaga' => $item['vaga'], 'compatibilidade' => $item['compatibilidade'], 'bloqueada' => !$assinaturaAte]) ?>
      <?php endforeach; ?>
    </div>
    <?php if (!$assinaturaAte && $total > 6): ?>
      <a class="mais-vagas" href="/planos"><?= icone('cadeado', 'icone icone-sm') ?> Mais <?= $total - 6 ?> vagas para assinantes</a>
    <?php endif; ?>
  </section>
<?php else: ?>
  <section class="cartao cartao-painel estado-vazio">
    <span class="estado-vazio-icone"><?= icone('caixa', 'icone icone-lg') ?></span>
    <h2>Suas candidaturas aparecerão aqui</h2>
    <p>Assim que entrar uma vaga compatível com o seu perfil, você vai ver empresa, vaga, data e status de cada envio.</p>
  </section>
<?php endif; ?>
