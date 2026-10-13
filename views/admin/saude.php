<?php
/** @var array<string, array<int, array{rotulo: string, estado: string, detalhe: string}>> $resultado @var array{erros: int, avisos: int, ok: int} $resumo */
$icones = ['ok' => ['check', 'selo-sucesso', 'OK'], 'aviso' => ['relogio', 'selo-alerta', 'Atenção'], 'erro' => ['x', 'selo-erro', 'Resolver']];
$pronto = $resumo['erros'] === 0;
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Saúde do sistema</h1>
    <p>Confere o servidor e a configuração. Rode antes de divulgar o site e de vez em quando depois.</p>
  </div>
  <a class="botao botao-secundario" href="/admin/saude"><?= icone('raio', 'icone icone-sm') ?> Conferir de novo</a>
</section>

<section class="saude-resumo cartao <?= $pronto ? 'saude-ok' : 'saude-erro' ?>">
  <span class="saude-icone"><?= icone($pronto ? 'check' : 'x', 'icone icone-lg') ?></span>
  <div>
    <h2><?= $pronto ? ($resumo['avisos'] === 0 ? 'Tudo certo para ir ao ar' : 'Pronto para ir ao ar, com ' . $resumo['avisos'] . ' ponto(s) de atenção') : $resumo['erros'] . ' item(ns) para resolver antes de vender' ?></h2>
    <p class="texto-suave"><?= (int) $resumo['ok'] ?> ok · <?= (int) $resumo['avisos'] ?> atenção · <?= (int) $resumo['erros'] ?> a resolver</p>
  </div>
</section>

<?php foreach ($resultado as $grupo => $itens): ?>
  <section class="cartao saude-grupo">
    <h2><?= e($grupo) ?></h2>
    <ul class="saude-lista">
      <?php foreach ($itens as $item): [$icone, $classe, $rotulo] = $icones[$item['estado']]; ?>
        <li class="saude-item saude-<?= e($item['estado']) ?>">
          <span class="selo <?= $classe ?>"><?= icone($icone, 'icone icone-sm') ?> <?= e($rotulo) ?></span>
          <div>
            <strong><?= e($item['rotulo']) ?></strong>
            <small class="texto-suave"><?= e($item['detalhe']) ?></small>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
