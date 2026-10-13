<?php
/** @var \App\Entities\Vaga[] $vagas @var array $valores @var array<string, string> $erros @var ?string $sucesso @var string $statusAtual */
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Moeda;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

$valor = fn (string $campo): string => e($valores[$campo] ?? '');
$selecao = function (string $campo, array $opcoes) use ($valores): string {
    $html = '<option value="">Selecione</option>';
    foreach ($opcoes as $chave => $rotulo) {
        $html .= '<option value="' . e($chave) . '"' . (($valores[$campo] ?? '') === $chave ? ' selected' : '') . '>' . e($rotulo) . '</option>';
    }
    return $html;
};
$ativas = count(array_filter($vagas, fn ($vaga) => $vaga->ativa));
$rotuloFonte = fn (string $fonte): string => match ($fonte) {
    'github' => 'GitHub', 'adzuna' => 'Adzuna', 'gupy' => 'Gupy', 'arbeitnow' => 'Arbeitnow', 'jobicy' => 'Jobicy',
    'remoteok' => 'Remote OK', 'himalayas' => 'Himalayas', 'greenhouse' => 'Greenhouse', 'lever' => 'Lever', default => 'Manual',
};
$totalGeral = array_sum(array_column($porFonte, 'total'));
$totalAtivas = array_sum(array_column($porFonte, 'ativas'));
$abas = ['' => ['Todas', $totalGeral], 'ativas' => ['Ativas', $totalAtivas], 'pausadas' => ['Pausadas', $totalGeral - $totalAtivas]];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Vagas</h1>
    <p>Vagas da curadoria interna. Elas alimentam o match e o contador de "vagas compatíveis" dos profissionais.</p>
  </div>
  <div class="acoes-cabecalho">
    <form method="post" action="/admin/vagas/importar-externas" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Importando… pode levar 2 minutos'">
      <?= csrf_campo() ?>
      <button class="botao botao-primario" type="submit"><?= icone('download', 'icone icone-sm') ?> Importar de todas as fontes</button>
    </form>
    <form method="post" action="/admin/vagas/importar-github" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Importando… pode levar 1 minuto'">
      <?= csrf_campo() ?>
      <button class="botao botao-secundario" type="submit"><?= icone('raio', 'icone icone-sm') ?> Só GitHub</button>
    </form>
    <a class="botao botao-secundario" href="/admin/vagas/adzuna"><?= icone('busca', 'icone icone-sm') ?> Importar da Adzuna</a>
  </div>

</section>

<?php if ($sucesso): ?>
  <div class="alerta alerta-sucesso alerta-topo"><?= icone('check') ?><span><?= e($sucesso) ?></span></div>
<?php endif; ?>

<?php if ($visiveis < $totalAtivas): ?>
  <div class="alerta alerta-aviso alerta-topo">
    <?= icone('relogio') ?>
    <span><strong><?= $totalAtivas ?> vagas estão ativas, mas só <?= $visiveis ?> aparecem para o público.</strong>
    As outras <?= $totalAtivas - $visiveis ?> foram criadas há mais de <?= \App\Services\VagaService::DIAS_VALIDADE ?> dias e saem do site sozinhas (a vaga costuma já estar preenchida). Importe vagas novas para repor.</span>
  </div>
<?php endif; ?>

<nav class="abas-status">
  <?php foreach ($abas as $valorAba => [$rotulo, $contagem]): ?>
    <a class="<?= $statusAtual === $valorAba ? 'ativo' : '' ?>" href="/admin/vagas<?= $valorAba !== '' ? '?status=' . $valorAba : '' ?>">
      <?= e($rotulo) ?> <span><?= $contagem ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<section class="indicadores">
  <?php foreach ($porFonte as $fonte => $contagem): ?>
    <div class="indicador cartao">
      <span class="indicador-icone"><?= icone('download', 'icone icone-sm') ?></span>
      <span class="indicador-rotulo"><?= e($rotuloFonte($fonte)) ?></span>
      <strong class="indicador-valor"><?= $contagem['total'] ?> <small class="texto-suave">(<?= $contagem['ativas'] ?> ativas)</small></strong>
    </div>
  <?php endforeach; ?>
