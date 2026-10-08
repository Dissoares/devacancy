<?php /** @var \App\Entities\Usuario $usuario */ ?>
<div class="auth-cartao perfil-cartao">
  <?php if ($usuario->cadastroCompleto): ?>
    <a class="voltar" href="/painel"><?= icone('chevron-esq', 'icone icone-sm') ?> Voltar ao painel</a>
  <?php else: ?>
    <div class="etapas">
      <span>Etapa 2 de 2</span>
      <div class="etapas-barra" style="--progresso: 75%"><span></span></div>
      <span>Seu perfil</span>
    </div>
  <?php endif; ?>

  <div class="auth-cabecalho">
    <h1><?= $usuario->cadastroCompleto ? 'Editar perfil' : 'Falta pouco, ' . e($usuario->primeiroNome()) . '!' ?></h1>
    <p>
      <?= $usuario->ehEmpresa()
          ? 'Conte sobre a sua empresa e os perfis que vocês contratam.'
          : 'Conte o que você procura. É com isso que encontramos as vagas certas para você.' ?>
    </p>
  </div>

  <?= parcial('profile/formulario', ['usuario' => $usuario, 'valores' => $valores, 'perfil' => $perfil, 'erros' => $erros]) ?>
</div>
