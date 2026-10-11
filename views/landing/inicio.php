<?php
$stacks = ['JavaScript', 'TypeScript', 'React', 'Next.js', 'Vue', 'Angular', 'Node.js', 'PHP', 'Laravel', 'Python', 'Django', 'Java', 'Spring', 'C#', '.NET', 'Go', 'Ruby', 'Kotlin', 'Swift', 'Flutter', 'React Native', 'AWS', 'Docker', 'Kubernetes', 'PostgreSQL', 'MySQL'];
$metade = (int) ceil(count($stacks) / 2);
$faixas = [array_slice($stacks, 0, $metade), array_slice($stacks, $metade)];
/** @var int $vagasAbertas @var int $vagasNovas @var \App\Entities\Vaga[] $vagasRecentes @var array $planos @var string $moeda */
$iconesArea = ['frontend' => 'camadas', 'backend' => 'terminal', 'fullstack' => 'codigo', 'mobile' => 'raio', 'dados' => 'grafico', 'devops' => 'ajustes', 'qa' => 'check'];
$perguntas = array_map(fn (int $n) => [t("landing.faq_{$n}_p"), t("landing.faq_{$n}_r")], range(1, 7));
?>

<!-- ============================== HERO ============================== -->
<section class="hero">
  <div class="hero-fundo" aria-hidden="true">
    <div class="hero-grade"></div>
    <div class="hero-flutuantes">
      <?php foreach ([['TypeScript', 58, 12, 1.2], ['Python', 90, 18, .6], ['Node.js', 62, 90, 2], ['Docker', 88, 86, .3], ['Kotlin', 76, 6, 2.4], ['React', 54, 52, 1]] as [$nome, $x, $y, $atraso]): ?>
        <span style="--x: <?= $x ?>%; --y: <?= $y ?>%; --atraso: <?= $atraso ?>s"><?= icone_stack($nome) ?><?= e($nome) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="hero-luz hero-luz-1"></div>
    <div class="hero-luz hero-luz-2"></div>
  </div>

  <div class="container hero-interno">
    <div class="hero-texto">
      <a class="hero-anuncio" href="/comecar">
        <span class="selo selo-sucesso selo-ponto"><?php if ($vagasAbertas > 0): ?><span data-contar-ate="<?= (int) $vagasAbertas ?>"><?= e(numero($vagasAbertas)) ?></span><?php else: ?><?= e(t('landing.anuncio_selo')) ?><?php endif; ?></span>
        <span><?= e(t($vagasAbertas > 0 ? ($vagasAbertas === 1 ? 'landing.vaga_aberta_agora' : 'landing.vagas_abertas_agora') : 'landing.anuncio')) ?><?php if ($vagasNovas > 0): ?> · <b><?= e(t('landing.vitrine_novas', ['n' => $vagasNovas])) ?></b><?php endif; ?></span>
        <?= icone('chevron-dir', 'icone icone-sm') ?>
      </a>

      <h1 class="hero-titulo">
        <?= e(t('landing.programa_em')) ?>
        <?php $palavras = array_map(fn (string $nome) => ['nome' => $nome, 'icone' => asset('img/stacks/' . \App\Core\IconesStack::arquivo($nome) . '.svg')], ['React', 'Python', 'Java', 'Node.js', 'PHP', 'Flutter', '.NET', 'Go']); ?>
        <span class="palavra-rotativa" data-palavras="<?= e(json_encode($palavras, JSON_UNESCAPED_SLASHES)) ?>"><span><img class="palavra-icone" src="<?= e($palavras[0]['icone']) ?>" alt="">React</span></span>.<br>
        <span class="texto-gradiente"><?= e(t('marca.slogan_2')) ?></span>
      </h1>

      <p class="hero-subtitulo"><?= t('landing.subtitulo', ['stack' => '<strong>' . e(t('landing.stack')) . '</strong>', 'nivel' => '<strong>' . e(t('landing.nivel')) . '</strong>']) ?></p>

      <!-- O questionário começa aqui: um toque já responde a primeira pergunta. -->
      <div class="inicio-quiz" id="comecar">
        <p class="inicio-quiz-titulo"><span class="inicio-quiz-passo">1/10</span> <?= e(t('landing.quiz_pergunta')) ?></p>
        <div class="inicio-quiz-opcoes">
          <?php foreach (\App\Enums\AreaAtuacao::cases() as $area): ?>
            <a class="inicio-quiz-opcao" href="/comecar?area=<?= e($area->value) ?>">
              <?= icone($iconesArea[$area->value] ?? 'codigo', 'icone icone-sm') ?>
              <span><?= e($area->rotulo()) ?></span>
            </a>
          <?php endforeach; ?>
          <a class="inicio-quiz-opcao inicio-quiz-opcao-cta" href="/comecar">
            <span><?= e(t('landing.quiz_outra')) ?></span> <?= icone('seta', 'icone icone-sm') ?>
          </a>
        </div>
      </div>

      <ul class="hero-confianca">
        <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.confianca_1')) ?></li>
        <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.confianca_tempo')) ?></li>
        <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.confianca_2')) ?></li>
      </ul>
    </div>

    <?php if ($vagasRecentes): ?>
      <?= parcial('partials/vitrine-vagas', ['vagas' => $vagasRecentes, 'novas' => $vagasNovas]) ?>
    <?php endif; ?>
  </div>
