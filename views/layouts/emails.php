<?php
/**
 * Moldura de todos os e-mails do sistema. Só HTML de tabela e estilo inline, que é o que os programas de e-mail entendem.
 * @var string $assunto @var string $previa @var string $conteudo
 */
$marca = e((string) configuracao('email.nome'));
$logo = e(url('/assets/img/icone-192.png'));
$site = e(url('/'));
?>
<!DOCTYPE html>
<html lang="<?= e(\App\Core\Idioma::html()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= e($assunto) ?></title>
</head>
<body style="margin:0;padding:0;background:#eef2f8;-webkit-text-size-adjust:100%">
  <!-- Texto de pré-visualização que aparece ao lado do assunto na caixa de entrada. -->
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#eef2f8"><?= e($previa) ?>&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f8">
    <tr>
      <td align="center" style="padding:28px 14px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">
          <tr>
            <td style="padding:0 4px 16px">
              <a href="<?= $site ?>" style="text-decoration:none">
                <img src="<?= $logo ?>" width="34" height="34" alt="" style="vertical-align:middle;border:0;border-radius:9px">
                <span style="vertical-align:middle;margin-left:8px;font-family:Arial,Helvetica,sans-serif;font-size:19px;font-weight:bold;color:#0b1730;letter-spacing:-.4px">dev<span style="color:#64748b">acancy</span></span>
              </a>
            </td>
          </tr>
          <tr>
            <td style="background:#ffffff;border-radius:16px;border:1px solid #dbe3f0;overflow:hidden">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="height:6px;background:#12266b;font-size:0;line-height:0">&nbsp;</td></tr>
                <tr>
                  <td style="padding:34px 34px 30px;font-family:Arial,Helvetica,sans-serif;color:#334155;font-size:15px;line-height:1.65">
                    <?= $conteudo ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#64748b;text-align:center">
              <?= e(t('email.rodape_motivo', ['app' => (string) configuracao('email.nome')])) ?><br>
              <?= e(t('email.rodape_ajuda')) ?>
              <a href="<?= $site ?>" style="color:#475569"><?= e(preg_replace('#^https?://#', '', rtrim(endereco_do_site(), '/'))) ?></a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
