<?php
/**
 * @var \App\Entities\Usuario $usuario @var \App\Entities\PerfilProfissional|\App\Entities\PerfilEmpresa|null $perfil
 * @var array<int, array{vaga: \App\Entities\Vaga, compatibilidade: int}> $compativeis @var int $recentes @var ?string $assinaturaAte
 * @var array<int, \App\Enums\StatusCandidatura> $statusCandidaturas @var ?string $linkIndicacao @var ?array $indicacoes
 */
use App\Entities\PerfilEmpresa;
use App\Entities\PerfilProfissional;

$statusCandidaturas ??= [];
?>
<?php if (!empty($sucesso)): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>
<?php if (!empty($aviso)): ?>
  <div class="alerta alerta-aviso alerta-topo" role="status"><?= icone('relogio') ?><span><?= e($aviso) ?></span></div>
<?php endif; ?>

<section class="boas-vindas">
  <div>
    <?php if ($assinaturaAte): ?>
      <span class="selo selo-sucesso selo-ponto"><?= e(t('painel.assinatura_ativa_ate', ['data' => data_br($assinaturaAte)])) ?></span>
    <?php elseif ($usuario->ehProfissional()): ?>
      <span class="selo selo-alerta selo-ponto"><?= e(t('painel.sem_assinatura')) ?></span>
    <?php else: ?>
      <span class="selo selo-sucesso selo-ponto"><?= e(t('painel.beta')) ?></span>
    <?php endif; ?>
    <h1><?= e(t('painel.ola', ['nome' => $usuario->primeiroNome()])) ?></h1>
    <p><?= e(t($usuario->ehEmpresa() ? 'painel.texto_empresa' : ($assinaturaAte ? 'painel.texto_assinante' : 'painel.texto_profissional'))) ?></p>
  </div>
  <a class="botao botao-secundario" href="<?= $usuario->ehProfissional() ? '/perfil' : '/completar-perfil' ?>"><?= icone('usuario', 'icone icone-sm') ?> <?= e(t($usuario->ehProfissional() ? 'perfilpg.meu_perfil' : 'painel.editar_perfil')) ?></a>
</section>

<?php if ($usuario->ehProfissional()): ?>
  <?= parcial('dashboard/vagas-profissional', [
      'compativeis' => $compativeis,
      'recentes' => $recentes,
      'assinaturaAte' => $assinaturaAte,
      'statusCandidaturas' => $statusCandidaturas,
      'paginaVagas' => $paginaVagas ?? 1,
      'restantesHoje' => $restantesHoje ?? 0,
      'limiteDiario' => $limiteDiario ?? 2,
  ]) ?>
<?php endif; ?>

