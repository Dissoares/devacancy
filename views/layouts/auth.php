<?php $tipoLateral ??= 'profissional'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?= parcial('partials/head', ['titulo' => $titulo ?? null, 'estilos' => ['paginas/autenticacao.css']]) ?>
  <script src="<?= asset('js/componentes/formularios.js') ?>" defer></script>
</head>
<body class="pagina-auth" data-tipo="<?= e($tipoLateral) ?>">
  <?= parcial('partials/icones') ?>

  <div class="auth">
    <aside class="auth-lateral" aria-hidden="true">
      <div class="auth-lateral-fundo"></div>
      <?= parcial('partials/marca') ?>

      <div class="auth-lateral-conteudo somente-profissional">
        <span class="selo selo-sucesso selo-ponto">Beta aberto</span>
        <h2>Você programa.<br><span class="texto-gradiente">A gente se candidata.</span></h2>
        <ul class="auth-beneficios">
          <li><span><?= icone('alvo', 'icone icone-sm') ?></span><div><strong>Vagas certas para você</strong>Filtradas pela sua stack, nível e pretensão.</div></li>
          <li><span><?= icone('raio', 'icone icone-sm') ?></span><div><strong>Até 2 candidaturas por dia</strong>Enviadas pela nossa equipe, todos os dias úteis.</div></li>
          <li><span><?= icone('grafico', 'icone icone-sm') ?></span><div><strong>Tudo no seu painel</strong>Acompanhe o status de cada candidatura.</div></li>
        </ul>
      </div>

      <div class="auth-lateral-conteudo somente-empresa">
        <span class="selo selo-primario selo-ponto">Para empresas</span>
        <h2>Contrate devs<br><span class="texto-gradiente">sem garimpar.</span></h2>
        <ul class="auth-beneficios">
          <li><span><?= icone('codigo', 'icone icone-sm') ?></span><div><strong>Filtrados pela sua stack</strong>Perfis organizados pelas tecnologias que você usa.</div></li>
          <li><span><?= icone('camadas', 'icone icone-sm') ?></span><div><strong>Nível e pretensão claros</strong>Saiba o essencial antes da primeira conversa.</div></li>
          <li><span><?= icone('brilho', 'icone icone-sm') ?></span><div><strong>Acesso prioritário</strong>Empresas do beta são as primeiras a receber perfis.</div></li>
        </ul>
      </div>

      <p class="auth-lateral-rodape"><?= icone('cadeado', 'icone icone-sm') ?> Nunca pedimos a senha das suas contas.</p>
    </aside>

    <main class="auth-principal">
      <div class="auth-topo-movel"><?= parcial('partials/marca') ?></div>
      <?= $conteudo ?>
    </main>
  </div>
</body>
</html>
