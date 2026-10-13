<?php
/**
 * Uma seção de configurações: um cartão por serviço, cada um com o seu formulário.
 * @var string $chaveSecao @var array $secao @var array<string, string> $erros @var ?string $cartaoComErro @var string $cartaoSalvo
 * @var ?array $teste @var ?array $leitura
 */
$origens = [
    'painel' => ['Salvo aqui', 'selo-sucesso'],
    'env' => ['Vindo do .env', 'selo-neutro'],
    'vazio' => ['Em branco', 'selo-neutro'],
];
$estados = [
    'configurado' => ['Configurado', 'selo-sucesso'],
    'incompleto' => ['Incompleto', 'selo-alerta'],
    'vazio' => ['Não configurado', 'selo-neutro'],
];
?>
<section class="cabecalho-pagina">
  <div>
    <h1><?= e($secao['titulo']) ?></h1>
    <p><?= e($secao['descricao']) ?></p>
  </div>
</section>

<div class="cfg-lista">
  <?php foreach ($secao['cartoes'] as $chaveCartao => $cartao):
      $idCartao = $chaveSecao . '-' . $chaveCartao;
      $errosDoCartao = $cartaoComErro === $chaveCartao ? $erros : [];
  ?>
    <section class="cartao cfg-cartao" id="<?= e($idCartao) ?>">
      <form method="post" action="/admin/configuracoes/<?= e($chaveSecao) ?>/<?= e($chaveCartao) ?>" autocomplete="off">
        <?= csrf_campo() ?>

        <header class="cfg-cabecalho">
          <div class="cfg-titulo">
            <?php if ($cartao['icone']): ?><?= icone($cartao['icone']) ?><?php endif; ?>
            <h2><?= e($cartao['titulo']) ?></h2>
          </div>
          <div class="cfg-direita">
            <?php if ($cartao['status']): [$rotuloEstado, $classeEstado] = $estados[$cartao['status']]; ?>
              <span class="selo <?= $classeEstado ?> selo-ponto"><?= e($rotuloEstado) ?></span>
            <?php endif; ?>
            <?php if ($cartao['metodo']): ?>
              <input type="hidden" name="__ativo_enviado" value="1">
              <label class="interruptor" title="Mostrar esta forma de pagamento no checkout">
                <input type="checkbox" name="__ativo" value="1" <?= $cartao['ativo'] ? 'checked' : '' ?>>
                <span class="interruptor-trilho" aria-hidden="true"></span>
                <span>No checkout</span>
              </label>
            <?php endif; ?>
          </div>
        </header>
        <p class="cfg-descricao"><?= e($cartao['descricao']) ?></p>

        <div class="cfg-corpo">
          <?php if ($cartaoSalvo === $chaveCartao): ?>
            <div class="alerta alerta-sucesso" role="status" style="grid-column: 1 / -1"><?= icone('check') ?><span>Salvo. Já está valendo no site.</span></div>
          <?php endif; ?>
          <?php if ($cartaoComErro === $chaveCartao): ?>
            <div class="alerta alerta-erro" role="alert" style="grid-column: 1 / -1"><?= icone('x') ?><span>Nada foi salvo: corrija os campos marcados.</span></div>
          <?php endif; ?>

          <?php foreach ($cartao['campos'] as $chave => $campo):
              $id = 'cfg-' . $chave;
              [$rotuloOrigem, $classeOrigem] = $origens[$campo['origem']];
          ?>
            <div class="<?= classe_campo($errosDoCartao, $chave) ?>">
              <label class="campo-rotulo cfg-rotulo" for="<?= e($id) ?>">
                <span><?= e($campo['rotulo']) ?></span>
                <?php if ($campo['origem'] !== 'vazio'): ?><span class="selo <?= $classeOrigem ?>"><?= e($rotuloOrigem) ?></span><?php endif; ?>
              </label>

              <?php if ($campo['tipo'] === 'opcao'): ?>
                <select class="selecao" id="<?= e($id) ?>" name="<?= e($chave) ?>">
                  <option value="">Padrão</option>
                  <?php foreach ($campo['opcoes'] as $valor => $rotulo): ?>
                    <option value="<?= e((string) $valor) ?>" <?= $campo['valor'] === (string) $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                  <?php endforeach; ?>
                </select>

              <?php elseif ($campo['tipo'] === 'segredo'): ?>
                <div class="entrada-grupo">
                  <input class="entrada mono" id="<?= e($id) ?>" name="<?= e($chave) ?>" type="password" autocomplete="new-password" spellcheck="false"
                         placeholder="<?= $campo['final'] ? e('Configurado (termina em ' . $campo['final'] . '). Em branco mantém.') : 'Cole aqui' ?>">
                  <button class="entrada-acao" type="button" aria-label="Mostrar" data-alternar-senha><?= icone('olho') ?></button>
                </div>
                <?php if ($campo['origem'] === 'painel'): ?>
                  <label class="cfg-opcao"><input type="checkbox" name="limpar[]" value="<?= e($chave) ?>"> Apagar o valor salvo</label>
                <?php endif; ?>

              <?php elseif ($campo['tipo'] === 'token'): ?>
                <div class="copia-cola">
                  <input class="entrada mono" id="<?= e($id) ?>" value="<?= e($campo['valor']) ?>" readonly>
                  <button class="botao botao-secundario" type="button" data-copiar="#<?= e($id) ?>" data-mensagem="Token copiado!"><?= icone('arquivo', 'icone icone-sm') ?> Copiar</button>
                </div>
                <label class="cfg-opcao"><input type="checkbox" name="regenerar[]" value="<?= e($chave) ?>"> Gerar um token novo (o antigo para de funcionar)</label>

              <?php else: ?>
                <input class="entrada" id="<?= e($id) ?>" name="<?= e($chave) ?>" value="<?= e($campo['valor']) ?>" spellcheck="false"
                       type="<?= $campo['tipo'] === 'email' ? 'email' : ($campo['tipo'] === 'url' ? 'url' : 'text') ?>">
              <?php endif; ?>

              <?php if (!empty($campo['ajuda'])): ?><small class="texto-suave cfg-ajuda"><?= e($campo['ajuda']) ?></small><?php endif; ?>
              <?= erro_campo($errosDoCartao, $chave) ?>
            </div>
          <?php endforeach; ?>

          <?php if ($cartao['avisos']): ?>
            <ul class="cfg-avisos">
              <?php foreach ($cartao['avisos'] as $indice => $aviso): $idAviso = 'aviso-' . $idCartao . '-' . $indice;
                  // "Rótulo: endereço" vira rótulo + endereço com botão de copiar.
                  $partes = preg_match('#^(.*?)(https?://\S+)$#', $aviso, $achado) ? $achado : null; ?>
                <li>
                  <?= icone('externo', 'icone icone-sm') ?>
                  <?php if ($partes): ?>
                    <span><?= e(trim($partes[1])) ?> <code class="cfg-url" id="<?= e($idAviso) ?>"><?= e($partes[2]) ?></code>
                      <button class="botao botao-fantasma botao-pequeno" type="button" data-copiar="#<?= e($idAviso) ?>" data-mensagem="Endereço copiado!">Copiar</button></span>
                  <?php else: ?>
                    <span><?= e($aviso) ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <div class="cfg-rodape">
            <span></span>
            <button class="botao botao-primario" type="submit" data-salvando><?= icone('check', 'icone icone-sm') ?> Salvar</button>
          </div>
        </div>
      </form>
    </section>
  <?php endforeach; ?>

  <?php if ($secao['extra'] === 'teste_email' && $leitura): ?>
    <section class="cartao cfg-cartao" id="email-teste">
      <header class="cfg-cabecalho">
        <div class="cfg-titulo"><?= icone('email') ?><h2>Testar o envio</h2></div>
      </header>
      <p class="cfg-descricao">Confira o que o servidor leu e mande um e-mail de teste para a sua conta. Se falhar, a mensagem mostra o motivo exato.</p>

      <div class="cfg-corpo">
        <?php if ($teste): ?>
          <?php if ($teste['ok']): ?>
            <div class="alerta alerta-sucesso" role="status" style="grid-column: 1 / -1"><?= icone('check') ?><span>O servidor aceitou o e-mail para <?= e($teste['para']) ?>. Confira a caixa de entrada e o spam.</span></div>
          <?php else: ?>
            <div class="alerta alerta-erro" role="alert" style="grid-column: 1 / -1"><?= icone('x') ?><span><strong>Falhou:</strong> <?= e($teste['erro'] ?? 'erro desconhecido') ?></span></div>
          <?php endif; ?>
        <?php endif; ?>

        <dl class="cfg-leitura">
          <div><dt>Modo de envio</dt><dd><?= e($leitura['modo']) ?></dd></div>
          <div><dt>Remetente</dt><dd><?= e($leitura['de']) ?></dd></div>
          <div><dt>Servidor</dt><dd><?= e($leitura['host'] ?: '—') ?></dd></div>
          <div><dt>Porta / segurança</dt><dd><?= e($leitura['porta'] . ' / ' . ($leitura['seguranca'] ?: '—')) ?></dd></div>
          <div><dt>Usuário</dt><dd><?= e($leitura['usuario'] ?: '—') ?></dd></div>
          <div><dt>Senha</dt><dd><?= $leitura['senha_tamanho'] > 0 ? e($leitura['senha_tamanho'] . ' caracteres') : '—' ?></dd></div>
          <div><dt>Endereço do site (links)</dt><dd><?= e($leitura['url']) ?></dd></div>
        </dl>

        <?php if (!$leitura['usuario_completo']): ?>
          <div class="alerta alerta-aviso" style="grid-column: 1 / -1"><?= icone('relogio') ?><span>O usuário SMTP deve ser o e-mail completo (com @).</span></div>
        <?php endif; ?>
        <?php if ($leitura['senha_suspeita']): ?>
          <div class="alerta alerta-aviso" style="grid-column: 1 / -1"><?= icone('relogio') ?><span>A senha tem aspas ou espaços, o que costuma indicar erro de digitação.</span></div>
        <?php endif; ?>

        <form class="cfg-rodape" method="post" action="/admin/email/testar">
          <?= csrf_campo() ?>
          <span class="texto-suave">O e-mail vai para <?= e($usuario->email) ?>.</span>
          <button class="botao botao-secundario" type="submit" data-salvando><?= icone('email', 'icone icone-sm') ?> Enviar e-mail de teste</button>
        </form>
      </div>
    </section>
  <?php endif; ?>
</div>