</section>

<details class="cartao cartao-painel formulario-vaga" <?= $erros ? 'open' : '' ?>>
  <summary><?= icone('maleta', 'icone icone-sm') ?> Cadastrar vaga</summary>

  <form method="post" action="/admin/vagas" novalidate>
    <?= csrf_campo() ?>
    <div class="grade-campos">
      <div class="<?= classe_campo($erros, 'titulo', 'coluna-dupla') ?>">
        <label class="campo-rotulo" for="titulo">Título</label>
        <input class="entrada" id="titulo" name="titulo" maxlength="150" placeholder="Desenvolvedor PHP Pleno" value="<?= $valor('titulo') ?>">
        <?= erro_campo($erros, 'titulo') ?>
      </div>
      <div class="<?= classe_campo($erros, 'empresa') ?>">
        <label class="campo-rotulo" for="empresa">Empresa</label>
        <input class="entrada" id="empresa" name="empresa" maxlength="150" value="<?= $valor('empresa') ?>">
        <?= erro_campo($erros, 'empresa') ?>
      </div>
      <div class="<?= classe_campo($erros, 'area') ?>">
        <label class="campo-rotulo" for="area">Área</label>
        <select class="selecao" id="area" name="area"><?= $selecao('area', AreaAtuacao::opcoes()) ?></select>
        <?= erro_campo($erros, 'area') ?>
      </div>
      <div class="<?= classe_campo($erros, 'senioridade') ?>">
        <label class="campo-rotulo" for="senioridade">Senioridade</label>
        <select class="selecao" id="senioridade" name="senioridade"><?= $selecao('senioridade', Senioridade::opcoes()) ?></select>
        <?= erro_campo($erros, 'senioridade') ?>
      </div>
      <div class="<?= classe_campo($erros, 'modelo_trabalho') ?>">
        <label class="campo-rotulo" for="modelo_trabalho">Modelo</label>
        <select class="selecao" id="modelo_trabalho" name="modelo_trabalho">
          <?= $selecao('modelo_trabalho', array_diff_key(ModeloTrabalho::opcoes(), [ModeloTrabalho::Indiferente->value => true])) ?>
        </select>
        <?= erro_campo($erros, 'modelo_trabalho') ?>
      </div>
      <div class="<?= classe_campo($erros, 'regiao') ?>">
        <label class="campo-rotulo" for="regiao">Região</label>
        <select class="selecao" id="regiao" name="regiao">
          <?php foreach (RegiaoVagas::deVagas() as $regiao): ?>
            <option value="<?= $regiao->value ?>" <?= ($valores['regiao'] ?? 'brasil') === $regiao->value ? 'selected' : '' ?>><?= e($regiao->rotulo()) ?></option>
          <?php endforeach; ?>
        </select>
        <?= erro_campo($erros, 'regiao') ?>
      </div>
      <div class="<?= classe_campo($erros, 'moeda') ?>">
        <label class="campo-rotulo" for="moeda">Moeda do salário</label>
        <select class="selecao" id="moeda" name="moeda">
          <?php foreach (Moeda::cases() as $opcao): ?>
            <option value="<?= $opcao->value ?>" <?= ($valores['moeda'] ?? 'BRL') === $opcao->value ? 'selected' : '' ?>><?= e($opcao->rotulo()) ?></option>
          <?php endforeach; ?>
        </select>
        <?= erro_campo($erros, 'moeda') ?>
      </div>
      <div class="<?= classe_campo($erros, 'stack', 'coluna-dupla') ?>">
        <label class="campo-rotulo" for="stack">Stack (separada por vírgula)</label>
        <input class="entrada" id="stack" name="stack" maxlength="600" placeholder="PHP, Laravel, MySQL" value="<?= $valor('stack') ?>">
        <?= erro_campo($erros, 'stack') ?>
      </div>
      <div class="<?= classe_campo($erros, 'cidade') ?>">
        <label class="campo-rotulo" for="cidade">Cidade</label>
        <input class="entrada" id="cidade" name="cidade" maxlength="120" placeholder="Para presencial/híbrido" value="<?= $valor('cidade') ?>">
        <?= erro_campo($erros, 'cidade') ?>
      </div>
      <div class="<?= classe_campo($erros, 'salario_min') ?>">
        <label class="campo-rotulo" for="salario_min">Salário mínimo</label>
        <input class="entrada" id="salario_min" name="salario_min" type="number" min="0" value="<?= $valor('salario_min') ?>">
        <?= erro_campo($erros, 'salario_min') ?>
      </div>
      <div class="<?= classe_campo($erros, 'salario_max') ?>">
        <label class="campo-rotulo" for="salario_max">Salário máximo</label>
        <input class="entrada" id="salario_max" name="salario_max" type="number" min="0" value="<?= $valor('salario_max') ?>">
        <?= erro_campo($erros, 'salario_max') ?>
      </div>
      <div class="<?= classe_campo($erros, 'link', 'coluna-dupla') ?>">
        <label class="campo-rotulo" for="link">Link da vaga</label>
        <input class="entrada" id="link" name="link" maxlength="500" placeholder="https://" value="<?= $valor('link') ?>">
        <?= erro_campo($erros, 'link') ?>
      </div>
      <div class="<?= classe_campo($erros, 'descricao', 'coluna-total') ?>">
        <label class="campo-rotulo" for="descricao">Descrição / observações</label>
        <textarea class="area-texto" id="descricao" name="descricao" rows="3" maxlength="5000"><?= $valor('descricao') ?></textarea>
        <?= erro_campo($erros, 'descricao') ?>
      </div>
    </div>
    <button class="botao botao-primario" type="submit"><?= icone('check', 'icone icone-sm') ?> Salvar vaga</button>
  </form>
