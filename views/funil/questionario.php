<?php
/**
 * Questionário anônimo, uma pergunta por tela (o JS controla os passos; sem JS vira um formulário único).
 * @var array $valores @var array<string, string> $erros @var bool $noBrasil @var string $moeda
 * @var array $radar total de vagas e o que ele conta (o radar se atualiza pelo HTMX a cada resposta)
 */
use App\Enums\AreaAtuacao;
use App\Enums\IdiomaFalado;
use App\Enums\ModeloTrabalho;
use App\Enums\NivelIngles;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;
use App\Services\FunilService;

$marcado = fn (string $campo, string $valor): string => in_array($valor, array_map('strval', (array) ($valores[$campo] ?? [])), true) ? 'checked' : '';
$iconesArea = ['frontend' => 'camadas', 'backend' => 'terminal', 'fullstack' => 'codigo', 'mobile' => 'raio', 'dados' => 'grafico', 'devops' => 'ajustes', 'qa' => 'check'];
$iconesModelo = ['remoto' => 'local', 'hibrido' => 'calendario', 'presencial' => 'predio'];
$periodo = $moeda === 'BRL' ? t('funil.por_mes') : t('funil.por_ano');
$faixas = FunilService::FAIXAS_SALARIAIS[$moeda];

$perguntas = ['area', 'senioridade', 'anos_experiencia', 'stack', 'idiomas', 'nivel_ingles', 'modelos', ...($noBrasil ? ['regiao_vagas'] : []), 'cidade', 'pretensao_salarial', 'ultima_remuneracao'];
$passoInicial ??= 0;
foreach ($perguntas as $indice => $pergunta) {
    if (isset($erros[$pergunta])) {
        $passoInicial = $indice;
        break;
    }
}
$numero = fn (string $pergunta): int => array_search($pergunta, $perguntas, true) + 1;
$cabecalho = fn (string $pergunta, string $titulo, ?string $subtitulo = null): string =>
    '<legend><span class="pergunta-contador">' . e(t('funil.pergunta_de', ['n' => $numero($pergunta), 'total' => count($perguntas)]))
    . match (true) {
        $numero($pergunta) === 1 => ' · ' . e(t('funil.leva_1_minuto')),
        $numero($pergunta) >= count($perguntas) - 1 => ' · ' . e(t('funil.quase_la')),
        default => '',
    }
    . '</span><h1>' . e($titulo) . '</h1></legend>'
    . ($subtitulo ? '<p class="pergunta-subtitulo">' . e($subtitulo) . '</p>' : '');
