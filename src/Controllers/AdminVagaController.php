<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Enums\Senioridade;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\ImportadorVagasAdzuna;
use App\Services\ImportadorVagasGithub;
use App\Services\VagaService;
use RuntimeException;

/** Cadastro interno das vagas que alimentam o match. */
final class AdminVagaController
{
    /** Resultados da última busca na Adzuna, guardados na sessão pra o botão "Importar" não precisar reenviar os dados. */
    private const CHAVE_SESSAO_BUSCA = 'admin_busca_adzuna';

    public function __construct(
        private readonly VagaService $vagas,
        private readonly ImportadorVagasAdzuna $adzuna,
        private readonly ImportadorVagasGithub $github,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function listar(Requisicao $requisicao): Resposta
    {
        return $this->pagina($requisicao, [], [], Sessao::obterFlash('sucesso'));
    }

    public function cadastrar(Requisicao $requisicao): Resposta
    {
        $dados = $requisicao->todos();

        try {
            $this->vagas->cadastrar($dados);
        } catch (ValidacaoException $erro) {
            return $this->pagina($requisicao, $dados, $erro->erros(), null);
        }

        Sessao::flash('sucesso', 'Vaga cadastrada.');
        return Resposta::redirecionar('/admin/vagas');
    }

    public function alternar(Requisicao $requisicao): Resposta
    {
        $this->vagas->alternarAtiva((int) $requisicao->parametro('id'));
        return Resposta::redirecionar($this->voltarPara($requisicao));
    }

    public function excluir(Requisicao $requisicao): Resposta
    {
        $this->vagas->excluir((int) $requisicao->parametro('id'));
        Sessao::flash('sucesso', 'Vaga excluída.');
        return Resposta::redirecionar($this->voltarPara($requisicao));
    }

    /** Mesma importação automática do terminal (repositórios de vagas do GitHub), disparada pelo navegador. */
    public function importarGithub(Requisicao $requisicao): Resposta
    {
        set_time_limit(180);
        $resumo = $this->github->importar();

        $mensagem = "GitHub: {$resumo['importadas']} vagas novas, {$resumo['repetidas']} já existiam, {$resumo['ignoradas']} ignoradas (antigas).";
        if ($resumo['erros'] !== []) {
            $mensagem .= ' Avisos: ' . implode(' | ', array_slice($resumo['erros'], 0, 3));
        }

        Sessao::flash('sucesso', $mensagem);
        return Resposta::redirecionar('/admin/vagas');
    }

    public function buscarAdzuna(Requisicao $requisicao): Resposta
    {
        $termo = trim((string) $requisicao->consulta('termo', ''));
        $nivel = (string) $requisicao->consulta('nivel', '');
        $pagina = max(1, (int) $requisicao->consulta('pagina', 1));

        $linhas = [];
        $erro = null;

        if (!$this->adzuna->configurado()) {
            $erro = 'Adzuna não configurado: peça pra alguém cadastrar ADZUNA_APP_ID e ADZUNA_APP_KEY no .env.';
        } elseif ($termo !== '') {
            try {
                $linhas = $this->buscarResultados($termo, $nivel, $pagina);
            } catch (RuntimeException $e) {
                $erro = 'Não deu pra buscar na Adzuna agora: ' . $e->getMessage();
            }
        }

        $dados = ['linhas' => $linhas, 'termo' => $termo, 'nivel' => $nivel, 'pagina' => $pagina, 'erro' => $erro];

        if ($requisicao->ehHtmx()) {
            return Resposta::html(Visao::parcial('admin/partials/resultados-adzuna', $dados));
        }

        return Resposta::html(Visao::renderizar('admin/importar-adzuna', [
            'titulo' => 'Importar vagas — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            ...$dados,
        ], 'app'));
    }

    public function importarAdzuna(Requisicao $requisicao): Resposta
    {
        $id = (string) $requisicao->parametro('id');
        $bruta = Sessao::obter(self::CHAVE_SESSAO_BUSCA, [])[$id] ?? null;

        if ($bruta !== null) {
            $this->adzuna->importarUma($bruta);
        }

        return Resposta::html(Visao::parcial('admin/partials/linha-adzuna', [
            'id' => $id,
            'vaga' => $bruta !== null ? $this->adzuna->interpretar($bruta) : null,
            'link' => $bruta['redirect_url'] ?? null,
            'jaImportada' => true,
        ]));
    }

    /** Busca, filtra por nível (a Adzuna não tem esse filtro nativo) e guarda os resultados brutos na sessão. */
    private function buscarResultados(string $termo, string $nivel, int $pagina): array
    {
        $brutas = $this->adzuna->buscarPagina($termo, $pagina);
        $nivelFiltro = Senioridade::tryFrom($nivel);

        $cache = [];
        $linhas = [];
        foreach ($brutas as $bruta) {
            $vaga = $this->adzuna->interpretar($bruta);
            if ($nivelFiltro !== null && $vaga->senioridade !== $nivelFiltro) {
                continue;
            }

            $id = (string) ($bruta['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $cache[$id] = $bruta;
            $linhas[] = [
                'id' => $id,
                'vaga' => $vaga,
                'link' => $bruta['redirect_url'] ?? null,
                'jaImportada' => $vaga->link !== null && $this->adzuna->jaImportada($vaga->link),
            ];
        }

        Sessao::definir(self::CHAVE_SESSAO_BUSCA, $cache);
        return $linhas;
    }

    private function pagina(Requisicao $requisicao, array $valores, array $erros, ?string $sucesso): Resposta
    {
        $status = (string) $requisicao->consulta('status', '');
        $ativa = match ($status) {
            'ativas' => true,
            'pausadas' => false,
            default => null,
        };

        return Resposta::html(Visao::renderizar('admin/vagas', [
            'titulo' => 'Vagas — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'vagas' => $this->vagas->listar($ativa),
            'porFonte' => $this->vagas->contarPorFonte(),
            'statusAtual' => $status,
            'valores' => $valores,
            'erros' => $erros,
            'sucesso' => $sucesso,
        ], 'app'), $erros ? 422 : 200);
    }

    /** Volta pra mesma aba (todas/ativas/pausadas) de onde a ação foi disparada. */
    private function voltarPara(Requisicao $requisicao): string
    {
        $status = (string) $requisicao->entrada('status', '');
        return $status !== '' ? "/admin/vagas?status={$status}" : '/admin/vagas';
    }
}
