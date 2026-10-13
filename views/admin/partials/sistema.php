<?php /** Abas das telas de sistema do admin (todas ficam sob "Configurações" no menu). */ ?>
<nav class="abas-sistema" aria-label="Sistema">
  <a class="<?= ativo('/admin/configuracoes') ?>" href="/admin/configuracoes"><?= icone('ajustes', 'icone icone-sm') ?> Configurações</a>
  <a class="<?= ativo('/admin/email') ?>" href="/admin/email"><?= icone('email', 'icone icone-sm') ?> Teste de e-mail</a>
  <a class="<?= ativo('/admin/administradores') ?>" href="/admin/administradores"><?= icone('escudo', 'icone icone-sm') ?> Administradores</a>
  <a class="<?= ativo('/admin/migracoes') ?>" href="/admin/migracoes"><?= icone('raio', 'icone icone-sm') ?> Migrações</a>
</nav>
