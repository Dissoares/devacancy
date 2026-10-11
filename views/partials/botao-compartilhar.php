<?php
/**
 * Um botão só para todos os aparelhos: no celular abre a folha de compartilhamento do sistema (WhatsApp,
 * Instagram, Telegram...); no computador abre um menu com as redes. Ver js/componentes/compartilhar.js.
 * @var \App\Entities\Vaga $vaga @var string $classe
 */
$classe ??= 'botao-secundario botao-pequeno';
?>
<button class="botao <?= e($classe) ?> botao-compartilhar" type="button" data-compartilhar
        data-url="<?= e(url_vaga($vaga)) ?>"
        data-titulo="<?= e($vaga->titulo) ?>"
        data-texto="<?= e(t('compartilhar.texto', ['titulo' => $vaga->titulo])) ?>">
  <?= icone('compartilhar', 'icone icone-sm') ?>
  <span><?= e(t('compartilhar.botao')) ?></span>
</button>
