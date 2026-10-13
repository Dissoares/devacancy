<?php
/** @var array $config @var ?array{ok: bool, para: string, erro: ?string} $teste */
$linhas = [
  'Modo de envio' => $config['modo'],
  'Remetente (MAIL_DE)' => $config['de'],
  'Servidor (MAIL_SMTP_HOST)' => $config['host'] ?: '— (vazio)',
  'Porta / segurança' => $config['porta'] . ' / ' . ($config['seguranca'] ?: '—'),
  'Verificar certificado' => $config['verificar'],
  'Usuário (MAIL_SMTP_USUARIO)' => $config['usuario'] ?: '— (vazio)',
  'Senha (MAIL_SMTP_SENHA)' => $config['senha_tamanho'] > 0 ? $config['senha_tamanho'] . ' caracteres' : '— (vazia)',
  'APP_URL (usado nos links)' => $config['url'],
];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>E-mail</h1>
    <p>Confira o que o servidor leu do .env e envie um e-mail de teste para você.</p>
  </div>
</section>

<?= parcial('admin/partials/sistema') ?>

<?php if ($teste): ?>
  <?php if ($teste['ok']): ?>
    <div class="alerta alerta-sucesso"><?= icone('check') ?><span>O servidor aceitou o e-mail para <?= e($teste['para']) ?>. Confira a caixa de entrada e o spam.</span></div>
  <?php else: ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span><strong>Falhou:</strong> <?= e($teste['erro'] ?? 'erro desconhecido') ?></span></div>
  <?php endif; ?>
<?php endif; ?>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela">
      <tbody>
        <?php foreach ($linhas as $rotulo => $valor): ?>
          <tr><td class="texto-suave"><?= e($rotulo) ?></td><td><span class="texto-celula mono"><?= e((string) $valor) ?></span></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="rodape-tabela-acao">
    <?php if ($config['usuario'] !== '' && !$config['usuario_completo']): ?>
      <p class="texto-suave">⚠ O usuário SMTP deve ser o e-mail completo (com @).</p>
    <?php endif; ?>
    <?php if ($config['senha_suspeita']): ?>
      <p class="texto-suave">⚠ A senha tem aspas ou espaços; o .env remove aspas das pontas e isso pode alterá-la.</p>
    <?php endif; ?>
    <form method="post" action="/admin/email/testar">
      <?= csrf_campo() ?>
      <button class="botao botao-primario" type="submit"><?= icone('email', 'icone icone-sm') ?> Enviar e-mail de teste para mim</button>
    </form>
  </div>
</section>
