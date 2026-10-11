<?php
/** @var ?string $erro @var ?array{executadas: string[], erro: ?array{arquivo: string, mensagem: string}} $resultado */
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
      <p class="texto-suave">Por segurança, <strong>remova <code>MIGRACOES_TOKEN</code> do <code>.env</code></strong> agora: sem ele esta página deixa de existir.</p>
    <?php else: ?>
      <form class="formulario" method="post" action="/install" autocomplete="off">
        <?= csrf_campo() ?>
        <div class="campo">
          <label class="campo-rotulo" for="token">Token</label>
          <input class="entrada" id="token" name="token" type="password" autocomplete="off" required autofocus>
        </div>
        <button class="botao botao-primario botao-grande" type="submit">Rodar migrações pendentes</button>
      </form>
    <?php endif; ?>
  </div>
</section>
