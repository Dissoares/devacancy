<?php /** @var \App\Entities\Usuario $usuario */ ?>
<!DOCTYPE html>
<html lang="<?= \App\Core\Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/painel.css', 'paginas/assinatura.css', 'paginas/admin.css']]) ?>
  <meta name="robots" content="noindex, nofollow">
  <script src="<?= asset('js/componentes/formularios.js') ?>" defer></script>
  <script src="<?= asset('js/paginas/admin.js') ?>" defer></script>
  <script src="<?= asset('js/paginas/admin-shell.js') ?>" defer></script>
</head>
<body class="pagina-admin">
  <?= parcial('partials/icones') ?>

  <div class="admin" data-admin>
    <aside class="admin-lateral" id="admin-lateral">
      <div class="admin-lateral-topo">
        <?= parcial('partials/marca') ?>
        <span class="selo selo-primario">Admin</span>
      </div>

      <?= parcial('admin/partials/menu') ?>

      <div class="admin-lateral-rodape">
        <a class="admin-menu-item" href="/" target="_blank" rel="noopener"><?= icone('externo', 'icone icone-sm') ?><span>Ver o site</span></a>
        <div class="admin-usuario">
          <span class="avatar">
            <?php if ($usuario->avatarUrl): ?><img src="<?= e($usuario->avatarUrl) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= e(iniciais($usuario->nome)) ?><?php endif; ?>
          </span>
          <span class="admin-usuario-nome"><strong><?= e($usuario->nome) ?></strong><small><?= e($usuario->email) ?></small></span>
          <form method="post" action="/sair">
            <?= csrf_campo() ?>
            <button class="botao botao-fantasma botao-pequeno" type="submit" title="Sair" aria-label="Sair"><?= icone('sair', 'icone icone-sm') ?></button>
          </form>
        </div>
      </div>
    </aside>

    <div class="admin-fundo" data-admin-fechar hidden></div>

    <div class="admin-principal">
      <header class="admin-topo">
        <button class="botao botao-secundario botao-pequeno admin-abrir" type="button" data-admin-abrir aria-controls="admin-lateral" aria-expanded="false" aria-label="Abrir menu">
          <?= icone('menu', 'icone icone-sm') ?>
        </button>
        <?= parcial('partials/marca') ?>
      </header>

      <main class="admin-conteudo" id="conteudo">
        <?= $conteudo ?>
      </main>
    </div>
  </div>
</body>
</html>
