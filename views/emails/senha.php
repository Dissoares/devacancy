<?php
/**
 * E-mail de recuperação de senha.
 * @var string $nome @var string $link
 */
?>
<h1 style="margin:0 0 14px;font-size:24px;line-height:1.25;color:#0b1730;letter-spacing:-.4px"><?= e(t('email.senha_titulo')) ?></h1>
<p style="margin:0 0 6px"><?= e(t('email.senha_ola', ['nome' => $nome])) ?></p>
<p style="margin:0 0 6px"><?= e(t('recuperar.email_corpo')) ?></p>

<?= \App\Core\Visao::parcial('emails/partials/botao', ['href' => $link, 'rotulo' => t('recuperar.email_botao')]) ?>

<p style="margin:16px 0 0;font-size:13px;color:#64748b"><?= e(t('recuperar.email_aviso')) ?></p>
<p style="margin:14px 0 0;font-size:12px;color:#94a3b8;word-break:break-all"><?= e(t('email.senha_link_alternativo')) ?><br><a href="<?= e($link) ?>" style="color:#475569"><?= e($link) ?></a></p>
