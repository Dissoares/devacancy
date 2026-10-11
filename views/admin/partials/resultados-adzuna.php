<?php
/** @var array $linhas @var string $termo @var string $nivel @var int $pagina @var ?string $erro */
$paginador = fn (int $p): string => '/admin/vagas/adzuna?termo=' . urlencode($termo) . '&nivel=' . urlencode($nivel) . '&pagina=' . $p;
?>
<?php if ($erro): ?>
  <div class="alerta alerta-erro"><?= icone('x') ?><span><?= e($erro) ?></span></div>
<?php elseif ($termo === ''): ?>
  <p class="texto-vazio">Digite uma tecnologia ou cargo pra ver vagas reais da Adzuna.</p>
<?php elseif ($linhas === []): ?>
  <p class="texto-vazio">Nenhuma vaga encontrada para "<?= e($termo) ?>" nessa página.</p>
<?php else: ?>
  <div class="grade-resultados-adzuna">
    <?php foreach ($linhas as $linha): ?>
      <?= parcial('admin/partials/linha-adzuna', $linha) ?>
    <?php endforeach; ?>
  </div>
  <div class="paginacao">
    <?php if ($pagina > 1): ?>
      <a class="botao botao-secundario botao-pequeno" href="<?= e($paginador($pagina - 1)) ?>" hx-get="<?= e($paginador($pagina - 1)) ?>" hx-target="#resultados-adzuna" hx-push-url="true">
        <?= icone('chevron-esq', 'icone icone-sm') ?> Anterior
      </a>
    <?php endif; ?>
    <a class="botao botao-secundario botao-pequeno" href="<?= e($paginador($pagina + 1)) ?>" hx-get="<?= e($paginador($pagina + 1)) ?>" hx-target="#resultados-adzuna" hx-push-url="true">
      Próxima <?= icone('chevron-dir', 'icone icone-sm') ?>
    </a>
  </div>
<?php endif; ?>
