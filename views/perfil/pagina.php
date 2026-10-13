<?php
/**
 * Página de perfil do profissional.
 * @var \App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional $perfil
 * @var array{percentual: int, pendencias: array<int, array{rotulo: string, importavel: bool}>} $forca
 * @var int $totalCompativeis @var array<string, int> $emAlta @var ?array $revisao @var ?string $sucesso @var ?string $erroExclusao
 */
use App\Enums\IdiomaFalado;

$primeiraLetra = fn (string $texto): string => iniciais($texto);
$moeda = $perfil->moedaPretensao;
$local = array_filter([$perfil->cidade ? $perfil->cidade : null]);
$links = array_filter([
    'linkedin' => $perfil->linkedinUrl,
    'github' => $perfil->githubUrl,
    'externo' => $perfil->portfolioUrl,
]);
$rotuloLink = ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'externo' => t('perfilpg.portfolio')];
$idiomas = array_map(fn (string $codigo) => IdiomaFalado::tryFrom($codigo), $perfil->idiomas);
?>
<script src="<?= asset('js/paginas/perfil.js') ?>" defer></script>
<?php if (!empty($sucesso)): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<section class="cartao perfil-topo">
  <span class="avatar avatar-enorme">
    <?php if ($usuario->avatarUrl): ?><img src="<?= e($usuario->avatarUrl) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= e($primeiraLetra($usuario->nome)) ?><?php endif; ?>
  </span>

  <div class="perfil-topo-info">
    <h1><?= e($usuario->nome) ?></h1>
    <p class="perfil-cargo"><?= e($perfil->cargoDesejado) ?></p>
    <ul class="perfil-marcas">
      <li><?= icone('maleta', 'icone icone-sm') ?> <?= e($perfil->senioridade->rotulo()) ?> · <?= e($perfil->area->rotulo()) ?></li>
      <li><?= icone('relogio', 'icone icone-sm') ?> <?= e(t('perfilpg.anos_exp', ['n' => $perfil->anosExperiencia])) ?></li>
      <li><?= icone('local', 'icone icone-sm') ?> <?= e($perfil->modeloTrabalho->rotulo()) ?><?= $perfil->cidade ? ' · ' . e($perfil->cidade) : '' ?></li>
    </ul>
    <?php if ($links): ?>
      <div class="perfil-links">
        <?php foreach ($links as $icone => $url): ?>
          <a class="botao botao-secundario botao-pequeno" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= icone($icone, 'icone icone-sm') ?> <?= e($rotuloLink[$icone]) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="perfil-topo-acoes">
    <a class="botao botao-secundario" href="/completar-perfil"><?= icone('ajustes', 'icone icone-sm') ?> <?= e(t('perfilpg.editar')) ?></a>
    <a class="botao botao-primario" href="#importar"><?= icone('upload', 'icone icone-sm') ?> <?= e(t('perfilpg.importar')) ?></a>
  </div>
</section>

