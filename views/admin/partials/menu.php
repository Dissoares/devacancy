<?php
/**
 * Menu lateral do admin. Cada grupo é um cabeçalho; "ativo" compara o caminho atual (início do caminho vale
 * para as telas internas, como /admin/vagas/adzuna dentro de Vagas).
 */
$contagens = admin_contagens();

$grupos = [
    'Visão geral' => [
        ['/admin/analytics', 'grafico', 'Painel'],
    ],
    'Operação' => [
        ['/admin', 'usuarios', 'Cadastros', null, true],
        ['/admin/vagas', 'maleta', 'Vagas'],
        ['/admin/candidaturas', 'raio', 'Candidaturas'],
    ],
    'Financeiro' => [
        ['/admin/pagamentos', 'cadeado', 'Pagamentos', $contagens['pagamentos']],
        ['/admin/comissoes', 'camadas', 'Comissões'],
    ],
    'Configurações' => [
        ['/admin/configuracoes/site', 'ajustes', 'Site'],
        ['/admin/configuracoes/pagamentos', 'cadeado', 'Formas de pagamento'],
        ['/admin/configuracoes/login', 'escudo', 'Login social'],
        ['/admin/configuracoes/email', 'email', 'E-mail'],
        ['/admin/configuracoes/vagas', 'busca', 'Importação de vagas'],
    ],
    'Sistema' => [
        ['/admin/administradores', 'usuario', 'Administradores'],
        ['/admin/migracoes', 'caixa', 'Banco de dados'],
    ],
];

$caminho = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$ehAtivo = fn (string $rota, bool $exato): bool => $exato ? $caminho === $rota : ($caminho === $rota || str_starts_with($caminho, $rota . '/'));
?>
<nav class="admin-menu" aria-label="Administração">
  <?php foreach ($grupos as $titulo => $itens): ?>
    <div class="admin-menu-grupo">
      <p class="admin-menu-titulo"><?= e($titulo) ?></p>
      <?php foreach ($itens as $item): [$rota, $icone, $rotulo, $selo, $exato] = array_pad($item, 5, null); ?>
        <a class="admin-menu-item<?= $ehAtivo($rota, (bool) $exato) ? ' ativo' : '' ?>" href="<?= e($rota) ?>"<?= $ehAtivo($rota, (bool) $exato) ? ' aria-current="page"' : '' ?>>
          <?= icone($icone, 'icone icone-sm') ?>
          <span><?= e($rotulo) ?></span>
          <?php if (!empty($selo)): ?><b class="admin-menu-selo" title="Aguardando você"><?= (int) $selo ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</nav>
