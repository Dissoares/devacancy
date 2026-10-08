<?php
/** @var array $estatisticas @var \App\DTOs\FiltroUsuariosDTO $filtro @var array $resultado */
use App\Enums\AreaAtuacao;
use App\Enums\Senioridade;
use App\Enums\TipoUsuario;

$indicadores = [
    ['usuarios', 'Profissionais', $estatisticas['profissionais'] ?? 0],
    ['predio', 'Empresas', $estatisticas['empresas'] ?? 0],
    ['grafico', 'Novos em 7 dias', $estatisticas['novos_semana'] ?? 0],
    ['check', 'Perfis completos', $estatisticas['completos'] ?? 0],
];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Cadastros</h1>
    <p>Profissionais e empresas que entraram pela landing page.</p>
  </div>
</section>

<section class="indicadores">
  <?php foreach ($indicadores as [$icone, $rotulo, $valor]): ?>
    <div class="indicador cartao">
      <span class="indicador-icone"><?= icone($icone, 'icone icone-sm') ?></span>
      <span class="indicador-rotulo"><?= e($rotulo) ?></span>
      <strong class="indicador-valor"><?= number_format((int) $valor, 0, ',', '.') ?></strong>
    </div>
  <?php endforeach; ?>
</section>

<form class="filtros cartao" method="get" action="/admin"
      hx-get="/admin/usuarios" hx-target="#tabela-usuarios" hx-swap="outerHTML"
      hx-trigger="input changed delay:300ms from:input[name='busca'], change from:select, submit">
  <label class="filtro-busca">
    <?= icone('busca', 'icone icone-sm') ?>
    <input class="entrada" type="search" name="busca" value="<?= e($filtro->busca) ?>" placeholder="Buscar por nome, e-mail, empresa ou tecnologia" autocomplete="off">
  </label>

  <select class="selecao" name="tipo" aria-label="Tipo">
    <option value="">Todos os tipos</option>
    <?php foreach (TipoUsuario::publicos() as $tipo): ?>
      <option value="<?= e($tipo->value) ?>" <?= $filtro->tipo === $tipo ? 'selected' : '' ?>><?= e($tipo->rotulo()) ?></option>
    <?php endforeach; ?>
  </select>

  <select class="selecao" name="senioridade" aria-label="Senioridade">
    <option value="">Todos os níveis</option>
    <?php foreach (Senioridade::cases() as $nivel): ?>
      <option value="<?= e($nivel->value) ?>" <?= $filtro->senioridade === $nivel ? 'selected' : '' ?>><?= e($nivel->rotulo()) ?></option>
    <?php endforeach; ?>
  </select>

  <select class="selecao" name="area" aria-label="Área">
    <option value="">Todas as áreas</option>
    <?php foreach (AreaAtuacao::cases() as $area): ?>
      <option value="<?= e($area->value) ?>" <?= $filtro->area === $area ? 'selected' : '' ?>><?= e($area->rotulo()) ?></option>
    <?php endforeach; ?>
  </select>

  <a class="botao botao-secundario" href="/admin/usuarios/exportar?<?= e($filtro->paraConsulta(['pagina' => null])) ?>" data-exportar>
    <?= icone('download', 'icone icone-sm') ?> Exportar CSV
  </a>
</form>

<?= parcial('admin/partials/tabela', ['filtro' => $filtro, 'resultado' => $resultado]) ?>

<div class="gaveta" id="gaveta" aria-hidden="true">
  <div class="gaveta-fundo" data-fechar-gaveta></div>
  <aside class="gaveta-painel" role="dialog" aria-modal="true" aria-label="Detalhes do cadastro">
    <div id="gaveta-conteudo"></div>
  </aside>
</div>