<div class="perfil-grade">
  <div class="perfil-principal">
    <?= parcial('perfil/partials/importador', ['revisao' => $revisao]) ?>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.sobre')) ?></h2>
      <?php if ($perfil->resumo): ?>
        <p class="perfil-texto"><?= nl2br(e($perfil->resumo)) ?></p>
      <?php else: ?>
        <p class="perfil-vazio"><?= e(t('perfilpg.sobre_vazio')) ?> <a class="link" href="#importar"><?= e(t('perfilpg.importar_curto')) ?></a></p>
      <?php endif; ?>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.experiencia')) ?></h2>
      <?php if ($perfil->experiencias): ?>
        <ol class="perfil-linha-tempo">
          <?php foreach ($perfil->experiencias as $experiencia): ?>
            <li>
              <span class="perfil-marcador<?= !empty($experiencia['atual']) ? ' atual' : '' ?>"></span>
              <div>
                <h3><?= e($experiencia['cargo']) ?></h3>
                <?php if (!empty($experiencia['empresa'])): ?><p class="perfil-empresa"><?= icone('predio', 'icone icone-sm') ?> <?= e($experiencia['empresa']) ?></p><?php endif; ?>
                <small class="texto-suave">
                  <?= e(periodo_experiencia($experiencia['inicio'] ?? null, $experiencia['fim'] ?? null, !empty($experiencia['atual']))) ?>
                  <?php $duracao = duracao_experiencia($experiencia['inicio'] ?? null, !empty($experiencia['atual']) ? null : ($experiencia['fim'] ?? null)); ?>
                  <?= $duracao ? ' · ' . e($duracao) : '' ?>
                </small>
                <?php if (!empty($experiencia['descricao'])): ?><p class="perfil-texto"><?= e($experiencia['descricao']) ?></p><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php else: ?>
        <p class="perfil-vazio"><?= e(t('perfilpg.experiencia_vazia')) ?> <a class="link" href="#importar"><?= e(t('perfilpg.importar_curto')) ?></a></p>
      <?php endif; ?>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.formacao')) ?></h2>
      <?php if ($perfil->formacao): ?>
        <ul class="perfil-formacao">
          <?php foreach ($perfil->formacao as $item): ?>
            <li>
              <span class="perfil-formacao-icone"><?= icone('brilho', 'icone icone-sm') ?></span>
              <div>
                <h3><?= e($item['curso'] ?: $item['instituicao']) ?></h3>
                <?php if (!empty($item['curso']) && !empty($item['instituicao'])): ?><p class="perfil-empresa"><?= e($item['instituicao']) ?></p><?php endif; ?>
                <?php if (!empty($item['ano'])): ?><small class="texto-suave"><?= e($item['ano']) ?></small><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="perfil-vazio"><?= e(t('perfilpg.formacao_vazia')) ?> <a class="link" href="#importar"><?= e(t('perfilpg.importar_curto')) ?></a></p>
      <?php endif; ?>
    </section>
  </div>

  <aside class="perfil-lateral">
    <?= parcial('perfil/partials/assinatura', ['assinatura' => $assinatura, 'limiteDiario' => $limiteDiario]) ?>

    <section class="cartao perfil-secao perfil-forca">
      <h2><?= e(t('perfilpg.forca')) ?></h2>
      <div class="perfil-anel" style="--valor: <?= (int) $forca['percentual'] ?>" role="img" aria-label="<?= (int) $forca['percentual'] ?>%">
        <strong><?= (int) $forca['percentual'] ?><small>%</small></strong>
      </div>
      <p class="texto-suave perfil-forca-texto">
        <?= e(t($forca['percentual'] >= 90 ? 'perfilpg.forca_otima' : 'perfilpg.forca_texto', ['n' => $totalCompativeis])) ?>
      </p>
      <?php if ($forca['pendencias']): ?>
        <ul class="perfil-pendencias">
          <?php foreach ($forca['pendencias'] as $pendencia): ?>
            <li>
              <span><?= icone('x', 'icone icone-sm') ?> <?= e($pendencia['rotulo']) ?></span>
              <a class="link" href="<?= $pendencia['importavel'] ? '#importar' : '/completar-perfil' ?>"><?= e(t($pendencia['importavel'] ? 'perfilpg.importar_curto' : 'perfilpg.preencher')) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.stack')) ?></h2>
      <div class="lista-etiquetas">
        <?php foreach ($perfil->stack as $tecnologia): ?><span class="etiqueta"><?= icone_stack($tecnologia) ?><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
      <?php if ($emAlta): ?>
        <div class="perfil-alta">
          <h3><?= icone('grafico', 'icone icone-sm') ?> <?= e(t('perfilpg.em_alta')) ?></h3>
          <p class="texto-suave"><?= e(t('perfilpg.em_alta_texto')) ?></p>
          <ul>
            <?php foreach ($emAlta as $tecnologia => $vagas): ?>
              <li><span class="etiqueta etiqueta-sugerida"><?= icone_stack((string) $tecnologia) ?><?= e((string) $tecnologia) ?></span> <small class="texto-suave"><?= e(t($vagas === 1 ? 'perfilpg.em_1_vaga' : 'perfilpg.em_n_vagas', ['n' => $vagas])) ?></small></li>
            <?php endforeach; ?>
          </ul>
          <a class="link" href="/completar-perfil"><?= e(t('perfilpg.adicionar_stack')) ?></a>
        </div>
      <?php endif; ?>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.idiomas')) ?></h2>
      <?php if ($idiomas): ?>
        <ul class="perfil-idiomas">
          <?php foreach ($idiomas as $idioma): if ($idioma === null) { continue; } ?>
            <li>
              <img src="/assets/img/bandeiras/<?= e($idioma->bandeira()) ?>.svg" alt="" width="22" height="16">
              <span><?= e($idioma->rotulo()) ?></span>
              <?php if ($idioma === IdiomaFalado::Ingles && $perfil->nivelIngles): ?><small class="selo selo-neutro"><?= e($perfil->nivelIngles->rotulo()) ?></small><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="perfil-vazio"><?= e(t('perfilpg.idiomas_vazio')) ?></p>
      <?php endif; ?>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.preferencias')) ?></h2>
      <dl class="lista-dados">
        <div><dt><?= e(t('perfilpg.modelo')) ?></dt><dd><?= e($perfil->modeloTrabalho->rotulo()) ?></dd></div>
        <div><dt><?= e(t('perfilpg.regiao')) ?></dt><dd><?= e($perfil->regiaoVagas->rotulo()) ?></dd></div>
        <?php if ($perfil->pretensaoSalarial): ?>
          <div><dt><?= e(t('perfilpg.pretensao')) ?></dt><dd><?= e(moeda((float) $perfil->pretensaoSalarial, $moeda, 0)) ?> <small class="texto-suave"><?= e($moeda === 'BRL' ? t('funil.por_mes') : t('funil.por_ano')) ?></small></dd></div>
        <?php endif; ?>
      </dl>
      <p class="perfil-privado"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('perfil.privados')) ?></p>
    </section>

    <section class="cartao perfil-secao">
      <h2><?= e(t('perfilpg.curriculo')) ?></h2>
      <?php if ($perfil->possuiCurriculo()): ?>
        <a class="perfil-arquivo" href="/perfil/curriculo" target="_blank">
          <span class="perfil-arquivo-icone"><?= icone('arquivo') ?></span>
          <span><strong><?= e($perfil->curriculoNomeOriginal) ?></strong><small class="texto-suave"><?= e(t('perfilpg.abrir_pdf')) ?></small></span>
        </a>
      <?php else: ?>
        <p class="perfil-vazio"><?= e(t('perfilpg.curriculo_vazio')) ?></p>
      <?php endif; ?>
    </section>
  </aside>
</div>

<section class="cartao perfil-secao perfil-exclusao" id="excluir">
  <details <?= !empty($erroExclusao) ? 'open' : '' ?>>
    <summary><?= e(t('perfilpg.excluir_titulo')) ?></summary>
    <p class="texto-suave"><?= e(t('perfilpg.excluir_texto')) ?></p>
    <?php if (!empty($erroExclusao)): ?><div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erroExclusao) ?></span></div><?php endif; ?>
    <form method="post" action="/perfil/excluir" class="formulario-linha"
          onsubmit="return confirm('<?= e(addslashes(t('perfilpg.excluir_certeza'))) ?>')">
      <?= csrf_campo() ?>
      <label class="campo-rotulo" for="confirmacao-exclusao"><?= e(t('perfilpg.excluir_instrucao', ['palavra' => t('perfilpg.excluir_palavra')])) ?></label>
      <div class="acoes-cabecalho">
        <input class="entrada" id="confirmacao-exclusao" name="confirmacao" autocomplete="off" required>
        <button class="botao botao-perigo" type="submit"><?= e(t('perfilpg.excluir_botao')) ?></button>
      </div>
    </form>
  </details>
</section>
