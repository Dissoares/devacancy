<?php
/**
 * E-mail logo depois do cadastro: confirma a conta e leva a pessoa de volta para ver as vagas (a ação que gera a assinatura).
 * @var string $nome @var ?int $vagas vagas que já combinam com o perfil, quando dá para saber
 */
$passos = [t('email.boas_passo_1'), t('email.boas_passo_2'), t('email.boas_passo_3')];
?>
<h1 style="margin:0 0 14px;font-size:24px;line-height:1.25;color:#0b1730;letter-spacing:-.4px"><?= e(t('email.boas_titulo', ['nome' => $nome])) ?></h1>
<p style="margin:0 0 18px"><?= e(t('email.boas_texto')) ?></p>

<?php if ($vagas !== null && $vagas > 0): ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px">
    <tr>
      <td style="background:#eef3ff;border:1px solid #cbd9f7;border-radius:12px;padding:16px 18px;color:#12266b">
        <span style="font-size:30px;font-weight:bold;line-height:1;letter-spacing:-1px"><?= e(numero($vagas)) ?></span>
        <span style="font-size:15px;font-weight:bold"> <?= e(t($vagas === 1 ? 'email.boas_vagas_um' : 'email.boas_vagas_varios')) ?></span>
        <div style="margin-top:4px;font-size:13px;color:#475569"><?= e(t('email.boas_vagas_nota')) ?></div>
      </td>
    </tr>
  </table>
<?php endif; ?>

<p style="margin:0 0 10px;font-weight:bold;color:#0b1730"><?= e(t('email.boas_como')) ?></p>
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

<?= \App\Core\Visao::parcial('emails/partials/botao', ['href' => url('/painel'), 'rotulo' => t('email.boas_botao')]) ?>
<p style="margin:14px 0 0;font-size:13px;color:#64748b"><?= e(t('email.boas_nota')) ?></p>
