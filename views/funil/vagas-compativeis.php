<?php
/**
 * Busca animada (o JS revela os cartões um a um) e, no fim, o convite para aplicar às vagas.
 * Sem JS, tudo aparece de uma vez.
 * @var ?\App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional $perfil
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes
 * @var array{analisadas: int, porTecnologia: array<string, int>, naArea: int, salarioMedio: ?int, salarioMaximo: ?int, moeda: string, percentualRemoto: int} $panorama
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int, stack: string[]}> $feed
 * @var ?string $local @var string $destino
 */
use App\Enums\ModeloTrabalho;

$total = count($compativeis);
$lugar = $local ?? t('funil.sua_regiao');
$plural = fn (int $n, string $chave): string => t($chave . ($n === 1 ? '_um' : '_varios'), ['n' => numero($n)]);
$stackPerfil = array_map(fn (string $tecnologia) => mb_strtolower($tecnologia), $perfil->stack);
$restantes = $total - count($feed);
$textoBotao = $usuario
    ? t('funil.ver_no_painel')
    : ($total > 0 ? t($total === 1 ? 'funil.aplicar_uma' : 'funil.aplicar_varias', ['n' => $total]) : t('funil.ativar_alertas'));
?>
<section class="busca" data-busca>
  <header class="busca-topo">
    <span class="busca-icone" aria-hidden="true"><?= icone('busca', 'icone icone-lg') ?></span>
    <h1><?= e(t('funil.analise_titulo')) ?></h1>
    <ul class="busca-perfil">
      <li><?= e($perfil->area->rotulo()) ?></li>
      <li><?= e($perfil->senioridade->rotulo()) ?></li>
      <li><?= icone('local', 'icone icone-sm') ?> <?= e($lugar) ?></li>
      <?php foreach (array_slice($perfil->stack, 0, 3) as $tecnologia): ?>
        <li class="busca-perfil-stack"><?= e($tecnologia) ?></li>
      <?php endforeach; ?>
    </ul>
  </header>

  <div class="busca-status">
    <div class="busca-status-linha">
      <span class="feed-busca-radar" aria-hidden="true"></span>
      <strong data-busca-status
              data-mensagens="<?= e(json_encode([t('funil.status_perfil'), t('funil.status_cruzando', ['n' => numero($panorama['analisadas'])]), t('funil.status_compatibilidade')], JSON_UNESCAPED_UNICODE)) ?>"
              data-concluido="<?= e(t('funil.feed_concluido')) ?>"><?= e(t('funil.feed_concluido')) ?></strong>
      <span class="busca-contador"><b data-busca-contador><?= $total ?></b> <?= e(t('funil.feed_encontradas')) ?></span>
    </div>
    <div class="analise-progresso" aria-hidden="true"><span data-progresso></span></div>
  </div>

  <ol class="vagas-encontradas" aria-live="polite">
    <?php foreach ($feed as $item): ?>
      <?php
      $vaga = $item['vaga'];
      $salario = $vaga->salarioMax
          ? t('funil.feed_pagando_ate', ['valor' => moeda($vaga->salarioMax, $vaga->moeda, 0)])
          : ($vaga->salarioMin ? t('vaga.a_partir_de', ['valor' => moeda($vaga->salarioMin, $vaga->moeda, 0)]) : null);
      $remota = $vaga->modeloTrabalho === ModeloTrabalho::Remoto;
      $localVaga = $remota
          ? ($local ? t('funil.remota_de', ['local' => $local]) : t('funil.remota_' . $vaga->regiao->value))
          : ($vaga->cidade ? $vaga->cidade . ' · ' : '') . $vaga->modeloTrabalho->rotulo();
      $descricao = $vaga->descricao ?: t('funil.descricao_gerada', [
          'nivel' => mb_strtolower($vaga->senioridade->rotulo()),
          'area' => $vaga->area->rotulo(),
          'stack' => implode(', ', array_slice($vaga->stack, 0, 3)) ?: $vaga->area->rotulo(),
      ]);
      // A empresa fica oculta até a assinatura: some também de dentro da descrição.
      if ($vaga->empresa !== 'Confidencial' && mb_strlen($vaga->empresa) >= 3) {
          $descricao = preg_replace('/' . preg_quote($vaga->empresa, '/') . '/iu', '▒▒▒▒▒', $descricao);
      }
      ?>
      <li class="vaga-encontrada" data-busca-item>
        <div class="vaga-encontrada-topo">
          <span class="vaga-encontrada-selo"><?= icone('check', 'icone icone-sm') ?> <?= e(t('funil.vaga_encontrada')) ?></span>
          <span class="vaga-encontrada-direita">
            <?= icones_stack($vaga->stack) ?>
            <?php if (!empty($item['proxima'])): ?>
              <span class="feed-item-match feed-item-proxima"><?= e(t('vaga.proxima_do_perfil')) ?></span>
            <?php else: ?>
              <span class="feed-item-match"><?= e(t('vaga.compatibilidade', ['n' => (int) $item['compatibilidade']])) ?></span>
            <?php endif; ?>
          </span>
        </div>

        <h2><?= e($vaga->titulo) ?></h2>

        <p class="vaga-encontrada-empresa">
          <?= icone('predio', 'icone icone-sm') ?>
          <span class="empresa-censurada" style="--largura: <?= 5 + mb_strlen($vaga->titulo) % 5 ?>em" aria-label="<?= e(t('vaga.empresa_confidencial')) ?>"></span>
          <?= icone('cadeado', 'icone icone-sm') ?>
        </p>

        <ul class="vaga-encontrada-dados">
          <li><?= icone('local', 'icone icone-sm') ?> <?= e($localVaga) ?></li>
          <li><?= icone('maleta', 'icone icone-sm') ?> <?= e($vaga->senioridade->rotulo()) ?></li>
          <?php if ($salario): ?><li class="vaga-encontrada-salario"><?= icone('grafico', 'icone icone-sm') ?> <?= e($salario) ?></li><?php endif; ?>
        </ul>

        <p class="vaga-encontrada-descricao"><?= e($descricao) ?></p>

        <?php if ($vaga->stack): ?>
          <div class="vaga-encontrada-stack">
            <?php foreach (array_slice($vaga->stack, 0, 6) as $tecnologia): ?>
              <span class="etiqueta <?= in_array(mb_strtolower($tecnologia), $stackPerfil, true) ? 'etiqueta-sua' : '' ?>"><?= icone_stack($tecnologia) ?><?= e($tecnologia) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>

    <?php if ($feed === []): ?>
      <li class="vaga-encontrada vaga-encontrada-vazia" data-busca-item>
        <span class="estado-vazio-icone"><?= icone('relogio', 'icone icone-lg') ?></span>
        <h2><?= e(t('funil.feed_nenhuma')) ?></h2>
        <p><?= e(t('funil.feed_nenhuma_texto')) ?></p>
      </li>
    <?php endif; ?>
  </ol>

  <section class="busca-conclusao" data-busca-conclusao id="conclusao">
    <?php if ($total > 0): ?>
      <h2>
        <?= t('funil.conclusao_titulo', [
            'total' => '<span class="texto-gradiente">' . e($plural($total, 'funil.vagas_compativeis')) . '</span>',
        ]) ?>
      </h2>
      <p>
        <?php $todasRemotas = array_filter($compativeis, fn (array $item) => $item['vaga']->modeloTrabalho !== ModeloTrabalho::Remoto) === []; ?>
        <?= e(t($todasRemotas ? 'funil.conclusao_local_remotas' : 'funil.conclusao_local', ['local' => $lugar])) ?>
        <?php if ($panorama['salarioMaximo']): ?><?= e(t('funil.conclusao_salario', ['valor' => moeda($panorama['salarioMaximo'], $panorama['moeda'], 0)])) ?><?php endif; ?>
        <?php if ($restantes > 0): ?><?= e($plural($restantes, 'funil.mais_vagas')) ?>.<?php endif; ?>
        <?= e(t('funil.conclusao_texto')) ?>
      </p>
    <?php else: ?>
      <h2><?= e(t('funil.conclusao_vazia_titulo')) ?></h2>
      <p><?= e(t('funil.conclusao_vazia_texto')) ?></p>
    <?php endif; ?>

    <a class="botao botao-primario botao-grande botao-bloco" href="<?= e($destino) ?>"><?= icone('raio', 'icone icone-sm') ?> <?= e($textoBotao) ?> <?= icone('seta', 'icone icone-seta') ?></a>
    <ul class="busca-garantias">
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('funil.garantia_gratis')) ?></li>
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('funil.garantia_candidaturas')) ?></li>
      <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.confianca_2')) ?></li>
    </ul>
    <?php if (!$usuario): ?>
      <a class="link link-secundario" href="/comecar"><?= e(t('funil.refazer')) ?></a>
    <?php endif; ?>
  </section>
</section>

<div class="cta-fixo cta-fixo-funil" data-cta-fixo-funil>
  <a class="botao botao-primario botao-grande botao-bloco" href="<?= e($destino) ?>"><?= e($textoBotao) ?> <?= icone('seta', 'icone icone-seta') ?></a>
</div>
