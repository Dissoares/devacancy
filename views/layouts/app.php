<?php /** @var \App\Entities\Usuario $usuario */ use App\Core\Idioma; ?>
<!DOCTYPE html>
<html lang="<?= Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/painel.css', 'paginas/assinatura.css', 'paginas/perfil.css']]) ?>
  <?php if ($usuario->ehAdmin()): ?>
    <script src="<?= asset('js/componentes/formularios.js') ?>" defer></script>
    <script src="<?= asset('js/paginas/admin.js') ?>" defer></script>
  <?php else: ?>
    <script src="<?= asset('js/paginas/assinatura.js') ?>" defer></script>
  <?php endif; ?>
</head>
<body class="pagina-app">
  <?= parcial('partials/icones') ?>

  <header class="barra-app">
    <div class="container-app barra-app-interno">
      <?= parcial('partials/marca') ?>

      <?php if ($usuario->ehProfissional()): ?>
        <nav class="barra-app-navegacao" aria-label="Principal">
          <a class="<?= ativo('/painel') ?>" href="/painel"><?= icone('maleta', 'icone icone-sm') ?> <?= e(t('nav.vagas')) ?></a>
          <a class="<?= ativo('/perfil') ?>" href="/perfil"><?= icone('usuario', 'icone icone-sm') ?> <?= e(t('perfilpg.meu_perfil')) ?></a>
        </nav>
      <?php endif; ?>

      <?php if ($usuario->ehAdmin()): ?>
        <a class="botao botao-secundario botao-pequeno" href="/admin"><?= icone('ajustes', 'icone icone-sm') ?> Painel admin</a>
      <?php endif; ?>

      <div class="menu-usuario">
        <?= parcial('partials/seletor-idioma') ?>
        <span class="avatar">
          <?php if ($usuario->avatarUrl): ?>
            <img src="<?= e($usuario->avatarUrl) ?>" alt="" referrerpolicy="no-referrer">
          <?php else: ?>
            <?= e(iniciais($usuario->nome)) ?>
          <?php endif; ?>
        </span>
        <span class="menu-usuario-nome">
          <strong><?= e($usuario->nome) ?></strong>
          <small><?= e($usuario->tipo->rotulo()) ?></small>
        </span>
        <form method="post" action="/sair">
          <?= csrf_campo() ?>
          <button class="botao botao-fantasma botao-pequeno" type="submit" title="<?= e(t('nav.sair')) ?>"><?= icone('sair', 'icone icone-sm') ?><span class="ocultar-movel"><?= e(t('nav.sair')) ?></span></button>
        </form>
      </div>
    </div>
  </header>

  <main class="container-app conteudo-app">
    <?= $conteudo ?>
  </main>
</body>
</html>
