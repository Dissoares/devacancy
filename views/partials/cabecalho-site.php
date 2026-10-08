<?php $logado = usuario_sessao(); ?>
<header class="cabecalho" data-cabecalho>
  <div class="container cabecalho-interno">
    <?= parcial('partials/marca') ?>

    <nav class="navegacao" id="navegacao" aria-label="Principal">
      <a href="/#como-funciona">Como funciona</a>
      <a href="/#recursos">Recursos</a>
      <a href="/#empresas">Para empresas</a>
      <a href="/#perguntas">Dúvidas</a>
      <div class="navegacao-acoes-movel">
        <?php if ($logado): ?>
          <a class="botao botao-primario botao-bloco" href="<?= $logado['tipo'] === 'admin' ? '/admin' : '/painel' ?>">Meu painel</a>
        <?php else: ?>
          <a class="botao botao-secundario botao-bloco" href="/entrar">Entrar</a>
          <a class="botao botao-primario botao-bloco" href="/cadastro">Criar conta grátis</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="cabecalho-acoes">
      <?php if ($logado): ?>
        <a class="botao botao-primario botao-pequeno" href="<?= $logado['tipo'] === 'admin' ? '/admin' : '/painel' ?>">Meu painel <?= icone('seta', 'icone icone-seta') ?></a>
      <?php else: ?>
        <a class="botao botao-fantasma botao-pequeno" href="/entrar">Entrar</a>
        <a class="botao botao-claro botao-pequeno" href="/cadastro">Criar conta <?= icone('seta', 'icone icone-seta') ?></a>
      <?php endif; ?>
    </div>

    <button class="botao-menu" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="navegacao" data-botao-menu>
      <?= icone('menu', 'icone icone-abrir') ?><?= icone('x', 'icone icone-fechar') ?>
    </button>
  </div>
</header>
