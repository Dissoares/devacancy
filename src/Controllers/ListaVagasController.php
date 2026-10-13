<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CatalogoSeo;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;
use App\Exceptions\NaoEncontradoException;
use App\Services\IndexNowService;
use App\Services\VagaService;

/** Listagens públicas de vagas por tema, feitas para serem encontradas nos buscadores. */
final class ListaVagasController
{
    private const POR_PAGINA = 20;

    public function __construct(
        private readonly VagaService $vagas,
        private readonly IndexNowService $indexNow,
    ) {
    }

    public function todas(Requisicao $requisicao): Resposta
    {
        return $this->pagina($requisicao, null);
    }

    public function categoria(Requisicao $requisicao): Resposta
    {
        $categoria = CatalogoSeo::buscar((string) $requisicao->parametro('slug'));
        if ($categoria === null) {
            throw new NaoEncontradoException();
        }
        return $this->pagina($requisicao, $categoria);
    }

    /** Chave do IndexNow: o buscador confere se este arquivo existe no site antes de aceitar os avisos. */
    public function chaveIndexNow(Requisicao $requisicao): Resposta
    {
        return Resposta::texto($this->indexNow->chave());
    }

    private function pagina(Requisicao $requisicao, ?array $categoria): Resposta
    {
        $todas = $this->vagas->abertas();
        $vagas = CatalogoSeo::filtrar($todas, $categoria);
        $total = count($vagas);
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $atual = min($paginas, max(1, (int) $requisicao->consulta('pagina', 1)));
        $caminho = $categoria ? '/vagas/' . $categoria['slug'] : '/vagas';

        $tipo = $categoria['tipo'] ?? 'todas';
        $rotulo = $categoria['rotulo'] ?? '';
        $agora = date('Y-m-d');
        $dadosTema = ['n' => $total, 'x' => $rotulo];
        $descricao = t('vagas.descricao_' . $tipo, $dadosTema + ['data' => data_br($agora)]);
        $urlCanonica = url($caminho . ($atual > 1 ? '?pagina=' . $atual : ''));

        $naPagina = array_slice($vagas, ($atual - 1) * self::POR_PAGINA, self::POR_PAGINA);

        return Resposta::html(Visao::renderizar('vagas/lista', [
            'titulo' => t('vagas.titulo_' . $tipo, $dadosTema) . ($atual > 1 ? ' · ' . t('vagas.pagina_n', ['n' => $atual, 'total' => $paginas]) : ''),
            'descricao' => $descricao,
            'urlCanonica' => $urlCanonica,
            // Páginas com poucas vagas ficam fora do Google: conteúdo fraco prejudica o site inteiro.
            'semIndexar' => $total < CatalogoSeo::MINIMO_PARA_INDEXAR,
            'dadosEstruturados' => $this->dadosEstruturados($caminho, $categoria, $naPagina),
            'categoria' => $categoria,
            'tipo' => $tipo,
            'rotulo' => $rotulo,
            'caminho' => $caminho,
            'vagas' => $naPagina,
            'total' => $total,
            'pagina' => $atual,
            'paginas' => $paginas,
            'categorias' => CatalogoSeo::todas(),
            'contagens' => $this->contagens($todas),
        ], 'site'));
    }

    /** @return array<string, int> quantas vagas cada categoria tem (para mostrar só as que existem) */
    private function contagens(array $todas): array
    {
        $contagens = [];
        foreach (CatalogoSeo::todas() as $slug => $categoria) {
            $contagens[$slug] = count(CatalogoSeo::filtrar($todas, $categoria));
        }
        return $contagens;
    }

    /** Trilha de navegação e lista de vagas em dados estruturados (ajuda o Google a entender e mostrar a página). */
    private function dadosEstruturados(string $caminho, ?array $categoria, array $vagas): array
    {
        $trilha = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => t('vagas.migalha_inicio'), 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => t('vagas.migalha_vagas'), 'item' => url('/vagas')],
        ];
        if ($categoria !== null) {
            $trilha[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $categoria['rotulo'], 'item' => url($caminho)];
        }

        $itens = [];
        foreach ($vagas as $posicao => $vaga) {
            $itens[] = ['@type' => 'ListItem', 'position' => $posicao + 1, 'url' => url_vaga($vaga), 'name' => $vaga->titulo];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'BreadcrumbList', 'itemListElement' => $trilha],
                ['@type' => 'ItemList', 'itemListElement' => $itens],
            ],
        ];
    }
}
