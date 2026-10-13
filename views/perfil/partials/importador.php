<?php
/**
 * Cartão "Atualize seu perfil": PDF, PDF do LinkedIn ou texto colado. O texto do PDF é lido no navegador
 * (js/paginas/perfil.js); o site só recebe o texto e o próprio PDF. @var ?array $revisao
 */
?>
<section class="cartao perfil-importador" id="importar" data-importador
         data-pdfjs="<?= e(asset('js/vendor/pdfjs/pdf.min.js')) ?>" data-worker="<?= e(asset('js/vendor/pdfjs/pdf.worker.min.js')) ?>"
         data-textos="<?= e(json_encode([
             'lendo' => t('importar.lendo'), 'enviando' => t('importar.enviando'), 'sem_texto' => t('importar.sem_texto'),
             'erro_leitura' => t('importar.erro_leitura'), 'so_pdf' => t('importar.so_pdf'), 'grande' => t('importar.arquivo_grande'),
         ], JSON_UNESCAPED_UNICODE)) ?>">
  <div id="importacao-resultado" aria-live="polite">
    <?php if ($revisao): ?>
      <?= parcial('perfil/partials/revisao', ['revisao' => $revisao]) ?>
    <?php endif; ?>
  </div>

  <div class="importador-entrada" data-importador-entrada <?= $revisao ? 'hidden' : '' ?>>
    <header class="importador-titulo">
      <span class="importador-icone"><?= icone('raio', 'icone') ?></span>
      <div>
        <h2><?= e(t('importar.titulo')) ?></h2>
        <p class="texto-suave"><?= e(t('importar.texto')) ?></p>
      </div>
    </header>

    <div class="importador-abas" role="tablist">
      <button type="button" role="tab" class="ativo" data-aba="pdf" aria-selected="true"><?= icone('arquivo', 'icone icone-sm') ?> <?= e(t('importar.aba_pdf')) ?></button>
      <button type="button" role="tab" data-aba="linkedin" aria-selected="false"><?= icone('linkedin', 'icone icone-sm') ?> <?= e(t('importar.aba_linkedin')) ?></button>
      <button type="button" role="tab" data-aba="texto" aria-selected="false"><?= icone('codigo', 'icone icone-sm') ?> <?= e(t('importar.aba_texto')) ?></button>
    </div>

    <div class="importador-painel" data-painel="linkedin" hidden>
      <ol class="importador-passos">
        <li><?= t('importar.li_passo_1', ['link' => '<a class="link" href="https://www.linkedin.com/in/" target="_blank" rel="noopener">linkedin.com/in</a>']) ?></li>
        <li><?= e(t('importar.li_passo_2')) ?></li>
        <li><?= e(t('importar.li_passo_3')) ?></li>
      </ol>
      <p class="importador-nota"><?= icone('escudo', 'icone icone-sm') ?> <?= e(t('importar.li_nota')) ?></p>
    </div>

    <div class="importador-painel" data-painel="texto" hidden>
      <label class="campo-rotulo" for="importar-texto"><?= e(t('importar.texto_rotulo')) ?></label>
      <textarea class="area-texto" id="importar-texto" rows="7" maxlength="60000" data-texto-colado placeholder="<?= e(t('importar.texto_placeholder')) ?>"></textarea>
      <button class="botao botao-primario" type="button" data-enviar-texto><?= icone('raio', 'icone icone-sm') ?> <?= e(t('importar.analisar')) ?></button>
    </div>

    <label class="zona-arquivo importador-zona" data-zona-importar data-painel-zona="pdf linkedin">
      <input type="file" accept="application/pdf,.pdf" data-arquivo-importar>
      <span class="zona-arquivo-icone"><?= icone('upload') ?></span>
      <span>
        <strong data-zona-titulo><?= e(t('importar.zona_titulo')) ?></strong>
        <small><?= e(t('importar.zona_dica')) ?></small>
      </span>
    </label>

    <div class="importador-estado" data-importador-estado hidden role="status"><span class="carregando" aria-hidden="true"></span> <span data-estado-texto></span></div>
    <div id="importador-erro" data-importador-erro></div>

    <p class="importador-privacidade"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('importar.privacidade')) ?></p>
  </div>
</section>
