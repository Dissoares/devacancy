<?php
/** @var \App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional|\App\Entities\PerfilEmpresa|null $perfil */
use App\Entities\PerfilEmpresa;
use App\Entities\PerfilProfissional;

$telefone = preg_replace('/\D/', '', (string) $usuario->telefone);
$linkWhatsapp = $telefone ? 'https://wa.me/' . (strlen($telefone) <= 11 ? '55' . $telefone : $telefone) : null;
$link = fn (?string $url, string $rotulo): string => $url
    ? '<a class="link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($rotulo) . ' ' . icone('externo', 'icone icone-sm') . '</a>'
    : '<span class="texto-suave">—</span>';
?>
<header class="gaveta-cabecalho">
  <span class="avatar avatar-grande">
    <?php if ($usuario->avatarUrl): ?><img src="<?= e($usuario->avatarUrl) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= e(iniciais($usuario->nome)) ?><?php endif; ?>
  </span>
  <div>
    <h2><?= e($usuario->nome) ?></h2>
    <p><?= e($usuario->email) ?></p>
    <div class="gaveta-selos">
      <span class="selo <?= $usuario->ehEmpresa() ? 'selo-primario' : 'selo-neutro' ?>"><?= e($usuario->tipo->rotulo()) ?></span>
      <?php if (!$usuario->cadastroCompleto): ?><span class="selo selo-alerta selo-ponto">Perfil incompleto</span><?php endif; ?>
    </div>
  </div>
  <button class="botao-fechar" type="button" aria-label="Fechar" data-fechar-gaveta><?= icone('x') ?></button>
</header>

<div class="gaveta-acoes">
  <?php if ($linkWhatsapp): ?>
    <a class="botao botao-primario botao-pequeno" href="<?= e($linkWhatsapp) ?>" target="_blank" rel="noopener"><?= icone('whatsapp', 'icone icone-sm') ?> WhatsApp</a>
  <?php endif; ?>
  <a class="botao botao-secundario botao-pequeno" href="mailto:<?= e($usuario->email) ?>"><?= icone('email', 'icone icone-sm') ?> E-mail</a>
  <?php if ($perfil instanceof PerfilProfissional && $perfil->possuiCurriculo()): ?>
    <a class="botao botao-secundario botao-pequeno" href="/admin/usuarios/<?= $usuario->id ?>/curriculo" target="_blank"><?= icone('arquivo', 'icone icone-sm') ?> Currículo</a>
  <?php endif; ?>
</div>