</section>

<!-- ============================== STACKS ============================== -->
<section class="faixa-stacks" aria-label="<?= e(t('landing.stacks_rotulo')) ?>">
  <p class="faixa-stacks-titulo"><?= e(t('landing.stacks_titulo')) ?></p>
  <?php foreach ($faixas as $indice => $faixa): ?>
    <div class="letreiro <?= $indice === 1 ? 'letreiro-reverso' : '' ?>">
      <div class="letreiro-trilho">
        <?php for ($repeticao = 0; $repeticao < 2; $repeticao++): ?>
          <?php foreach ($faixa as $stack): ?>
            <span class="letreiro-item"><?= icone_stack($stack) ?: icone('codigo', 'icone icone-sm') ?><?= e($stack) ?></span>
          <?php endforeach; ?>
        <?php endfor; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<!-- ============================== SOZINHO × DEVACANCY ============================== -->
<section class="secao secao-compacta">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.problema_sobretitulo')) ?></span>
      <h2><?= t('landing.problema_titulo', ['destaque' => '<span class="texto-gradiente">' . e(t('landing.problema_destaque')) . '</span>']) ?></h2>
    </div>

    <div class="comparativo revelar">
      <div class="comparativo-coluna comparativo-sozinho">
        <h3><?= icone('relogio', 'icone icone-sm') ?> <?= e(t('landing.sozinho')) ?></h3>
        <ul>
          <?php foreach (range(1, 4) as $n): ?>
            <li><?= icone('x', 'icone icone-sm') ?> <?= e(t("landing.sozinho_{$n}")) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="comparativo-coluna comparativo-devacancy">
        <h3><?= icone('raio', 'icone icone-sm') ?> <?= e(t('landing.com_devacancy')) ?></h3>
        <ul>
          <?php foreach (range(1, 4) as $n): ?>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t("landing.com_{$n}")) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="botao botao-primario botao-bloco" href="/comecar"><?= e(t('landing.cta_principal')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============================== COMO FUNCIONA ============================== -->
<section class="secao secao-destaque" id="como-funciona">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.como_sobretitulo')) ?></span>
      <h2><?= e(t('landing.como_titulo')) ?></h2>
      <p><?= e(t('landing.como_texto')) ?></p>
    </div>

    <div class="demo-passos revelar" data-demo-passos>
      <ol class="demo-abas">
        <?php foreach (range(1, 3) as $n): ?>
          <li>
            <button type="button" class="demo-aba<?= $n === 1 ? ' ativa' : '' ?>" data-aba="<?= $n ?>" aria-pressed="<?= $n === 1 ? 'true' : 'false' ?>">
              <span class="demo-aba-numero mono">0<?= $n ?></span>
              <span class="demo-aba-texto">
                <strong><?= e(t("landing.passo_{$n}_titulo")) ?></strong>
                <small><?= e(t("landing.passo_{$n}_texto")) ?></small>
              </span>
              <span class="demo-aba-barra" aria-hidden="true"><i></i></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ol>

      <!-- Celular desenhado em código: uma tela por passo. Clicável: leva direto pro questionário. -->
      <a class="celular" href="/comecar" aria-label="<?= e(t('landing.demo_clique')) ?>">
        <div class="celular-tela">
          <div class="celular-barra"><span class="marca-mini">dev<b>acancy</b></span><span class="celular-hora">9:41</span></div>

          <div class="celular-cena ativa" data-cena="1">
            <div class="cena-progresso"><i style="--valor: 20%"></i></div>
            <small class="mono"><?= e(t('funil.pergunta_de', ['n' => 2, 'total' => 10])) ?></small>
            <strong class="cena-titulo"><?= e(t('funil.p_nivel')) ?></strong>
            <?php foreach (['junior', 'pleno', 'senior'] as $nivel): ?>
              <span class="cena-opcao<?= $nivel === 'pleno' ? ' marcada' : '' ?>"><?= e(t('senioridade.' . $nivel)) ?></span>
            <?php endforeach; ?>
            <span class="cena-botao"><?= e(t('funil.continuar')) ?> →</span>
          </div>

          <div class="celular-cena" data-cena="2">
            <span class="cena-status"><i></i> <?= e(t('funil.status_compatibilidade')) ?></span>
            <?php foreach ([[94, 1], [89, 2], [81, 3]] as [$match, $n]): ?>
              <span class="cena-vaga" style="--atraso: <?= ($n - 1) * .35 ?>s">
                <b class="cena-selo">✓ <?= e(t('funil.vaga_encontrada')) ?> · <?= $match ?>%</b>
                <strong><?= e(t("landing.previa_{$n}")) ?></strong>
                <small><?= e(t("landing.previa_{$n}_info")) ?></small>
              </span>
            <?php endforeach; ?>
          </div>

          <div class="celular-cena" data-cena="3">
            <strong class="cena-titulo"><?= e(t('landing.demo_painel')) ?></strong>
            <?php foreach ([['entrevista', 'selo-sucesso', 1], ['enviada', 'selo-primario', 2], ['na_fila', 'selo-alerta', 3]] as [$status, $classe, $n]): ?>
              <span class="cena-candidatura" style="--atraso: <?= ($n - 1) * .3 ?>s">
                <span><strong><?= e(t("landing.previa_{$n}")) ?></strong></span>
                <b class="selo <?= $classe ?>"><?= e(t('candidatura.' . $status)) ?></b>
              </span>
            <?php endforeach; ?>
            <small class="cena-rodape"><?= e(t('landing.divisao_nos_tempo')) ?></small>
          </div>
        </div>
        <span class="celular-dica"><?= e(t('landing.demo_clique')) ?> <?= icone('seta', 'icone icone-sm') ?></span>
      </a>
    </div>
  </div>
</section>

<!-- ============================== VOCÊ × A DEVACANCY ============================== -->
<section class="secao secao-compacta">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.divisao_sobretitulo')) ?></span>
      <h2><?= t('landing.divisao_titulo', ['destaque' => '<span class="texto-gradiente">' . e(t('landing.divisao_destaque')) . '</span>']) ?></h2>
    </div>

    <div class="divisao revelar">
      <div class="divisao-coluna divisao-voce">
        <h3><?= icone('usuario', 'icone icone-sm') ?> <?= e(t('landing.divisao_voce')) ?></h3>
        <ol>
          <?php foreach (range(1, 3) as $n): ?>
            <li><span><?= $n ?></span> <?= e(t("landing.divisao_voce_{$n}")) ?></li>
          <?php endforeach; ?>
        </ol>
        <p class="divisao-tempo"><?= icone('relogio', 'icone icone-sm') ?> <?= e(t('landing.divisao_voce_tempo')) ?></p>
      </div>
      <div class="divisao-coluna divisao-nos">
        <h3><?= icone('raio', 'icone icone-sm') ?> <?= e(t('landing.divisao_nos')) ?></h3>
        <ul>
          <?php foreach (range(1, 4) as $n): ?>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t("landing.divisao_nos_{$n}")) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="divisao-tempo"><?= icone('grafico', 'icone icone-sm') ?> <?= e(t('landing.divisao_nos_tempo')) ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ============================== PRÉVIA DO RESULTADO ============================== -->
