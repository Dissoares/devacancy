<?php /** @var \App\Entities\Usuario[] $admins @var ?string $erro @var ?string $sucesso @var string $email @var \App\Entities\Usuario $usuario */ ?>
<section class="cabecalho-pagina">
  <div>
    <h1>Administradores</h1>
    <p>Quem pode entrar neste painel. Para trocar a conta padrão pela sua: crie a sua conta normalmente, promova-a aqui e depois exclua a padrão.</p>
  </div>
</section>

<?php if ($sucesso): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>
<?php if ($erro): ?>
  <div class="alerta alerta-erro alerta-topo" role="alert"><?= icone('x') ?><span><?= e($erro) ?></span></div>
<?php endif; ?>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Último acesso</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($admins as $admin): ?>
          <tr>
            <td><strong class="texto-celula"><?= e($admin->nome) ?></strong></td>
            <td><?= e($admin->email) ?></td>
            <td class="texto-suave"><?= $admin->ultimoAcessoEm ? data_br($admin->ultimoAcessoEm, true) : 'nunca' ?></td>
            <td>
              <?php if ($admin->id === $usuario->id): ?>
                <span class="selo selo-neutro">Você</span>
              <?php elseif (count($admins) > 1): ?>
                <form method="post" action="/admin/usuarios/<?= $admin->id ?>/excluir"
                      onsubmit="return confirm('Excluir o administrador <?= e(addslashes($admin->email)) ?>? Isso não pode ser desfeito.')">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="voltar" value="admins">
                  <button class="botao botao-secundario botao-pequeno" type="submit">Excluir</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="rodape-tabela-acao">
    <form class="formulario-linha" method="post" action="/admin/administradores">
      <?= csrf_campo() ?>
      <label class="campo-rotulo" for="email-admin">Tornar administrador (e-mail de um cadastro existente)</label>
      <div class="acoes-cabecalho">
        <input class="entrada" id="email-admin" name="email" type="email" value="<?= e($email) ?>" placeholder="voce@email.com" required>
        <button class="botao botao-primario" type="submit"><?= icone('escudo', 'icone icone-sm') ?> Promover</button>
      </div>
    </form>
  </div>
</section>
