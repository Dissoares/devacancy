<?php
/**
 * @var \App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional|\App\Entities\PerfilEmpresa|null $perfil
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes @var ?string $assinaturaAte
 */
use App\Entities\PerfilEmpresa;
use App\Entities\PerfilProfissional;
?>
<?php if (!empty($sucesso)): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<section class="boas-vindas">
  <div>
    <?php if ($assinaturaAte): ?>
      <span class="selo selo-sucesso selo-ponto">Assinatura ativa até <?= data_br($assinaturaAte) ?></span>
    <?php elseif ($usuario->ehProfissional()): ?>
      <span class="selo selo-alerta selo-ponto">Candidaturas pausadas</span>
    <?php else: ?>
      <span class="selo selo-sucesso selo-ponto">Você está no beta</span>
    <?php endif; ?>
    <h1>Olá, <?= e($usuario->primeiroNome()) ?>.</h1>
    <p>
      <?= $usuario->ehEmpresa()
          ? 'Recebemos o cadastro da sua empresa. Você terá prioridade quando o banco de talentos abrir.'
          : ($assinaturaAte
              ? 'Suas candidaturas estão em andamento. Acompanhe abaixo as vagas na fila.'
              : 'Seu perfil está pronto. Assine para que a gente comece a se candidatar às vagas compatíveis com você.') ?>
    </p>
  </div>
  <a class="botao botao-secundario" href="/completar-perfil"><?= icone('ajustes', 'icone icone-sm') ?> Editar perfil</a>
</section>

<div class="grade-painel">
  <section class="cartao cartao-painel">
    <h2 class="cartao-titulo">Próximos passos</h2>
    <ol class="linha-tempo">
      <li class="concluido">
        <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
        <div><strong>Cadastro concluído</strong><small><?= data_br($usuario->criadoEm) ?></small></div>
      </li>
      <?php if ($usuario->ehEmpresa()): ?>
        <li class="atual">
          <span class="linha-tempo-marcador"></span>
          <div><strong>Análise da empresa</strong><small>Nossa equipe vai entrar em contato pelo WhatsApp.</small></div>
        </li>
        <li>
          <span class="linha-tempo-marcador"></span>
          <div><strong>Acesso aos perfis de devs</strong><small>Perfis compatíveis com a sua stack.</small></div>
        </li>
      <?php else: ?>
        <li class="concluido">
          <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
          <div><strong>Perfil analisado</strong><small><?= count($compativeis) ?> vagas compatíveis encontradas.</small></div>
        </li>
        <li class="<?= $assinaturaAte ? 'concluido' : 'atual' ?>">
          <span class="linha-tempo-marcador"><?= $assinaturaAte ? icone('check', 'icone icone-sm') : '' ?></span>
          <div><strong>Assinatura</strong><small><?= $assinaturaAte ? 'Ativa até ' . data_br($assinaturaAte) . '.' : '<a class="link" href="/planos">Escolher um plano</a> para liberar as candidaturas.' ?></small></div>
        </li>
        <li class="<?= $assinaturaAte ? 'atual' : '' ?>">
          <span class="linha-tempo-marcador"></span>
          <div><strong>Candidaturas diárias</strong><small>Até 2 por dia útil, com acompanhamento aqui no painel.</small></div>
        </li>
      <?php endif; ?>
    </ol>
  </section>

  <section class="cartao cartao-painel">
    <h2 class="cartao-titulo">Seu perfil</h2>

    <?php if ($perfil instanceof PerfilProfissional): ?>
      <dl class="lista-dados">
        <div><dt>Cargo</dt><dd><?= e($perfil->cargoDesejado) ?></dd></div>
        <div><dt>Nível</dt><dd><?= e($perfil->senioridade->rotulo()) ?> · <?= e($perfil->area->rotulo()) ?></dd></div>
        <div><dt>Experiência</dt><dd><?= (int) $perfil->anosExperiencia ?> <?= $perfil->anosExperiencia === 1 ? 'ano' : 'anos' ?></dd></div>
        <div><dt>Modelo</dt><dd><?= e($perfil->modeloTrabalho->rotulo()) ?><?= $perfil->cidade ? ' · ' . e($perfil->cidade) : '' ?></dd></div>
        <div><dt>Currículo</dt><dd><?= $perfil->possuiCurriculo() ? '<span class="selo selo-sucesso">Enviado</span>' : '<a class="link" href="/completar-perfil">Enviar currículo</a>' ?></dd></div>
      </dl>
      <div class="lista-etiquetas">
        <?php foreach ($perfil->stack as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
    <?php elseif ($perfil instanceof PerfilEmpresa): ?>
      <dl class="lista-dados">
        <div><dt>Empresa</dt><dd><?= e($perfil->nomeEmpresa) ?></dd></div>
        <div><dt>Tamanho</dt><dd><?= e($perfil->tamanho->rotulo()) ?></dd></div>
        <div><dt>Contato</dt><dd><?= e($usuario->nome) ?> · <?= e($perfil->cargoContato) ?></dd></div>
        <div><dt>Vagas abertas</dt><dd><?= (int) $perfil->vagasAbertas ?></dd></div>
      </dl>
      <div class="lista-etiquetas">
        <?php foreach ($perfil->stacksContratacao as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($usuario->ehProfissional()): ?>
  <?= parcial('dashboard/vagas-profissional', ['compativeis' => $compativeis, 'recentes' => $recentes, 'assinaturaAte' => $assinaturaAte]) ?>
<?php endif; ?>
