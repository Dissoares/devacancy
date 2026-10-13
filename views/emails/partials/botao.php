<?php
/**
 * Botão de ação do e-mail (laranja, igual ao do site).
 * @var string $href @var string $rotulo
 */
?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 8px">
  <tr>
    <td style="border-radius:10px;background:#ea580c">
      <a href="<?= e($href) ?>" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px"><?= e($rotulo) ?> &rarr;</a>
    </td>
  </tr>
</table>
