<?php
/**
 * @var array<string, array{titulo: string, descricao: string, campos: array, avisos: string[]}> $grupos
 * @var array<string, string> $erros @var ?string $grupoComErro @var string $grupoSalvo
 */
$origens = [
    'painel' => ['Salvo aqui', 'selo-sucesso'],
    'env' => ['Vindo do .env', 'selo-neutro'],
    'vazio' => ['Não configurado', 'selo-alerta'],
];
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Configurações</h1>
    <p>Chaves de pagamento, e-mail e login social, sem precisar abrir a hospedagem. O que é salvo aqui vale no lugar do <code>.env</code>; só o banco de dados continua lá.</p>
  </div>
</section>

<?= parcial('admin/partials/sistema') ?>

<nav class="config-atalhos" aria-label="Seções">
  <?php foreach ($grupos as $chaveGrupo => $grupo): ?>
    <a href="#<?= e($chaveGrupo) ?>"><?= e($grupo['titulo']) ?></a>
  <?php endforeach; ?>
</nav>

<div class="config-grupos">
  <?php foreach ($grupos as $chaveGrupo => $grupo): ?>
    <section class="cartao config-grupo" id="<?= e($chaveGrupo) ?>">
      <header class="config-grupo-topo">
        <h2><?= e($grupo['titulo']) ?></h2>
        <p class="texto-suave"><?= e($grupo['descricao']) ?></p>
      </header>

      <?php if ($grupoSalvo === $chaveGrupo): ?>
        <div class="alerta alerta-sucesso" role="status"><?= icone('check') ?><span>Salvo. Já está valendo no site.</span></div>
      <?php endif; ?>
      <?php if ($grupoComErro === $chaveGrupo): ?>
        <div class="alerta alerta-erro" role="alert"><?= icone('x') ?><span>Nada foi salvo: corrija os campos marcados.</span></div>
      <?php endif; ?>

      <form class="config-form" method="post" action="/admin/configuracoes/<?= e($chaveGrupo) ?>" autocomplete="off">
        <?= csrf_campo() ?>

        <?php foreach ($grupo['campos'] as $chave => $campo):
            $id = 'cfg-' . $chave;
            $errosDoGrupo = $grupoComErro === $chaveGrupo ? $erros : [];
            [$rotuloOrigem, $classeOrigem] = $origens[$campo['origem']];
        ?>
          <div class="<?= classe_campo($errosDoGrupo, $chave) ?>">
            <label class="campo-rotulo config-rotulo" for="<?= e($id) ?>">
              <span><?= e($campo['rotulo']) ?></span>
              <span class="selo <?= $classeOrigem ?>"><?= e($rotuloOrigem) ?></span>
            </label>

            <?php if ($campo['tipo'] === 'opcao'): ?>
              <select class="selecao" id="<?= e($id) ?>" name="<?= e($chave) ?>">
                <option value="">— padrão —</option>
                <?php foreach ($campo['opcoes'] as $valor => $rotulo): ?>
                  <option value="<?= e($valor) ?>" <?= $campo['valor'] === (string) $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
              </select>

            <?php elseif ($campo['tipo'] === 'segredo'): ?>
              <div class="entrada-grupo">
                <input class="entrada mono" id="<?= e($id) ?>" name="<?= e($chave) ?>" type="password" autocomplete="new-password" spellcheck="false"
                       placeholder="<?= $campo['final'] ? e('Configurado (termina em ' . $campo['final'] . '). Deixe em branco para manter.') : 'Cole aqui' ?>">
                <button class="entrada-acao" type="button" aria-label="Mostrar" data-alternar-senha><?= icone('olho') ?></button>
              </div>
              <?php if ($campo['origem'] === 'painel'): ?>
                <label class="config-check"><input type="checkbox" name="limpar[]" value="<?= e($chave) ?>"> Apagar este valor</label>
              <?php endif; ?>

            <?php elseif ($campo['tipo'] === 'token'): ?>
              <div class="copia-cola">
                <input class="entrada mono" id="<?= e($id) ?>" name="<?= e($chave) ?>" value="<?= e($campo['valor']) ?>" readonly>
                <button class="botao botao-secundario" type="button" data-copiar="#<?= e($id) ?>" data-mensagem="Copiado!"><?= icone('arquivo', 'icone icone-sm') ?> Copiar</button>
              </div>
              <label class="config-check"><input type="checkbox" name="regenerar[]" value="<?= e($chave) ?>"> Gerar um token novo (o antigo para de funcionar)</label>

            <?php else: ?>
              <input class="entrada" id="<?= e($id) ?>" name="<?= e($chave) ?>" value="<?= e($campo['valor']) ?>" spellcheck="false"
                     type="<?= $campo['tipo'] === 'email' ? 'email' : ($campo['tipo'] === 'url' ? 'url' : 'text') ?>">
            <?php endif; ?>

            <?php if (!empty($campo['ajuda'])): ?><small class="texto-suave config-ajuda"><?= e($campo['ajuda']) ?></small><?php endif; ?>
            <?= erro_campo($errosDoGrupo, $chave) ?>
          </div>
        <?php endforeach; ?>

        <?php if ($grupo['avisos']): ?>
          <ul class="config-avisos">
            <?php foreach ($grupo['avisos'] as $aviso): ?><li><?= icone('externo', 'icone icone-sm') ?> <span><?= e($aviso) ?></span></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="config-acoes">
          <button class="botao botao-primario" type="submit"><?= icone('check', 'icone icone-sm') ?> Salvar <?= e($grupo['titulo']) ?></button>
        </div>
      </form>
    </section>
  <?php endforeach; ?>
</div>
