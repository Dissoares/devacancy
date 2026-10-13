<?php
/**
 * E-mail depois do pagamento confirmado: tranquiliza (agora é com a equipe), mostra o comprovante e o que vem a seguir.
 * @var string $nome @var bool $renovacao @var string $plano @var string $valor @var string $forma @var string $data
 * @var string $validoAte @var ?string $pedido
 */
$linhas = [
    t('email.ass_plano') => $plano,
    t('email.ass_valor') => $valor,
    t('email.ass_forma') => $forma,
    t('email.ass_data') => $data,
    t('email.ass_valido_ate') => $validoAte,
];
if ($pedido) {
    $linhas[t('email.ass_pedido')] = $pedido;
}
$passos = [t('email.ass_passo_1'), t('email.ass_passo_2'), t('email.ass_passo_3')];
?>
<h1 style="margin:0 0 14px;font-size:24px;line-height:1.25;color:#0b1730;letter-spacing:-.4px"><?= e(t($renovacao ? 'email.ass_titulo_renovacao' : 'email.ass_titulo', ['nome' => $nome])) ?></h1>
<p style="margin:0 0 18px"><?= e(t($renovacao ? 'email.ass_texto_renovacao' : 'email.ass_texto', ['plano' => $plano])) ?></p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;background:#f6f8fc;border:1px solid #dbe3f0;border-radius:12px">
  <tr><td style="padding:14px 18px 4px;font-size:12px;font-weight:bold;letter-spacing:.8px;color:#64748b;text-transform:uppercase"><?= e(t('email.ass_comprovante')) ?></td></tr>
  <tr>
    <td style="padding:0 18px 10px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <?php foreach ($linhas as $rotulo => $valorLinha): ?>
          <tr>
            <td style="padding:7px 0;border-top:1px solid #e3e9f4;font-size:14px;color:#64748b"><?= e($rotulo) ?></td>
            <td align="right" style="padding:7px 0;border-top:1px solid #e3e9f4;font-size:14px;font-weight:bold;color:#0b1730"><?= e($valorLinha) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </td>
  </tr>
</table>

<?php if (!$renovacao): ?>
  <p style="margin:0 0 10px;font-weight:bold;color:#0b1730"><?= e(t('email.ass_agora')) ?></p>
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
    <?php foreach ($passos as $indice => $passo): ?>
      <tr>
        <td width="34" valign="top" style="padding:3px 0 9px">
          <span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#12266b;color:#ffffff;font-size:13px;font-weight:bold"><?= $indice + 1 ?></span>
        </td>
        <td valign="top" style="padding:3px 0 9px"><?= e($passo) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?= \App\Core\Visao::parcial('emails/partials/botao', ['href' => url('/painel'), 'rotulo' => t('email.ass_botao')]) ?>
<p style="margin:14px 0 0;font-size:13px;color:#64748b"><?= e(t('email.ass_nota')) ?></p>
