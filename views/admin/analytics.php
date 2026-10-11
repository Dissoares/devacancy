<?php
/** @var array $funil @var array $receita @var array $porSenioridade @var array $porArea @var array $porModelo @var array $porRegiao @var array $porStack */
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

$cadastros = $funil['cadastros'] ?? 0;
$perfisCompletos = $funil['perfis_completos'] ?? 0;
$assinantes = $funil['assinantes'] ?? 0;
$taxaConversao = $cadastros > 0 ? round($assinantes / $cadastros * 100, 1) : 0.0;

$etapasFunil = [
    ['Cadastraram', $cadastros, 100.0],
    ['Completaram o perfil', $perfisCompletos, $cadastros > 0 ? round($perfisCompletos / $cadastros * 100, 1) : 0.0],
    ['Assinaram', $assinantes, $cadastros > 0 ? round($assinantes / $cadastros * 100, 1) : 0.0],
];

/** Monta as barras de conversão por segmento, convertendo o valor bruto salvo no banco em rótulo legível. */
$segmentos = [
    ['Senioridade', $porSenioridade, fn (string $v) => Senioridade::rotuloDe($v)],
    ['Área de atuação', $porArea, fn (string $v) => AreaAtuacao::rotuloDe($v)],
    ['Modelo de trabalho', $porModelo, fn (string $v) => ModeloTrabalho::rotuloDe($v)],
    ['Região das vagas', $porRegiao, fn (string $v) => RegiaoVagas::rotuloDe($v)],
    ['Tecnologias (top 10)', $porStack, fn (string $v) => $v],
];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Analytics</h1>
    <p>Funil de conversão, receita e quais perfis mais assinam.</p>
  </div>
</section>

<section class="indicadores">
  <div class="indicador cartao">
    <span class="indicador-icone"><?= icone('usuarios', 'icone icone-sm') ?></span>
    <span class="indicador-rotulo">Cadastros</span>
    <strong class="indicador-valor"><?= numero($cadastros) ?></strong>
  </div>
  <div class="indicador cartao">
    <span class="indicador-icone"><?= icone('check', 'icone icone-sm') ?></span>
    <span class="indicador-rotulo">Assinantes ativos</span>
    <strong class="indicador-valor"><?= numero($assinantes) ?></strong>
  </div>
  <div class="indicador cartao">
    <span class="indicador-icone"><?= icone('raio', 'icone icone-sm') ?></span>
    <span class="indicador-rotulo">Taxa de conversão</span>
    <strong class="indicador-valor"><?= numero($taxaConversao) ?>%</strong>
  </div>
  <div class="indicador cartao">
    <span class="indicador-icone"><?= icone('grafico', 'icone icone-sm') ?></span>
    <span class="indicador-rotulo">Receita (30 dias)</span>
    <strong class="indicador-valor"><?= moeda((float) ($receita['ultimos_30_dias'] ?? 0)) ?></strong>
  </div>
</section>

<section class="cartao bloco-analytics">
  <h2>Funil de conversão</h2>
  <div class="funil">
    <?php foreach ($etapasFunil as [$rotulo, $valor, $percentual]): ?>
      <div class="funil-etapa">
        <div class="funil-etapa-rotulo">
          <span><?= e($rotulo) ?></span>
          <strong><?= numero($valor) ?> <small>(<?= numero($percentual) ?>%)</small></strong>
        </div>
        <div class="funil-barra"><span style="width: <?= max($percentual, 2) ?>%"></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="cartao bloco-analytics">
  <h2>Receita</h2>
  <div class="linha-campos">
    <p><strong><?= moeda((float) ($receita['total'] ?? 0)) ?></strong> acumulado, <strong><?= moeda((float) ($receita['ultimos_30_dias'] ?? 0)) ?></strong> nos últimos 30 dias.</p>
  </div>
  <?php if ($receita['por_metodo']): ?>
    <table class="tabela-simples">
      <thead><tr><th>Método</th><th>Pagamentos</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($receita['por_metodo'] as $linha): ?>
          <tr>
            <td><?= e(ucfirst((string) $linha['metodo'])) ?></td>
            <td><?= numero((int) $linha['quantidade']) ?></td>
            <td><?= moeda((float) $linha['total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="texto-vazio">Nenhum pagamento confirmado ainda.</p>
  <?php endif; ?>
</section>

<?php foreach ($segmentos as [$titulo, $linhas, $rotuloDe]): ?>
  <section class="cartao bloco-analytics">
    <h2><?= e($titulo) ?></h2>
    <?php if (!$linhas): ?>
      <p class="texto-vazio">Sem dados suficientes ainda.</p>
    <?php else: ?>
      <div class="funil">
        <?php foreach ($linhas as $linha): ?>
          <div class="funil-etapa">
            <div class="funil-etapa-rotulo">
              <span><?= e($rotuloDe($linha['valor'])) ?></span>
              <strong><?= numero($linha['assinantes']) ?>/<?= numero($linha['cadastros']) ?> <small>(<?= numero($linha['taxa']) ?>%)</small></strong>
            </div>
            <div class="funil-barra funil-barra-segmento"><span style="width: <?= max($linha['taxa'], 2) ?>%"></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
