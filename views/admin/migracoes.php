<?php
/** @var array<int, array{arquivo: string, executada_em: ?string}> $estado @var ?array{executadas: string[], erro: ?array{arquivo: string, mensagem: string}} $resultado */
$pendentes = count(array_filter($estado, fn (array $migracao) => $migracao['executada_em'] === null));
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Migrações</h1>
    <p>Atualiza a estrutura do banco depois de subir uma versão nova. Rode quando houver pendentes.</p>
  </div>
</section>

<?php if ($resultado): ?>
  <?php if ($resultado['erro']): ?>
    <div class="alerta alerta-erro" role="alert">
      <?= icone('x') ?>
      <span>
        <strong>Falhou em <?= e($resultado['erro']['arquivo']) ?>.</strong> <?= e($resultado['erro']['mensagem']) ?>
        <?= $resultado['executadas'] ? '(As anteriores foram aplicadas: ' . e(implode(', ', $resultado['executadas'])) . '.)' : '' ?>
        Nada depois dela foi executado.
      </span>
    </div>
  <?php elseif ($resultado['executadas']): ?>
    <div class="alerta alerta-sucesso"><?= icone('check') ?><span><?= count($resultado['executadas']) ?> migração(ões) aplicada(s): <?= e(implode(', ', $resultado['executadas'])) ?>.</span></div>
  <?php else: ?>
    <div class="alerta alerta-sucesso"><?= icone('check') ?><span>Nada a fazer: o banco já está atualizado.</span></div>
  <?php endif; ?>
<?php endif; ?>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Arquivo</th><th>Status</th><th>Executada em</th></tr></thead>
      <tbody>
        <?php foreach ($estado as $migracao): ?>
          <tr>
            <td><span class="texto-celula mono"><?= e($migracao['arquivo']) ?></span></td>
            <td>
              <span class="selo <?= $migracao['executada_em'] ? 'selo-sucesso' : 'selo-alerta' ?> selo-ponto"><?= $migracao['executada_em'] ? 'Aplicada' : 'Pendente' ?></span>
            </td>
            <td class="texto-suave"><?= $migracao['executada_em'] ? data_br($migracao['executada_em'], true) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="rodape-tabela-acao">
    <?php if ($pendentes > 0): ?>
      <form method="post" action="/admin/migracoes/executar" onsubmit="return confirm('Aplicar <?= $pendentes ?> migração(ões) pendente(s) agora? Faça um backup do banco antes se puder.')">
        <?= csrf_campo() ?>
        <button class="botao botao-primario" type="submit"><?= icone('raio', 'icone icone-sm') ?> Rodar <?= $pendentes ?> pendente<?= $pendentes > 1 ? 's' : '' ?></button>
      </form>
    <?php else: ?>
      <span class="texto-suave"><?= icone('check', 'icone icone-sm') ?> Banco atualizado.</span>
    <?php endif; ?>
  </div>
</section>
