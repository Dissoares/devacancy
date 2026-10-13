<?php
/** @var string $origem 'cadastro', 'entrar' ou 'funil' @var string $tipo */
use App\Enums\ProvedorSocial;

$verbo = $origem === 'entrar' ? 'auth.entrar_com' : 'auth.continuar_com';
?>
<?php $provedores = provedores_sociais(); ?>
<?php if ($provedores): ?>
<div class="botoes-sociais">
  <?php foreach ($provedores as $provedor): ?>
    <a class="botao-social"
       href="/auth/<?= e($provedor->value) ?>?tipo=<?= e($tipo) ?>&amp;origem=<?= e($origem) ?>"
       data-provedor="<?= e($provedor->value) ?>"
       <?= $provedor === ProvedorSocial::Github ? 'data-somente="profissional"' : '' ?>>
      <?= icone($provedor->value) ?> <?= e(t($verbo, ['provedor' => $provedor->rotulo()])) ?>
    </a>
  <?php endforeach; ?>
</div>
<div class="divisor"><?= e(t('auth.ou_email')) ?></div>
<?php endif; ?>