<section class="secao secao-compacta">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.previa_sobretitulo')) ?></span>
      <h2><?= e(t('landing.previa_titulo')) ?></h2>
      <p><?= e(t('landing.previa_texto')) ?></p>
    </div>

    <div class="previa revelar">
      <div class="feed-busca ativo concluido">
        <div class="feed-busca-topo">
          <span class="feed-busca-radar" aria-hidden="true"></span>
          <strong><?= e(t('funil.feed_concluido')) ?></strong>
          <span class="feed-busca-contador"><?= e(t('landing.previa_exemplo')) ?></span>
        </div>
        <ul class="feed-lista">
          <?php foreach ([[94, 1], [89, 2], [81, 3]] as [$match, $n]): ?>
            <li class="feed-item visivel">
              <span class="feed-item-icone"><?= icone('check', 'icone icone-sm') ?></span>
              <div class="feed-item-texto">
                <strong><?= e(t("landing.previa_{$n}")) ?></strong>
                <small><span class="empresa-censurada" style="--largura: <?= 4 + $n ?>em"></span> · <?= e(t("landing.previa_{$n}_info")) ?></small>
              </div>
              <span class="feed-item-match"><?= $match ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <a class="botao botao-primario botao-grande botao-bloco" href="/comecar"><?= e(t('landing.previa_cta')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
    </div>
  </div>
</section>

<!-- ============================== RECURSOS ============================== -->
<section class="secao somente-desktop" id="recursos">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.recursos_sobretitulo')) ?></span>
      <h2><?= e(t('landing.recursos_titulo')) ?></h2>
    </div>

    <div class="grade-recursos">
      <article class="recurso recurso-largo revelar" data-holofote>
        <div class="recurso-texto">
          <span class="recurso-icone"><?= icone('alvo') ?></span>
          <h3><?= e(t('landing.recurso_1_titulo')) ?></h3>
          <p><?= e(t('landing.recurso_1_texto')) ?></p>
        </div>
        <div class="recurso-visual-stack">
          <?php foreach (['PHP' => true, 'Laravel' => true, 'Vue' => true, 'Docker' => true, 'AWS' => false, 'Kotlin' => false, 'MySQL' => true, 'Redis' => false] as $tecnologia => $combina): ?>
            <span class="etiqueta <?= $combina ? 'etiqueta-combina' : '' ?>"><?= $combina ? icone('check', 'icone icone-sm') : '' ?><?= e($tecnologia) ?></span>
          <?php endforeach; ?>
        </div>
      </article>

      <?php foreach ([['brilho', 2], ['grafico', 3], ['ajustes', 4], ['usuarios', 5]] as [$icone, $n]): ?>
        <article class="recurso revelar" data-holofote>
          <span class="recurso-icone"><?= icone($icone) ?></span>
          <h3><?= e(t("landing.recurso_{$n}_titulo")) ?></h3>
          <p><?= e(t("landing.recurso_{$n}_texto")) ?></p>
        </article>
      <?php endforeach; ?>

      <article class="recurso recurso-faixa revelar" data-holofote>
        <span class="recurso-icone"><?= icone('escudo') ?></span>
        <div>
          <h3><?= e(t('landing.recurso_6_titulo')) ?></h3>
          <p><?= e(t('landing.recurso_6_texto')) ?></p>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ============================== EMPRESAS ============================== -->
<section class="secao somente-desktop" id="empresas">
  <div class="container">
    <div class="empresas revelar">
      <div class="empresas-texto">
        <span class="sobretitulo"><?= e(t('landing.empresas_sobretitulo')) ?></span>
        <h2><?= e(t('landing.empresas_titulo_1')) ?><br><?= e(t('landing.empresas_titulo_2')) ?></h2>
        <p><?= e(t('landing.empresas_texto')) ?></p>
        <ul class="lista-verificada">
          <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.empresas_item_1')) ?></li>
          <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.empresas_item_2')) ?></li>
          <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.empresas_item_3')) ?></li>
        </ul>
        <a class="botao botao-claro botao-grande" href="/cadastro?tipo=empresa">
          <?= e(t('landing.empresas_cta')) ?> <?= icone('seta', 'icone icone-seta') ?>
        </a>
      </div>

      <div class="empresas-visual" aria-hidden="true">
        <?php foreach ([
            ['RA', 'Front-end', 'senior', ['React', 'TypeScript', 'Next.js'], 'remoto'],
            ['LM', 'Back-end', 'pleno', ['Node.js', 'PostgreSQL', 'AWS'], 'hibrido'],
            ['JP', 'Mobile', 'pleno', ['Flutter', 'Dart', 'Firebase'], 'remoto'],
        ] as [$iniciais, $area, $nivel, $tecnologias, $modelo]): ?>
          <div class="candidato">
            <div class="avatar"><?= e($iniciais) ?></div>
            <div class="candidato-info">
              <strong><?= e(t('landing.dev_area', ['area' => $area])) ?> <span class="selo selo-primario"><?= e(t('senioridade.' . $nivel)) ?></span></strong>
              <div class="lista-etiquetas">
                <?php foreach ($tecnologias as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
              </div>
            </div>
            <span class="candidato-modelo"><?= icone('local', 'icone icone-sm') ?><?= e(t('modelo.' . $modelo)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============================== CHAMADA ============================== -->
<section class="secao somente-desktop">
  <div class="container">
    <div class="beta revelar">
      <div class="beta-texto">
        <span class="selo selo-primario selo-ponto"><?= e(t('landing.chamada_selo')) ?></span>
        <h2><?= e(t('landing.chamada_titulo')) ?></h2>
        <p><?= e(t('landing.chamada_texto')) ?></p>
      </div>
      <div class="beta-cartao">
        <ul class="beta-lista">
          <?php foreach (range(1, 4) as $n): ?>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t("landing.chamada_item_{$n}")) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="botao botao-primario botao-grande botao-bloco" href="/comecar"><?= e(t('landing.cta_principal')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============================== PREÇOS ============================== -->
<section class="secao secao-compacta" id="precos">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo"><?= e(t('landing.precos_sobretitulo')) ?></span>
      <h2><?= t('landing.precos_titulo', ['destaque' => '<span class="texto-gradiente">' . e(t('landing.precos_destaque')) . '</span>']) ?></h2>
      <p><?= e(t('landing.precos_texto')) ?></p>
    </div>

    <ol class="precos-passos revelar">
      <li><span>1</span> <?= e(t('landing.precos_passo_1')) ?> <b><?= e(t('landing.gratis')) ?></b></li>
      <li><span>2</span> <?= e(t('landing.precos_passo_2')) ?> <b><?= e(t('landing.gratis')) ?></b></li>
      <li><span>3</span> <?= e(t('landing.precos_passo_3')) ?></li>
    </ol>

    <div class="precos revelar">
      <?php foreach ($planos as $chave => $plano): ?>
        <?php $preco = $plano['precos'][$moeda]; ?>
        <article class="preco<?= !empty($plano['destaque']) ? ' preco-destaque' : '' ?>">
          <?php if (!empty($plano['destaque'])): ?><span class="preco-selo"><?= e(t('planos.mais_escolhido')) ?></span><?php endif; ?>
          <h3><?= e(t('plano.' . $chave)) ?></h3>
          <p class="preco-valor"><strong><?= e(moeda($preco / $plano['divisor'], $moeda)) ?></strong><small>/<?= e(t('planos.por_' . $plano['periodo'])) ?></small></p>
          <p class="preco-cobranca">
            <?= e($plano['divisor'] > 1 ? t('planos.cobrado_a_cada', ['valor' => moeda($preco, $moeda), 'n' => $plano['divisor']]) : t('planos.cobrado_' . $plano['periodo'])) ?>
          </p>
          <ul>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.precos_item_1')) ?></li>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.precos_item_2')) ?></li>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.precos_item_3')) ?></li>
            <li><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.precos_item_4')) ?></li>
          </ul>
          <a class="botao <?= !empty($plano['destaque']) ? 'botao-primario' : 'botao-acao-suave' ?> botao-bloco" href="/comecar"><?= e(t(!empty($plano['destaque']) ? 'landing.precos_cta' : 'landing.cta_curto')) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="precos-nota"><?= icone('escudo', 'icone icone-sm') ?> <?= e(t('landing.precos_nota')) ?></p>
  </div>
</section>

<!-- ============================== FAQ ============================== -->
<section class="secao" id="perguntas">
  <div class="container faq">
    <div class="secao-cabecalho secao-cabecalho-esquerda revelar">
      <span class="sobretitulo"><?= e(t('landing.faq_sobretitulo')) ?></span>
      <h2><?= e(t('landing.faq_titulo')) ?></h2>
      <p><?= e(t('landing.faq_texto')) ?></p>
    </div>

    <div class="faq-lista">
      <?php foreach ($perguntas as $indice => [$pergunta, $resposta]): ?>
        <details class="faq-item revelar" <?= $indice === 0 ? 'open' : '' ?>>
          <summary><?= e($pergunta) ?><span class="faq-sinal" aria-hidden="true"></span></summary>
          <div class="faq-resposta"><p><?= e($resposta) ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================== CTA FINAL ============================== -->
<section class="secao secao-final">
  <div class="container">
    <div class="cta-final revelar">
      <div class="cta-final-brilho" aria-hidden="true"></div>
      <h2><?= e(t('landing.final_titulo_1')) ?><br><span class="texto-gradiente"><?= e(t('landing.final_titulo_2')) ?></span></h2>
      <p><?= e(t('landing.final_texto')) ?></p>
      <div class="hero-acoes">
        <a class="botao botao-primario botao-grande" href="/comecar"><?= e(t('landing.cta_principal')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
        <a class="botao botao-secundario botao-grande" href="/cadastro?tipo=empresa"><?= e(t('landing.sou_empresa')) ?></a>
      </div>
      <p class="cta-micro"><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.cta_micro')) ?></p>
    </div>
  </div>
</section>

<!-- Botão fixo no celular: aparece quando o início do questionário sai da tela. -->
<div class="cta-fixo" data-cta-fixo aria-hidden="true">
  <a class="botao botao-primario botao-grande botao-bloco" href="/comecar" tabindex="-1"><?= e(t('landing.cta_principal')) ?> <?= icone('seta', 'icone icone-seta') ?></a>
  <small><?= icone('check', 'icone icone-sm') ?> <?= e(t('landing.cta_micro')) ?></small>
</div>
