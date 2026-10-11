<?php
/** @var array $itens @var array<string, int> $contagem @var ?\App\Enums\StatusCandidatura $statusAtual @var bool $todas */
use App\Enums\StatusCandidatura;

$whatsapp = function (?string $telefone): ?string {
    $numero = preg_replace('/\D/', '', (string) $telefone);
    return $numero ? 'https://wa.me/' . (strlen($numero) <= 11 ? '55' . $numero : $numero) : null;
};
?>
<section class="cabecalho-pagina">
  <div>
    <h1>Candidaturas</h1>
    <p>Pedidos de "Candidatar-me" dos assinantes. Envie a candidatura e marque o status: o profissional vê no painel.</p>
  </div>
</section>

<nav class="abas-status">
  <?php foreach (StatusCandidatura::cases() as $status): ?>
    <a class="<?= $statusAtual === $status ? 'ativo' : '' ?>" href="/admin/candidaturas?status=<?= $status->value ?><?= $todas ? '&todas=1' : '' ?>">
      <?= e($status->rotulo()) ?> <span><?= (int) ($contagem[$status->value] ?? 0) ?></span>
    </a>
  <?php endforeach; ?>
  <a class="abas-status-extra" href="/admin/candidaturas?status=<?= e($statusAtual?->value ?? 'na_fila') ?><?= $todas ? '' : '&todas=1' ?>">
    <?= $todas ? 'Só assinantes' : 'Incluir reservas de quem não assinou' ?>
  </a>
</nav>

<section class="cartao tabela-cartao">
  <div class="tabela-rolagem">
    <table class="tabela tabela-vagas">
      <thead><tr><th>Profissional</th><th>Vaga</th><th>Pedido em</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($itens as $item): ?>
          <tr>
            <td>
              <span class="texto-celula"><?= e($item['nome']) ?></span>
              <small><?= e($item['email']) ?></small>
              <div class="acoes-linha">
                <?php if ($link = $whatsapp($item['telefone'])): ?>
                  <a class="link-whatsapp" href="<?= e($link) ?>" target="_blank" rel="noopener"><?= icone('whatsapp', 'icone icone-sm') ?> WhatsApp</a>
                <?php endif; ?>
                <?php if ($item['curriculo_arquivo']): ?>
                  <a class="link" href="/admin/usuarios/<?= (int) $item['usuario_id'] ?>/curriculo" target="_blank"><?= icone('arquivo', 'icone icone-sm') ?> Currículo</a>
                <?php endif; ?>
                <?php if ($item['linkedin_url']): ?>
                  <a class="link" href="<?= e($item['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <span class="texto-celula"><?= e($item['titulo']) ?></span>
              <small><?= e($item['empresa']) ?></small>
              <?php if ($item['link']): ?>
                <a class="link" href="<?= e($item['link']) ?>" target="_blank" rel="noopener noreferrer">Abrir vaga <?= icone('externo', 'icone icone-sm') ?></a>
              <?php endif; ?>
            </td>
            <td><?= data_br($item['criado_em'], true) ?></td>
            <td>
              <form class="acoes-status" method="post" action="/admin/candidaturas/<?= (int) $item['id'] ?>/status">
                <?= csrf_campo() ?>
                <input type="hidden" name="voltar" value="<?= e($statusAtual?->value ?? 'na_fila') ?>">
                <?php foreach (StatusCandidatura::cases() as $status): ?>
                  <?php if ($status->value !== $item['status']): ?>
                    <button class="botao botao-fantasma botao-pequeno" type="submit" name="status" value="<?= $status->value ?>"><?= e($status->rotulo()) ?></button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($itens === []): ?>
          <tr><td colspan="4" class="texto-suave">Nada por aqui.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
