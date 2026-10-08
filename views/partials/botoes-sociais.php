<?php
/** @var string $origem 'cadastro' ou 'entrar' @var string $tipo */
use App\Enums\ProvedorSocial;

$verbo = $origem === 'entrar' ? 'Entrar' : 'Continuar';
?>
<div class="botoes-sociais">
  <?php foreach (ProvedorSocial::cases() as $provedor): ?>
    <a class="botao-social"
       href="/auth/<?= e($provedor->value) ?>?tipo=<?= e($tipo) ?>&amp;origem=<?= e($origem) ?>"
       data-provedor="<?= e($provedor->value) ?>"
       <?= $provedor === ProvedorSocial::Github ? 'data-somente="profissional"' : '' ?>>
      <?= icone($provedor->value) ?> <?= $verbo ?> com <?= e($provedor->rotulo()) ?>
    </a>
  <?php endforeach; ?>
</div>
