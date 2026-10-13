<?php /** @var \App\Entities\Usuario $usuario */ use App\Core\Idioma; ?>
<!DOCTYPE html>
<html lang="<?= Idioma::html() ?>">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/painel.css', 'paginas/assinatura.css']]) ?>
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

      <?php if ($usuario->ehAdmin()): ?>
        <nav class="barra-app-navegacao" aria-label="Administração">
          <a class="<?= ativo('/admin') ?>" href="/admin"><?= icone('usuarios', 'icone icone-sm') ?> Cadastros</a>
          <a class="<?= ativo('/admin/vagas') ?>" href="/admin/vagas"><?= icone('maleta', 'icone icone-sm') ?> Vagas</a>
          <a class="<?= ativo('/admin/candidaturas') ?>" href="/admin/candidaturas"><?= icone('raio', 'icone icone-sm') ?> Candidaturas</a>
          <a class="<?= ativo('/admin/comissoes') ?>" href="/admin/comissoes"><?= icone('usuarios', 'icone icone-sm') ?> Comissões</a>
          <a class="<?= ativo('/admin/pagamentos') ?>" href="/admin/pagamentos"><?= icone('cadeado', 'icone icone-sm') ?> Pagamentos</a>
          <a class="<?= ativo('/admin/analytics') ?>" href="/admin/analytics"><?= icone('grafico', 'icone icone-sm') ?> Analytics</a>
          <?php $sistema = array_filter(['/admin/configuracoes', '/admin/migracoes', '/admin/email', '/admin/administradores'], fn (string $rota) => ativo($rota) !== ''); ?>
          <a class="<?= $sistema ? 'ativo' : '' ?>" href="/admin/configuracoes"><?= icone('ajustes', 'icone icone-sm') ?> Configurações</a>
        </nav>
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
