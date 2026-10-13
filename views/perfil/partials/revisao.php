<?php
/**
 * Revisão antes de aplicar: o que foi encontrado no currículo ao lado do que já está no perfil.
 * @var array{origem: string, linhas: array<int, array<string, mixed>>, pdf: ?string} $revisao
 */
?>
<form class="importador-revisao" method="post" action="/perfil/importar/aplicar" data-revisao>
  <?= csrf_campo() ?>

  <header class="importador-titulo">
    <span class="importador-icone sucesso"><?= icone('check', 'icone') ?></span>
    <div>
      <h2><?= e(t($revisao['origem'] === 'linkedin' ? 'importar.revisao_titulo_linkedin' : 'importar.revisao_titulo')) ?></h2>
      <p class="texto-suave"><?= e(t('importar.revisao_texto')) ?></p>
    </div>
  </header>

  <?php if ($revisao['linhas'] === []): ?>
    <div class="alerta alerta-aviso"><?= icone('relogio') ?><span><?= e(t('importar.nada_novo')) ?></span></div>
  <?php else: ?>
    <div class="revisao-acoes-topo">
      <button class="botao botao-fantasma botao-pequeno" type="button" data-marcar="todos"><?= e(t('importar.marcar_todos')) ?></button>
      <button class="botao botao-fantasma botao-pequeno" type="button" data-marcar="nenhum"><?= e(t('importar.desmarcar_todos')) ?></button>
    </div>

    <ul class="revisao-lista">
      <?php foreach ($revisao['linhas'] as $linha): $id = 'rev-' . $linha['chave']; ?>
        <li class="revisao-item">
          <label class="revisao-marca" for="<?= e($id) ?>">
            <input type="checkbox" id="<?= e($id) ?>" name="campos[]" value="<?= e($linha['chave']) ?>" checked>
            <span class="revisao-caixa" aria-hidden="true"><?= icone('check', 'icone icone-sm') ?></span>
          </label>
          <div class="revisao-corpo">
            <label class="revisao-rotulo" for="<?= e($id) ?>"><?= e($linha['rotulo']) ?>
              <?php if (!empty($linha['soma'])): ?><span class="selo selo-neutro"><?= e(t('importar.soma')) ?></span><?php endif; ?>
            </label>

            <?php if (!empty($linha['chips'])): ?>
              <div class="lista-etiquetas">
                <?php foreach ($linha['chips'] as $chip): ?><span class="etiqueta etiqueta-nova">+ <?= icone_stack($chip) ?><?= e($chip) ?></span><?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="revisao-novo <?= !empty($linha['longo']) ? 'longo' : '' ?>"><?= e($linha['novo']) ?></p>
            <?php endif; ?>

            <?php if (!empty($linha['lista'])): ?>
              <details class="revisao-detalhes">
                <summary><?= e(t('importar.ver_itens')) ?></summary>
                <ul>
                  <?php foreach ($linha['lista'] as $item): ?>
                    <li>
                      <?php if (($linha['tipo_lista'] ?? '') === 'experiencias'): ?>
                        <strong><?= e($item['cargo']) ?></strong><?= !empty($item['empresa']) ? ' · ' . e($item['empresa']) : '' ?>
                        <small class="texto-suave"><?= e(periodo_experiencia($item['inicio'] ?? null, $item['fim'] ?? null, !empty($item['atual']))) ?></small>
                      <?php else: ?>
                        <strong><?= e($item['curso'] ?: $item['instituicao']) ?></strong><?= !empty($item['curso']) && !empty($item['instituicao']) ? ' · ' . e($item['instituicao']) : '' ?>
                        <?= !empty($item['ano']) ? '<small class="texto-suave">' . e($item['ano']) . '</small>' : '' ?>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>

            <?php if (!empty($linha['dica'])): ?><small class="revisao-dica"><?= e($linha['dica']) ?></small><?php endif; ?>
            <?php if ($linha['atual'] !== null && $linha['atual'] !== ''): ?>
              <small class="revisao-atual"><?= e(t('importar.hoje')) ?>: <span><?= e((string) $linha['atual']) ?></span></small>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="revisao-rodape">
    <button class="botao botao-primario botao-grande" type="submit" <?= $revisao['linhas'] === [] ? 'disabled' : '' ?>>
      <?= icone('check', 'icone icone-sm') ?> <?= e(t('importar.aplicar_marcados')) ?>
    </button>
    <button class="botao botao-secundario" type="submit" formaction="/perfil/importar/descartar" formnovalidate><?= e(t('importar.descartar')) ?></button>
  </div>
</form>