</details>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela tabela-vagas">
      <thead>
        <tr><th>Vaga</th><th>Nível</th><th>Modelo</th><th>Stack</th><th>Fonte</th><th>Cadastro</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($vagas as $vaga): ?>
          <tr>
            <td>
              <span class="texto-celula"><?= e($vaga->titulo) ?></span>
              <small><?= e($vaga->empresa) ?><?= $vaga->faixaSalarial() ? ' · ' . e($vaga->faixaSalarial()) : '' ?></small>
            </td>
            <td><?= e($vaga->senioridade->rotulo()) ?><small><?= e($vaga->area->rotulo()) ?></small></td>
            <td><?= e($vaga->local()) ?><small><?= e($vaga->regiao->rotulo()) ?></small></td>
            <td><?= e(implode(', ', array_slice($vaga->stack, 0, 4))) ?></td>
            <td><span class="selo selo-neutro"><?= e($rotuloFonte($vaga->fonte)) ?></span></td>
            <td><?= data_br($vaga->criadoEm) ?></td>
            <td><span class="selo <?= $vaga->ativa ? 'selo-sucesso' : 'selo-neutro' ?>"><?= $vaga->ativa ? 'Ativa' : 'Pausada' ?></span></td>
            <td class="acoes-linha">
              <form method="post" action="/admin/vagas/<?= $vaga->id ?>/alternar">
                <?= csrf_campo() ?>
                <input type="hidden" name="status" value="<?= e($statusAtual) ?>">
                <button class="botao botao-fantasma botao-pequeno" type="submit"><?= $vaga->ativa ? 'Pausar' : 'Reativar' ?></button>
              </form>
              <form method="post" action="/admin/vagas/<?= $vaga->id ?>/excluir" onsubmit="return confirm('Excluir esta vaga? As candidaturas já enviadas pra ela também somem. Essa ação não pode ser desfeita.')">
                <?= csrf_campo() ?>
                <input type="hidden" name="status" value="<?= e($statusAtual) ?>">
                <button class="botao botao-fantasma botao-pequeno botao-perigo" type="submit" aria-label="Excluir vaga"><?= icone('x', 'icone icone-sm') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($vagas === []): ?>
          <tr><td colspan="8" class="texto-suave">Nenhuma vaga cadastrada ainda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="paginacao"><span><?= count($vagas) ?> vagas · <?= $ativas ?> ativas</span></div>
</section>
