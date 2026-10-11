<?php /** @var int $total @var array $valores @var array $erros @var ?string $erro */ ?>
<section class="criar-conta cartao">
  <span class="selo selo-sucesso selo-ponto"><?= e(t('funil.conta_selo')) ?></span>
  <h1>
    <?= $total > 0
        ? t('funil.conta_titulo', ['total' => '<span class="texto-gradiente">' . e(t($total === 1 ? 'funil.vagas_encontradas_um' : 'funil.vagas_encontradas_varios', ['n' => $total])) . '</span>'])
        : e(t('funil.conta_titulo_sem_vagas')) ?>
  </h1>
  <p class="texto-suave"><?= e(t('funil.conta_texto')) ?></p>

  <?php if (!empty($erro)): ?>
    <div class="alerta alerta-aviso"><?= icone('relogio') ?><span><?= e($erro) ?></span></div>
  <?php endif; ?>

  <?= parcial('partials/botoes-sociais', ['origem' => 'funil', 'tipo' => 'profissional']) ?>

  <div class="divisor"><?= e(t('auth.ou_email')) ?></div>

  <?= parcial('funil/formulario-conta', ['valores' => $valores, 'erros' => $erros]) ?>

  <p class="auth-rodape"><?= e(t('auth.ja_tem_conta')) ?> <a href="/entrar"><?= e(t('auth.entrar')) ?></a></p>
</section>
