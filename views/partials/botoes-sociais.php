<?php
/** @var string $origem 'cadastro', 'entrar' ou 'funil' @var string $tipo */
use App\Enums\ProvedorSocial;

$verbo = $origem === 'entrar' ? 'auth.entrar_com' : 'auth.continuar_com';
?>
<div class="botoes-sociais">
  <?php foreach (ProvedorSocial::cases() as $provedor): ?>
    <a class="botao-social"
       href="/auth/<?= e($provedor->value) ?>?tipo=<?= e($tipo) ?>&amp;origem=<?= e($origem) ?>"
       data-provedor="<?= e($provedor->value) ?>"
       <?= $provedor === ProvedorSocial::Github ? 'data-somente="profissional"' : '' ?>>
      <?= icone($provedor->value) ?> <?= e(t($verbo, ['provedor' => $provedor->rotulo()])) ?>
    </a>
  <?php endforeach; ?>
</div>
