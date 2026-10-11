<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClassificadorVaga;
use App\Core\ClienteHttp;
use App\DTOs\VagaDTO;
use App\Enums\AreaAtuacao;
use App\Enums\Moeda;
use App\Enums\RegiaoVagas;
use App\Interfaces\VagaRepositoryInterface;
use RuntimeException;

/**
 * Importa vagas reais da API da Adzuna (agregador de vagas, grátis para uso básico).
 * Diferente do GitHub, a resposta já vem estruturada (título, empresa, local, salário),
 * sem precisar interpretar texto livre — só a stack e a área ainda são adivinhadas pelo texto.
 */
final class ImportadorVagasAdzuna
{
    /** Anúncios mais antigos que isso nem são importados. */
    private const DIAS_MAXIMOS = 45;
    private const RESULTADOS_POR_PAGINA = 50;
    /** Limite de páginas por termo, pra não estourar a cota grátis da API em uma única rodada. */
    private const PAGINAS_MAXIMAS = 4;

    private const TERMOS_DE_BUSCA = [
        'desenvolvedor', 'programador', 'engenheiro de software', 'desenvolvedor front-end',
        'desenvolvedor back-end', 'desenvolvedor full stack', 'devops', 'analista de dados',
    ];

    public function __construct(
        private readonly ClienteHttp $http,
        private readonly VagaRepositoryInterface $vagas,
    ) {
    }

    public function configurado(): bool
    {
        return configuracao('adzuna.app_id') !== '' && configuracao('adzuna.app_key') !== '';
    }

    /** @return array{importadas: int, repetidas: int, ignoradas: int, erros: string[]} */
    public function importar(): array
    {
        $resumo = ['importadas' => 0, 'repetidas' => 0, 'ignoradas' => 0, 'erros' => []];

        if (!$this->configurado()) {
            $resumo['erros'][] = 'Adzuna não configurado: defina ADZUNA_APP_ID e ADZUNA_APP_KEY no .env.';
            return $resumo;
        }

        foreach (self::TERMOS_DE_BUSCA as $termo) {
            for ($pagina = 1; $pagina <= self::PAGINAS_MAXIMAS; $pagina++) {
                try {
                    $resposta = $this->http->get($this->url($termo, $pagina), [], 20);
                } catch (RuntimeException $erro) {
                    $resumo['erros'][] = "\"{$termo}\" (pág. {$pagina}): {$erro->getMessage()}";
                    break;
                }

                $resultados = $resposta['results'] ?? [];
                if ($resultados === []) {
                    break;
                }

                foreach ($resultados as $bruta) {
                    $this->processar($bruta, $resumo);
                }
            }
        }

        return $resumo;
    }

    private function processar(array $bruta, array &$resumo): void
    {
        $criadoEm = (string) ($bruta['created'] ?? '');
        if ($criadoEm === '' || strtotime($criadoEm) < time() - self::DIAS_MAXIMOS * 86400) {
            $resumo['ignoradas']++;
            return;
        }

        $link = $bruta['redirect_url'] ?? null;
        if (!$link || $this->vagas->existeLink($link)) {
            $resumo['repetidas']++;
            return;
        }

        $vaga = $this->interpretar($bruta);
        // A mesma vaga aparece em mais de um termo de busca.
        if ($this->vagas->existeSemelhante($vaga->titulo, $vaga->empresa, self::DIAS_MAXIMOS)) {
            $resumo['repetidas']++;
            return;
        }

        $this->vagas->criar($vaga, date('Y-m-d H:i:s', strtotime($criadoEm)));
        $resumo['importadas']++;
    }

    private function interpretar(array $dados): VagaDTO
    {
        $titulo = trim((string) ($dados['title'] ?? ''));
        $descricao = (string) ($dados['description'] ?? '');
        $texto = $titulo . ' ' . $descricao;

        return new VagaDTO(
            titulo: mb_substr(strip_tags($titulo), 0, 150),
            empresa: mb_substr(trim((string) ($dados['company']['display_name'] ?? '')) ?: 'Confidencial', 0, 150),
            area: ClassificadorVaga::area($texto, AreaAtuacao::Fullstack),
            senioridade: ClassificadorVaga::senioridade($texto),
            stack: ClassificadorVaga::stack($texto),
            modeloTrabalho: ClassificadorVaga::modelo($texto),
            cidade: $this->cidade($dados),
            salarioMin: isset($dados['salary_min']) ? (int) round((float) $dados['salary_min']) : null,
            salarioMax: isset($dados['salary_max']) ? (int) round((float) $dados['salary_max']) : null,
            link: $dados['redirect_url'] ?? null,
            descricao: $this->resumo($descricao),
            regiao: RegiaoVagas::Brasil,
            moeda: Moeda::Real,
            fonte: 'adzuna',
        );
    }

    private function cidade(array $dados): ?string
    {
        $nome = trim((string) ($dados['location']['display_name'] ?? ''));
        return $nome !== '' ? mb_substr($nome, 0, 120) : null;
    }

    /** A Adzuna já manda só um trecho da descrição; aqui é só limpar HTML e limitar o tamanho. */
    private function resumo(string $html): ?string
    {
        $texto = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
        if ($texto === '') {
            return null;
        }
        return mb_strlen($texto) > 400 ? rtrim(mb_substr($texto, 0, 397)) . '…' : $texto;
    }

    private function url(string $termo, int $pagina): string
    {
        $parametros = http_build_query([
            'app_id' => configuracao('adzuna.app_id'),
            'app_key' => configuracao('adzuna.app_key'),
            'what' => $termo,
            'results_per_page' => self::RESULTADOS_POR_PAGINA,
            'content-type' => 'application/json',
        ]);

        return sprintf(
            'https://api.adzuna.com/v1/api/jobs/%s/search/%d?%s',
            configuracao('adzuna.pais', 'br'),
            $pagina,
            $parametros,
        );
    }
}
