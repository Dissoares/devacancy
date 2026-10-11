<?php
/** @var ?string $erro @var ?string $admin @var ?string[] $diagnostico @var ?array{executadas: string[], erro: ?array{arquivo: string, mensagem: string}} $resultado */
?>
<section class="secao">
  <div class="container instalador">
    <h1>Migrações do banco</h1>
    <p class="texto-suave">Informe o token definido em <code>MIGRACOES_TOKEN</code> no <code>.env</code> para aplicar as migrações pendentes.</p>

    <?php if ($erro): ?>
      <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><?= e($erro) ?></span></div>
    <?php endif; ?>

    <?php if ($resultado): ?>
      <?php if ($resultado['erro']): ?>
        <div class="alerta alerta-erro" role="alert">
          <?= icone('x') ?>
          <span>
            <strong>Falhou em <?= e($resultado['erro']['arquivo']) ?>.</strong> <?= e($resultado['erro']['mensagem']) ?>
            <?= $resultado['executadas'] ? '(Aplicadas antes: ' . e(implode(', ', $resultado['executadas'])) . '.)' : '' ?>
          </span>
        </div>
      <?php elseif ($resultado['executadas']): ?>
        <div class="alerta alerta-sucesso"><?= icone('check') ?><span><?= count($resultado['executadas']) ?> migração(ões) aplicada(s): <?= e(implode(', ', $resultado['executadas'])) ?>.</span></div>
      <?php else: ?>
        <div class="alerta alerta-sucesso"><?= icone('check') ?><span>Nada a fazer: o banco já está atualizado.</span></div>
      <?php endif; ?>
      <?php if ($admin): ?>
        <div class="alerta <?= str_starts_with($admin, 'Administrador pronto') ? 'alerta-sucesso' : 'alerta-erro' ?>"><?= icone('check') ?><span><?= e($admin) ?></span></div>
      <?php endif; ?>
      <?php if ($diagnostico): ?>
        <div class="instalador-diagnostico">
          <strong>Diagnóstico do login</strong>
          <ul>
            <?php foreach ($diagnostico as $linha): ?><li><?= e($linha) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <p class="texto-suave">Por segurança, <strong>remova <code>MIGRACOES_TOKEN</code> do <code>.env</code></strong> agora: sem ele esta página deixa de existir.</p>
    <?php else: ?>
      <form class="formulario" method="post" action="/install" autocomplete="off">
        <?= csrf_campo() ?>
        <div class="campo">
          <label class="campo-rotulo" for="token">Token</label>
          <input class="entrada" id="token" name="token" type="password" autocomplete="off" required autofocus>
        </div>
        <fieldset class="instalador-admin">
          <legend>Administrador (opcional)</legend>
          <p class="texto-suave">Preencha para criar o administrador ou, se o e-mail já tiver conta (por exemplo criada pelo Google), torná-la administradora com esta senha.</p>
          <div class="campo">
            <label class="campo-rotulo" for="admin_email">E-mail do administrador</label>
            <input class="entrada" id="admin_email" name="admin_email" type="email" autocomplete="off">
          </div>
          <div class="campo">
            <label class="campo-rotulo" for="admin_senha">Senha (mínimo 8 caracteres)</label>
            <input class="entrada" id="admin_senha" name="admin_senha" type="password" autocomplete="new-password" minlength="8">
          </div>
        </fieldset>
        <fieldset class="instalador-admin">
          <legend>Diagnosticar login (opcional, não altera nada)</legend>
          <p class="texto-suave">Digite o e-mail e a senha que você usa no login para ver onde a verificação falha.</p>
          <div class="campo">
            <label class="campo-rotulo" for="diag_email">E-mail</label>
            <input class="entrada" id="diag_email" name="diag_email" type="email" autocomplete="off">
          </div>
          <div class="campo">
            <label class="campo-rotulo" for="diag_senha">Senha</label>
            <input class="entrada" id="diag_senha" name="diag_senha" type="password" autocomplete="off">
          </div>
        </fieldset>
        <button class="botao botao-primario botao-grande" type="submit">Rodar migrações e salvar</button>
      </form>
    <?php endif; ?>
  </div>
</section>
