<?php $tipoLateral ??= 'profissional'; ?>
<!DOCTYPE html>
<html lang="<?= \App\Core\Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/autenticacao.css']]) ?>
  <script src="<?= asset('js/componentes/formularios.js') ?>" defer></script>
</head>
<body class="pagina-auth" data-tipo="<?= e($tipoLateral) ?>">
  <?= parcial('partials/icones') ?>

  <div class="auth">
    <aside class="auth-lateral" aria-hidden="true">
      <div class="auth-lateral-fundo"></div>
      <?= parcial('partials/marca') ?>

      <div class="auth-lateral-conteudo somente-profissional">
        <span class="selo selo-sucesso selo-ponto"><?= e(t('auth.lateral_selo')) ?></span>
        <h2><?= e(t('marca.slogan_1')) ?><br><span class="texto-gradiente"><?= e(t('marca.slogan_2')) ?></span></h2>
        <ul class="auth-beneficios">
          <li><span><?= icone('alvo', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.beneficio_1')) ?></strong><?= e(t('auth.beneficio_1_texto')) ?></div></li>
          <li><span><?= icone('raio', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.beneficio_2')) ?></strong><?= e(t('auth.beneficio_2_texto')) ?></div></li>
          <li><span><?= icone('grafico', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.beneficio_3')) ?></strong><?= e(t('auth.beneficio_3_texto')) ?></div></li>
        </ul>
      </div>

      <div class="auth-lateral-conteudo somente-empresa">
        <span class="selo selo-primario selo-ponto"><?= e(t('landing.empresas_sobretitulo')) ?></span>
        <h2><?= e(t('auth.empresa_titulo_1')) ?><br><span class="texto-gradiente"><?= e(t('auth.empresa_titulo_2')) ?></span></h2>
        <ul class="auth-beneficios">
          <li><span><?= icone('codigo', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.empresa_beneficio_1')) ?></strong><?= e(t('auth.empresa_beneficio_1_texto')) ?></div></li>
          <li><span><?= icone('camadas', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.empresa_beneficio_2')) ?></strong><?= e(t('auth.empresa_beneficio_2_texto')) ?></div></li>
          <li><span><?= icone('brilho', 'icone icone-sm') ?></span><div><strong><?= e(t('auth.empresa_beneficio_3')) ?></strong><?= e(t('auth.empresa_beneficio_3_texto')) ?></div></li>
        </ul>
      </div>

      <p class="auth-lateral-rodape"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('auth.nunca_senha')) ?></p>
    </aside>

    <main class="auth-principal">
      <div class="auth-topo-movel"><?= parcial('partials/marca') ?><?= parcial('partials/seletor-idioma') ?></div>
      <?= $conteudo ?>
    </main>
  </div>
</body>
</html>