<?php if ($perfil instanceof PerfilProfissional): ?>
  <section class="gaveta-secao">
    <h3>Perfil profissional</h3>
    <dl class="lista-dados">
      <div><dt>Cargo desejado</dt><dd><?= e($perfil->cargoDesejado) ?></dd></div>
      <div><dt>Área</dt><dd><?= e($perfil->area->rotulo()) ?></dd></div>
      <div><dt>Senioridade</dt><dd><?= e($perfil->senioridade->rotulo()) ?></dd></div>
      <div><dt>Experiência</dt><dd><?= (int) $perfil->anosExperiencia ?> anos</dd></div>
      <div><dt>Modelo</dt><dd><?= e($perfil->modeloTrabalho->rotulo()) ?></dd></div>
      <div><dt>Cidade</dt><dd><?= e($perfil->cidade ?? '—') ?></dd></div>
      <div><dt>Pretensão</dt><dd><?= $perfil->pretensaoSalarial ? 'R$ ' . number_format($perfil->pretensaoSalarial, 0, ',', '.') : '—' ?></dd></div>
    </dl>
  </section>
  <section class="gaveta-secao">
    <h3>Stack</h3>
    <div class="lista-etiquetas"><?php foreach ($perfil->stack as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?></div>
  </section>
  <section class="gaveta-secao">
    <h3>Links</h3>
    <dl class="lista-dados">
      <div><dt>LinkedIn</dt><dd><?= $link($perfil->linkedinUrl, 'Abrir perfil') ?></dd></div>
      <div><dt>GitHub</dt><dd><?= $link($perfil->githubUrl, 'Abrir perfil') ?></dd></div>
      <div><dt>Portfólio</dt><dd><?= $link($perfil->portfolioUrl, 'Abrir site') ?></dd></div>
    </dl>
  </section>
  <?php if ($perfil->resumo): ?>
    <section class="gaveta-secao">
      <h3>Observações</h3>
      <p class="texto-longo"><?= nl2br(e($perfil->resumo)) ?></p>
    </section>
  <?php endif; ?>
<?php elseif ($perfil instanceof PerfilEmpresa): ?>
  <section class="gaveta-secao">
    <h3>Empresa</h3>
    <dl class="lista-dados">
      <div><dt>Nome</dt><dd><?= e($perfil->nomeEmpresa) ?></dd></div>
      <div><dt>Site</dt><dd><?= $link($perfil->site, 'Abrir site') ?></dd></div>
      <div><dt>Tamanho</dt><dd><?= e($perfil->tamanho->rotulo()) ?></dd></div>
      <div><dt>Contato</dt><dd><?= e($perfil->cargoContato) ?></dd></div>
      <div><dt>Vagas abertas</dt><dd><?= (int) $perfil->vagasAbertas ?></dd></div>
    </dl>
  </section>
  <section class="gaveta-secao">
    <h3>Stacks que contrata</h3>
    <div class="lista-etiquetas"><?php foreach ($perfil->stacksContratacao as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?></div>
  </section>
  <?php if ($perfil->mensagem): ?>
    <section class="gaveta-secao">
      <h3>Mensagem</h3>
      <p class="texto-longo"><?= nl2br(e($perfil->mensagem)) ?></p>
    </section>
  <?php endif; ?>
<?php else: ?>
  <section class="gaveta-secao">
    <p class="texto-suave">Este cadastro ainda não preencheu o perfil. Vale chamar no e-mail para incentivar a conclusão.</p>
  </section>
<?php endif; ?>

<?php if (!empty($pagamentos)): ?>
  <section class="gaveta-secao">
    <h3>Pagamentos</h3>
    <ul class="gaveta-lista">
      <?php foreach ($pagamentos as $pg): ?>
        <li>
          <strong><?= e(forma_pagamento($pg['metodo'])) ?></strong>
          · <?= e(t('plano.' . $pg['plano'])) ?> · <?= e(moeda((float) $pg['valor'], $pg['moeda'])) ?>
          · <span class="selo <?= $pg['status'] === 'pago' ? 'selo-sucesso' : 'selo-neutro' ?>"><?= e(\App\Enums\StatusPagamento::from($pg['status'])->rotulo()) ?></span>
          <small class="texto-suave"><?= data_br($pg['pago_em'] ?? $pg['criado_em'], true) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if (empty($podeExcluir)): ?>
  <p class="gaveta-nota texto-suave"><?= icone('cadeado', 'icone icone-sm') ?> Este cadastro tem pagamento ou assinatura ativa e por isso não pode ser excluído.</p>
<?php else: ?>
<form class="gaveta-perigo" method="post" action="/admin/usuarios/<?= $usuario->id ?>/excluir"
      onsubmit="return confirm('Excluir o cadastro de <?= e(addslashes($usuario->nome)) ?> e tudo o que depende dele (perfil, candidaturas, assinaturas)? Isso não pode ser desfeito.')">
  <?= csrf_campo() ?>
  <button class="botao botao-fantasma botao-pequeno" type="submit">Excluir cadastro</button>
</form>
<?php endif; ?>

<footer class="gaveta-rodape">
  Cadastrado em <?= data_br($usuario->criadoEm, true) ?><?= $usuario->ultimoAcessoEm ? ' · Último acesso em ' . data_br($usuario->ultimoAcessoEm, true) : '' ?>
</footer>