?>
<div class="questionario-layout">
<form class="questionario" method="post" action="/comecar" data-questionario data-passo-inicial="<?= $passoInicial ?>"
      data-mensagem-obrigatorio="<?= e(t('funil.responda_para_continuar')) ?>" novalidate>
  <?= csrf_campo() ?>

  <div class="questionario-progresso" aria-hidden="true"><span data-progresso-questionario></span></div>
  <span class="questionario-comemoracao" data-comemoracao data-texto="<?= e(t('funil.pv_pronto')) ?>" aria-live="polite"></span>

  <fieldset class="pergunta" data-pergunta="area" data-avancar-ao-escolher>
    <?= $cabecalho('area', t('funil.p_area'), t('funil.p_area_sub')) ?>
    <div class="opcoes-cartao">
      <?php foreach (AreaAtuacao::cases() as $area): ?>
        <label class="opcao-cartao">
          <input type="radio" name="area" value="<?= e($area->value) ?>" <?= $marcado('area', $area->value) ?> required>
          <span><?= icone($iconesArea[$area->value] ?? 'codigo') ?><strong><?= e($area->rotulo()) ?></strong></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'area') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="senioridade" data-avancar-ao-escolher>
    <?= $cabecalho('senioridade', t('funil.p_nivel')) ?>
    <div class="opcoes-lista">
      <?php foreach (Senioridade::cases() as $nivel): ?>
        <label class="opcao-cartao opcao-linha">
          <input type="radio" name="senioridade" value="<?= e($nivel->value) ?>" <?= $marcado('senioridade', $nivel->value) ?> required>
          <span><strong><?= e($nivel->rotulo()) ?></strong><small><?= e(t('funil.nivel_desc.' . $nivel->value)) ?></small></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'senioridade') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="anos_experiencia" data-avancar-ao-escolher>
    <?= $cabecalho('anos_experiencia', t('funil.p_experiencia')) ?>
    <div class="opcoes-lista opcoes-compactas">
      <?php foreach (FunilService::EXPERIENCIA as $valor => $chave): ?>
        <label class="opcao-cartao opcao-linha">
          <input type="radio" name="anos_experiencia" value="<?= e((string) $valor) ?>" <?= $marcado('anos_experiencia', (string) $valor) ?> required>
          <span><strong><?= e(t('funil.experiencia.' . $chave)) ?></strong></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'anos_experiencia') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="stack">
    <?= $cabecalho('stack', t('funil.p_stack'), t('funil.p_stack_sub')) ?>
    <div class="<?= classe_campo($erros, 'stack') ?>" data-campo-tags>
      <input type="hidden" name="stack" value="<?= e($valores['stack'] ?? '') ?>" data-obrigatorio>
      <div class="entrada-tags">
        <input type="text" autocomplete="off" aria-label="<?= e(t('funil.p_stack')) ?>" placeholder="<?= e(t('funil.p_stack_placeholder')) ?>">
      </div>
      <div class="sugestoes-tags" aria-label="<?= e(t('perfil.sugestoes')) ?>"></div>
      <?= erro_campo($erros, 'stack') ?>
    </div>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="idiomas">
    <?= $cabecalho('idiomas', t('funil.p_idiomas'), t('funil.marque_varios')) ?>
    <div class="opcoes-chips">
      <?php foreach (IdiomaFalado::cases() as $idioma): ?>
        <label class="opcao-chip">
          <input type="checkbox" name="idiomas[]" value="<?= e($idioma->value) ?>" <?= $marcado('idiomas', $idioma->value) ?>>
          <span><?= icone('check', 'icone icone-sm') ?><img class="bandeira" src="<?= e(asset('img/bandeiras/' . $idioma->bandeira() . '.svg')) ?>" alt="" width="18" height="13"><?= e($idioma->rotulo()) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'idiomas') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="nivel_ingles" data-avancar-ao-escolher data-condicional-campo="idiomas" data-condicional-valor="en">
    <?= $cabecalho('nivel_ingles', t('funil.p_nivel_ingles'), t('funil.p_nivel_ingles_sub')) ?>
    <div class="opcoes-lista">
      <?php foreach (NivelIngles::cases() as $nivelIngles): ?>
        <label class="opcao-cartao opcao-linha">
          <input type="radio" name="nivel_ingles" value="<?= e($nivelIngles->value) ?>" <?= $marcado('nivel_ingles', $nivelIngles->value) ?>>
          <span><strong><?= e($nivelIngles->rotulo()) ?></strong><small><?= e(t('funil.nivel_ingles_desc.' . $nivelIngles->value)) ?></small></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'nivel_ingles') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="modelos">
    <?= $cabecalho('modelos', t('funil.p_modelo'), t('funil.marque_varios')) ?>
    <div class="opcoes-cartao opcoes-tres">
      <?php foreach ([ModeloTrabalho::Remoto, ModeloTrabalho::Hibrido, ModeloTrabalho::Presencial] as $modelo): ?>
        <label class="opcao-cartao">
          <input type="checkbox" name="modelos[]" value="<?= e($modelo->value) ?>" <?= $marcado('modelos', $modelo->value) ?>>
          <span><?= icone($iconesModelo[$modelo->value]) ?><strong><?= e($modelo->rotulo()) ?></strong></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'modelos') ?>
  </fieldset>

  <?php if ($noBrasil): ?>
    <fieldset class="pergunta" data-pergunta="regiao_vagas" data-avancar-ao-escolher>
      <?= $cabecalho('regiao_vagas', t('funil.p_regiao')) ?>
      <div class="opcoes-lista">
        <?php foreach (RegiaoVagas::cases() as $regiao): ?>
          <label class="opcao-cartao opcao-linha">
            <input type="radio" name="regiao_vagas" value="<?= e($regiao->value) ?>" <?= $marcado('regiao_vagas', $regiao->value) ?> required>
            <span><strong><?= e($regiao->rotulo()) ?></strong><small><?= e(t('funil.regiao_desc.' . $regiao->value)) ?></small></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?= erro_campo($erros, 'regiao_vagas') ?>
    </fieldset>
  <?php endif; ?>

  <fieldset class="pergunta" data-pergunta="cidade">
    <?= $cabecalho('cidade', t('funil.p_cidade'), t('funil.p_cidade_sub')) ?>
    <div class="<?= classe_campo($erros, 'cidade') ?>">
      <div class="entrada-icone">
        <?= icone('local', 'icone icone-sm') ?>
        <input class="entrada entrada-grande" name="cidade" maxlength="120" autocomplete="off" data-cidade-autocomplete
               placeholder="<?= e(t('funil.p_cidade_placeholder')) ?>" value="<?= e($valores['cidade'] ?? '') ?>">
      </div>
      <?= erro_campo($erros, 'cidade') ?>
    </div>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="pretensao_salarial" data-avancar-ao-escolher>
    <?= $cabecalho('pretensao_salarial', t('funil.p_pretensao'), $periodo) ?>
    <div class="opcoes-chips opcoes-chips-grandes">
      <?php foreach ($faixas as $valor): ?>
        <label class="opcao-chip">
          <input type="radio" name="pretensao_salarial" value="<?= $valor ?>" <?= $marcado('pretensao_salarial', (string) $valor) ?> required>
          <span><?= e(t('funil.a_partir_de', ['valor' => moeda($valor, $moeda, 0)])) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= erro_campo($erros, 'pretensao_salarial') ?>
  </fieldset>

  <fieldset class="pergunta" data-pergunta="ultima_remuneracao" data-avancar-ao-escolher data-ultima>
    <?= $cabecalho('ultima_remuneracao', t('funil.p_ultima'), $periodo) ?>
    <div class="opcoes-chips opcoes-chips-grandes">
      <?php foreach ($faixas as $valor): ?>
        <label class="opcao-chip">
          <input type="radio" name="ultima_remuneracao" value="<?= $valor ?>" <?= $marcado('ultima_remuneracao', (string) $valor) ?>>
          <span><?= e(t('funil.a_partir_de', ['valor' => moeda($valor, $moeda, 0)])) ?></span>
        </label>
      <?php endforeach; ?>
      <label class="opcao-chip">
        <input type="radio" name="ultima_remuneracao" value="" <?= ($valores['ultima_remuneracao'] ?? null) === '' ? 'checked' : '' ?>>
        <span><?= e(t('funil.prefiro_nao_informar')) ?></span>
      </label>
    </div>
    <?= erro_campo($erros, 'ultima_remuneracao') ?>
  </fieldset>

  <!-- Rodapé: no celular fica fixo na tela (contador, nota e botões juntos, sempre no mesmo lugar). -->
  <div class="questionario-rodape" data-rodape>
    <?= parcial('funil/radar', $radar) ?>

    <p class="questionario-nota"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('funil.nota_privacidade')) ?></p>

    <div class="questionario-navegacao">
      <button class="botao botao-fantasma" type="button" data-voltar aria-label="<?= e(t('funil.voltar')) ?>"><?= icone('chevron-esq', 'icone icone-sm') ?> <span class="rotulo-voltar"><?= e(t('funil.voltar')) ?></span></button>
      <button class="botao botao-primario botao-grande" type="button" data-continuar><?= e(t('funil.continuar')) ?> <?= icone('seta', 'icone icone-seta') ?></button>
      <button class="botao botao-primario botao-grande" type="submit" data-enviar>
        <?= icone('busca', 'icone icone-sm') ?> <?= e(t('funil.ver_minhas_vagas')) ?>
      </button>
    </div>
  </div>
