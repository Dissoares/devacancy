<?php
/**
 * Página aberta de uma vaga (destino dos links compartilhados). A empresa e o link original ficam para assinantes.
 * @var \App\Entities\Vaga $vaga @var ?\App\Entities\Usuario $usuario
 */
use App\Enums\ModeloTrabalho;

$descricao = $vaga->descricao ?: t('funil.descricao_gerada', [
    'nivel' => mb_strtolower($vaga->senioridade->rotulo()),
    'area' => $vaga->area->rotulo(),
    'stack' => implode(', ', array_slice($vaga->stack, 0, 3)) ?: $vaga->area->rotulo(),
]);
// A empresa fica oculta também dentro do texto da descrição.
if ($vaga->empresa !== 'Confidencial' && mb_strlen($vaga->empresa) >= 3) {
    $descricao = preg_replace('/' . preg_quote($vaga->empresa, '/') . '/iu', '▒▒▒▒▒', $descricao);
}
$recente = strtotime($vaga->criadoEm) >= time() - 86400 * 2;
?>
<section class="vaga-publica">
  <article class="cartao vaga-publica-cartao">
    <header class="vaga-publica-topo">
      <span class="selo selo-sucesso selo-ponto"><?= e(t('vaga_publica.aberta')) ?></span>
      <?php if ($recente): ?><span class="selo selo-primario"><?= e(t('vaga.nova')) ?></span><?php endif; ?>
      <span class="vaga-publica-stack-icones"><?= icones_stack($vaga->stack) ?></span>
    </header>

    <h1><?= e($vaga->titulo) ?></h1>

    <p class="vaga-publica-empresa">
      <?= icone('predio', 'icone icone-sm') ?>
      <span class="empresa-censurada" style="--largura: <?= 5 + mb_strlen($vaga->titulo) % 5 ?>em" aria-label="<?= e(t('vaga.empresa_confidencial')) ?>"></span>
      <?= icone('cadeado', 'icone icone-sm') ?>
      <small><?= e(t('vaga_publica.empresa_nota')) ?></small>
    </p>

    <ul class="vaga-publica-dados">
      <li><?= icone('local', 'icone icone-sm') ?> <?= e($vaga->local()) ?></li>
      <li><?= icone('maleta', 'icone icone-sm') ?> <?= e($vaga->senioridade->rotulo()) ?></li>
      <li><?= icone('camadas', 'icone icone-sm') ?> <?= e($vaga->area->rotulo()) ?></li>
      <?php if ($faixa = $vaga->faixaSalarial()): ?><li class="vaga-encontrada-salario"><?= icone('grafico', 'icone icone-sm') ?> <?= e($faixa) ?></li><?php endif; ?>
    </ul>

    <?php if ($vaga->stack): ?>
      <div class="lista-etiquetas">
        <?php foreach ($vaga->stack as $tecnologia): ?><span class="etiqueta"><?= icone_stack($tecnologia) ?><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="vaga-publica-descricao"><?= nl2br(e($descricao)) ?></p>

    <div class="vaga-publica-acoes">
      <a class="botao botao-primario botao-grande" href="/vaga/<?= (int) $vaga->id ?>/aplicar">
        <?= icone('raio', 'icone icone-sm') ?> <?= e(t($usuario ? 'vaga_publica.aplicar_logado' : 'vaga_publica.aplicar')) ?> <?= icone('seta', 'icone icone-seta') ?>
      </a>
      <?= parcial('partials/botao-compartilhar', ['vaga' => $vaga, 'classe' => 'botao-secundario botao-grande']) ?>
    </div>

    <ul class="busca-garantias">
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('funil.garantia_gratis')) ?></li>
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('funil.garantia_candidaturas')) ?></li>
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.confianca_2')) ?></li>
    </ul>
  </article>
</section>
