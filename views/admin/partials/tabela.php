<?php
/** @var \App\DTOs\FiltroUsuariosDTO $filtro @var array{itens: array, total: int} $resultado */
use App\DTOs\FiltroUsuariosDTO;
use App\Enums\Senioridade;
use App\Enums\TamanhoEmpresa;

$total = $resultado['total'];
$paginas = max(1, (int) ceil($total / FiltroUsuariosDTO::POR_PAGINA));
$inicio = $total ? $filtro->deslocamento() + 1 : 0;
$fim = min($total, $filtro->deslocamento() + FiltroUsuariosDTO::POR_PAGINA);
?>
<section class="tabela-cartao cartao" id="tabela-usuarios">
  <?php if ($resultado['itens'] === []): ?>
    <div class="estado-vazio">
      <span class="estado-vazio-icone"><?= icone('caixa', 'icone icone-lg') ?></span>
      <h2><?= $filtro->busca || $filtro->tipo || $filtro->senioridade || $filtro->area ? 'Nenhum cadastro com esses filtros' : 'Nenhum cadastro ainda' ?></h2>
      <p>Os cadastros feitos na landing page aparecem aqui em tempo real.</p>
    </div>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Perfil</th>
            <th>Stack</th>
            <th>Assinatura</th>
            <th>WhatsApp</th>
            <th>Cadastro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resultado['itens'] as $linha):
              $ehEmpresa = $linha['tipo'] === 'empresa';
              $stack = json_decode(($ehEmpresa ? $linha['stacks_contratacao'] : $linha['stack']) ?? '[]', true) ?: [];
              $telefone = preg_replace('/\D/', '', (string) $linha['telefone']);
          ?>
            <tr hx-get="/admin/usuarios/<?= (int) $linha['id'] ?>" hx-trigger="click[!event.target.closest('a')], keyup[key=='Enter']" hx-target="#gaveta-conteudo" hx-swap="innerHTML" tabindex="0">
              <td>
                <div class="celula-usuario">
                  <span class="avatar">
                    <?php if ($linha['avatar_url']): ?><img src="<?= e($linha['avatar_url']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><?= e(iniciais($linha['nome'])) ?><?php endif; ?>
                  </span>
                  <div>
                    <strong><?= e($linha['nome']) ?></strong>
                    <small><?= e($linha['email']) ?></small>
                  </div>
                </div>
              </td>
              <td>
                <span class="selo <?= $ehEmpresa ? 'selo-primario' : 'selo-neutro' ?>"><?= $ehEmpresa ? 'Empresa' : 'Profissional' ?></span>
              </td>
              <td>
                <?php if (!$linha['cadastro_completo']): ?>
                  <span class="selo selo-alerta selo-ponto">Perfil incompleto</span>
                <?php elseif ($ehEmpresa): ?>
                  <strong class="texto-celula"><?= e($linha['nome_empresa']) ?></strong>
                  <small><?= e(TamanhoEmpresa::rotuloDe($linha['tamanho'])) ?> · <?= (int) $linha['vagas_abertas'] ?> vaga(s)</small>
                <?php else: ?>
                  <strong class="texto-celula"><?= e($linha['cargo_desejado']) ?></strong>
                  <small><?= e(Senioridade::rotuloDe($linha['senioridade'])) ?> · <?= (int) $linha['anos_experiencia'] ?> anos</small>
                <?php endif; ?>
              </td>
              <td>
                <div class="lista-etiquetas">
                  <?php foreach (array_slice($stack, 0, 3) as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
                  <?php if (count($stack) > 3): ?><span class="etiqueta">+<?= count($stack) - 3 ?></span><?php endif; ?>
                </div>
              </td>
              <td>
                <?php if ($linha['plano_assinatura']): ?>
                  <span class="selo selo-sucesso selo-ponto"><?= e(t('plano.' . $linha['plano_assinatura'])) ?></span>
                  <small class="texto-suave">até <?= data_br($linha['assinatura_expira_em']) ?><?php if ($linha['metodo_pagamento']): ?> · <?= e(forma_pagamento($linha['metodo_pagamento'])) ?><?php endif; ?></small>
                <?php else: ?>
                  <span class="selo selo-neutro">Não assinante</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($telefone): ?>
                  <a class="link-whatsapp" href="https://wa.me/<?= strlen($telefone) <= 11 ? '55' . e($telefone) : e($telefone) ?>" target="_blank" rel="noopener">
                    <?= icone('whatsapp', 'icone icone-sm') ?> Conversar
                  </a>
                <?php else: ?>
                  <span class="texto-suave">—</span>
                <?php endif; ?>
              </td>
              <td class="texto-suave"><?= data_br($linha['criado_em']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <footer class="paginacao">
      <span><?= $inicio ?>–<?= $fim ?> de <?= $total ?></span>
      <div>
        <button class="botao botao-secundario botao-pequeno" type="button" <?= $filtro->pagina <= 1 ? 'disabled' : '' ?>
                hx-get="/admin/usuarios?<?= e($filtro->paraConsulta(['pagina' => $filtro->pagina - 1])) ?>" hx-target="#tabela-usuarios" hx-swap="outerHTML">
          <?= icone('chevron-esq', 'icone icone-sm') ?> Anterior
        </button>
        <button class="botao botao-secundario botao-pequeno" type="button" <?= $filtro->pagina >= $paginas ? 'disabled' : '' ?>
                hx-get="/admin/usuarios?<?= e($filtro->paraConsulta(['pagina' => $filtro->pagina + 1])) ?>" hx-target="#tabela-usuarios" hx-swap="outerHTML">
          Próxima <?= icone('chevron-dir', 'icone icone-sm') ?>
        </button>
      </div>
    </footer>
  <?php endif; ?>
</section>
