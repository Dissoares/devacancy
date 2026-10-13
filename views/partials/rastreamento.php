<?php
/**
 * Entrega ao navegador os códigos e os eventos de conversão da página. Nada é carregado aqui: o script
 * rastreamento.js pergunta sobre cookies e só depois carrega o Pixel da Meta e o Google Analytics.
 */
use App\Core\Rastreamento;

if (!Rastreamento::ativo()) {
    return;
}
?>
<script>window.DV_RASTREIO = <?= json_encode([
    'pixel' => Rastreamento::pixel(),
    'ga' => Rastreamento::analytics(),
    'eventos' => Rastreamento::eventosDaPagina(),
    'textos' => [
        'aviso' => t('cookies.aviso'),
        'aceitar' => t('cookies.aceitar'),
        'recusar' => t('cookies.recusar'),
        'politica' => t('cookies.politica'),
        'rotulo' => t('cookies.rotulo'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('js/componentes/rastreamento.js') ?>" defer></script>
