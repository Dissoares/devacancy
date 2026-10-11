<?php /** @var \App\Entities\Usuario $usuario */ ?>
<div class="auth-cartao perfil-cartao">
  <?php if ($usuario->cadastroCompleto): ?>
    <a class="voltar" href="/painel"><?= icone('chevron-esq', 'icone icone-sm') ?> <?= e(t('perfil.voltar_painel')) ?></a>
  <?php else: ?>
    <div class="etapas">
      <span><?= e(t('perfil.etapa')) ?></span>
      <div class="etapas-barra" style="--progresso: 75%"><span></span></div>
      <span><?= e(t('painel.seu_perfil')) ?></span>
    </div>
  <?php endif; ?>

  <div class="auth-cabecalho">
    <h1><?= e($usuario->cadastroCompleto ? t('painel.editar_perfil') : t('perfil.falta_pouco', ['nome' => $usuario->primeiroNome()])) ?></h1>
    <p>
      <?= $usuario->ehEmpresa()
          ? e(t('perfil.texto_empresa'))
          : e(t('perfil.texto_profissional')) ?>
    </p>
  </div>

  <?= parcial('profile/formulario', ['usuario' => $usuario, 'valores' => $valores, 'perfil' => $perfil, 'erros' => $erros]) ?>
</div>