</form>

<!-- Perfil montando ao vivo: cada resposta preenche uma linha (o JS lê o texto das opções marcadas). -->
<aside class="perfil-vivo" data-perfil-vivo aria-label="<?= e(t('funil.pv_titulo')) ?>">
  <header class="perfil-vivo-topo">
    <span class="perfil-vivo-anel" data-perfil-anel style="--valor: 0%"><b data-perfil-percentual>0%</b></span>
    <div>
      <strong><?= e(t('funil.pv_titulo')) ?></strong>
      <small><?= e(t('funil.pv_subtitulo')) ?></small>
    </div>
  </header>
  <dl class="perfil-vivo-linhas">
    <?php foreach (['area', 'senioridade', 'anos_experiencia', 'stack', 'idiomas', 'nivel_ingles', 'modelos', ...($noBrasil ? ['regiao_vagas'] : []), 'cidade', 'pretensao_salarial'] as $campo): ?>
      <div data-perfil-linha="<?= e($campo) ?>" <?= $campo === 'nivel_ingles' ? 'data-condicional-campo="idiomas" data-condicional-valor="en"' : '' ?>>
        <dt><?= e(t('funil.pv_' . $campo)) ?></dt>
        <dd data-perfil-valor>—</dd>
      </div>
    <?php endforeach; ?>
  </dl>
  <p class="perfil-vivo-rodape"><?= icone('cadeado', 'icone icone-sm') ?> <?= e(t('funil.nota_privacidade')) ?></p>
</aside>
</div>
