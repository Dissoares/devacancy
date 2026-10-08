<?php /** @var \App\Entities\Usuario $usuario @var array $valores @var array $erros */ ?>
<form id="formulario-perfil" class="formulario" method="post" action="/completar-perfil" enctype="multipart/form-data"
      hx-post="/completar-perfil" hx-encoding="multipart/form-data" hx-target="this" hx-swap="outerHTML" novalidate>
  <?= csrf_campo() ?>

  <?php if ($erros !== []): ?>
    <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span>Revise os campos destacados abaixo.</span></div>
  <?php endif; ?>

  <?= $usuario->ehEmpresa()
      ? parcial('profile/campos-empresa', ['valores' => $valores, 'erros' => $erros])
      : parcial('profile/campos-profissional', ['valores' => $valores, 'erros' => $erros, 'perfil' => $perfil]) ?>

  <div class="rodape-formulario">
    <p><?= icone('cadeado', 'icone icone-sm') ?> Seus dados ficam privados e protegidos.</p>
    <button class="botao botao-primario botao-grande" type="submit">
      <span class="carregando" aria-hidden="true"></span>
      <span class="rotulo-botao"><?= $usuario->cadastroCompleto ? 'Salvar alterações' : 'Concluir cadastro' ?></span>
      <?= icone('seta', 'icone icone-seta') ?>
    </button>
  </div>
</form>