<div class="grade-painel grade-painel-rodape">
  <section class="cartao cartao-painel">
    <h2 class="cartao-titulo"><?= e(t('painel.proximos_passos')) ?></h2>
    <ol class="linha-tempo">
      <li class="concluido">
        <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
        <div><strong><?= e(t('painel.passo_cadastro')) ?></strong><small><?= data_br($usuario->criadoEm) ?></small></div>
      </li>
      <?php if ($usuario->ehEmpresa()): ?>
        <li class="atual">
          <span class="linha-tempo-marcador"></span>
          <div><strong><?= e(t('painel.passo_analise_empresa')) ?></strong><small><?= e(t('painel.passo_analise_empresa_texto')) ?></small></div>
        </li>
        <li>
          <span class="linha-tempo-marcador"></span>
          <div><strong><?= e(t('painel.passo_acesso_devs')) ?></strong><small><?= e(t('painel.passo_acesso_devs_texto')) ?></small></div>
        </li>
      <?php else: ?>
        <li class="concluido">
          <span class="linha-tempo-marcador"><?= icone('check', 'icone icone-sm') ?></span>
          <div><strong><?= e(t('painel.passo_perfil')) ?></strong><small><?= e(t('painel.passo_perfil_texto', ['n' => count($compativeis)])) ?></small></div>
        </li>
        <li class="<?= $assinaturaAte ? 'concluido' : 'atual' ?>">
          <span class="linha-tempo-marcador"><?= $assinaturaAte ? icone('check', 'icone icone-sm') : '' ?></span>
          <div>
            <strong><?= e(t('painel.passo_assinatura')) ?></strong>
            <small>
              <?php if ($assinaturaAte): ?>
                <?= e(t('painel.ativa_ate', ['data' => data_br($assinaturaAte)])) ?>
              <?php else: ?>
                <a class="link" href="/planos"><?= e(t('painel.escolher_plano')) ?></a> <?= e(t('painel.para_liberar')) ?>
              <?php endif; ?>
            </small>
          </div>
        </li>
        <li class="<?= $assinaturaAte ? 'atual' : '' ?>">
          <span class="linha-tempo-marcador"></span>
          <div><strong><?= e(t('painel.passo_candidaturas')) ?></strong><small><?= e(t('painel.passo_candidaturas_texto')) ?></small></div>
        </li>
      <?php endif; ?>
    </ol>
  </section>

  <section class="cartao cartao-painel">
    <h2 class="cartao-titulo"><?= e(t('painel.seu_perfil')) ?></h2>

    <?php if ($perfil instanceof PerfilProfissional): ?>
      <?php $forca = $perfil->forca(); ?>
      <div class="forca-perfil" style="--valor: <?= $forca['percentual'] ?>%">
        <div class="forca-perfil-topo"><span><?= e(t('painel.forca')) ?></span><strong><?= $forca['percentual'] ?>%</strong></div>
        <div class="forca-perfil-barra"><span></span></div>
        <?php if ($forca['pendencias']): ?>
          <small><?= e(t('painel.forca_pendencias')) ?> <a class="link" href="/perfil"><?= e(t('painel.completar')) ?></a></small>
        <?php endif; ?>
      </div>
      <dl class="lista-dados">
        <div><dt><?= e(t('painel.cargo')) ?></dt><dd><?= e($perfil->cargoDesejado) ?></dd></div>
        <div><dt><?= e(t('painel.nivel')) ?></dt><dd><?= e($perfil->senioridade->rotulo()) ?> · <?= e($perfil->area->rotulo()) ?></dd></div>
        <div><dt><?= e(t('painel.modelo')) ?></dt><dd><?= e($perfil->modeloTrabalho->rotulo()) ?><?= $perfil->cidade ? ' · ' . e($perfil->cidade) : '' ?></dd></div>
        <div><dt><?= e(t('painel.curriculo')) ?></dt><dd><?= $perfil->possuiCurriculo() ? '<span class="selo selo-sucesso">' . e(t('painel.enviado')) . '</span>' : '<a class="link" href="/perfil#importar">' . e(t('painel.enviar_curriculo')) . '</a>' ?></dd></div>
      </dl>
      <div class="lista-etiquetas">
        <?php foreach ($perfil->stack as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
    <?php elseif ($perfil instanceof PerfilEmpresa): ?>
      <dl class="lista-dados">
        <div><dt><?= e(t('painel.empresa')) ?></dt><dd><?= e($perfil->nomeEmpresa) ?></dd></div>
        <div><dt><?= e(t('painel.tamanho')) ?></dt><dd><?= e($perfil->tamanho->rotulo()) ?></dd></div>
        <div><dt><?= e(t('painel.contato')) ?></dt><dd><?= e($usuario->nome) ?> · <?= e($perfil->cargoContato) ?></dd></div>
        <div><dt><?= e(t('painel.vagas_abertas')) ?></dt><dd><?= (int) $perfil->vagasAbertas ?></dd></div>
      </dl>
      <div class="lista-etiquetas">
        <?php foreach ($perfil->stacksContratacao as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($linkIndicacao): ?>
  <?= parcial('dashboard/indicacao', ['link' => $linkIndicacao, 'indicacoes' => $indicacoes]) ?>
<?php endif; ?>
