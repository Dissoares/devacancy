<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClassificadorVaga;
use App\Core\ClienteHttp;
use App\DTOs\VagaDTO;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Moeda;
use App\Enums\RegiaoVagas;
use App\Interfaces\VagaRepositoryInterface;
use App\Services\Vagas\FonteArbeitnow;
use App\Services\Vagas\FonteDeVagas;
use App\Services\Vagas\FonteGreenhouse;
use App\Services\Vagas\FonteGupy;
use App\Services\Vagas\FonteHimalayas;
use App\Services\Vagas\FonteJobicy;
use App\Services\Vagas\FonteLever;
use App\Services\Vagas\FonteRemoteOk;
use Throwable;

/**
 * Importa vagas de várias APIs públicas (Gupy, Arbeitnow, Jobicy, Remote OK, Himalayas e painéis do Greenhouse e do
 * Lever). Cada fonte só busca; aqui ficam as regras comuns: ficar só com vagas de tecnologia, descartar as antigas
 * ou repetidas, classificar (área, nível, modelo, stack) e gravar. Pode rodar quantas vezes quiser.
 */
final class ImportadorVagasExternas
{
    /** Anúncios mais antigos que isso nem são importados. */
    private const DIAS_MAXIMOS = 45;
    private const LIMITE_POR_FONTE = 2000;

    /** Títulos que são claramente vaga de tecnologia. */
    private const TITULO_TECNOLOGIA = '/\b(software|developer|desenvolvedor[a]?|programador[a]?|devops|sre|dba|qa|sdet|full[\s-]?stack|front[\s-]?end|back[\s-]?end|mobile|android|ios|machine learning|data (engineer|scientist|analyst)|cientista de dados|engenheir[oa] de (dados|software|plataforma|machine|confiabilidade|qualidade)|analista (de )?(sistemas|desenvolvimento|dados|testes|bi|qualidade)|tech lead|arquitet[oa] de (software|solu)|site reliability|cloud|platform engineer)\b/iu';

    /** Títulos que parecem tecnologia mas não são (engenheiro civil, vendas, recrutamento...). */
    private const TITULO_EXCLUIDO = '/\b(sales|account (executive|manager)|recruit|marketing|legal|counsel|finance|accountant|customer (success|support)|mechanical|civil|electrical|chemical|neg[óo]cios|business developer|imobili[aá]ri|comercial|vendas|recursos humanos|supply|warehouse|cozinh|motorista|enfermeir|m\/w\/d|w\/m\/d|m\/f\/d|gn)\b/iu';

    /** Termos mais genéricos: só valem se o texto da vaga citar alguma tecnologia. */
    private const TITULO_GENERICO = '/\b(engineer|engenheir[oa]|analista|desenvolvedor|developer|arquitet)/iu';

    /** @var array<string, FonteDeVagas> */
    private array $fontes;

    public function __construct(ClienteHttp $http, private readonly VagaRepositoryInterface $vagas)
    {
        $lista = fn (string $chave): array => array_values(array_filter(array_map('trim', explode(',', (string) configuracao($chave)))));

        $this->fontes = [];
        foreach ([
            new FonteGupy($http),
            new FonteArbeitnow($http),
            new FonteJobicy($http),
            new FonteRemoteOk($http),
            new FonteHimalayas($http),
            new FonteGreenhouse($http, $lista('vagas.greenhouse')),
            new FonteLever($http, $lista('vagas.lever')),
        ] as $fonte) {
            $this->fontes[$fonte->chave()] = $fonte;
        }
    }

    /** @return array<string, string> chave => nome, de todas as fontes que existem */
    public function disponiveis(): array
    {
        return array_map(fn (FonteDeVagas $fonte) => $fonte->rotulo(), $this->fontes);
    }

    /** @return string[] as chaves ligadas em Configurações → Importação de vagas */
    public function ativas(): array
    {
        $ligadas = array_map('trim', explode(',', strtolower((string) configuracao('vagas.fontes'))));
        return array_values(array_intersect(array_keys($this->fontes), $ligadas));
    }

    /**
     * @param ?string[] $chaves quais fontes rodar; null = as ligadas no painel
     * @return array{fontes: array<string, array{rotulo: string, importadas: int, repetidas: int, ignoradas: int, fora_do_tema: int}>, erros: string[]}
     */
    public function importar(?array $chaves = null): array
    {
        $resultado = ['fontes' => [], 'erros' => []];

        foreach ($chaves ?? $this->ativas() as $chave) {
            $fonte = $this->fontes[$chave] ?? null;
            if ($fonte === null) {
                continue;
            }

            $contagem = ['rotulo' => $fonte->rotulo(), 'importadas' => 0, 'repetidas' => 0, 'ignoradas' => 0, 'fora_do_tema' => 0];
            try {
                foreach ($fonte->buscar() as $item) {
                    $this->processar($fonte, $item, $contagem);
                    if ($contagem['importadas'] >= self::LIMITE_POR_FONTE) {
                        break;
                    }
                }
            } catch (Throwable $erro) {
                $resultado['erros'][] = "{$fonte->rotulo()}: {$erro->getMessage()}";
            }
            $resultado['fontes'][$chave] = $contagem;
        }

        return $resultado;
    }

    private function processar(FonteDeVagas $fonte, array $item, array &$contagem): void
    {
        $titulo = trim(html_entity_decode(strip_tags((string) $item['titulo']), ENT_QUOTES | ENT_HTML5));
        $link = trim((string) $item['link']);
        $texto = $this->texto((string) $item['descricao']);

        if ($titulo === '' || $link === '' || (int) $item['publicadoEm'] < time() - self::DIAS_MAXIMOS * 86400) {
            $contagem['ignoradas']++;
            return;
        }
        if (!$this->ehDeTecnologia($titulo, $texto, (array) $item['tags'])) {
            $contagem['fora_do_tema']++;
            return;
        }
        // Fora do Brasil, vaga presencial em outro país não ajuda ninguém: só entram as remotas.
        if (!$item['brasil'] && !$item['remoto']) {
            $contagem['ignoradas']++;
            return;
        }
        if ($this->vagas->existeLink($link)) {
            $contagem['repetidas']++;
            return;
        }

        $vaga = $this->montar($fonte, $item, $titulo, $texto);
        if ($this->vagas->existeSemelhante($vaga->titulo, $vaga->empresa, self::DIAS_MAXIMOS)) {
            $contagem['repetidas']++;
            return;
        }

        $this->vagas->criar($vaga, date('Y-m-d H:i:s', (int) $item['publicadoEm']));
        $contagem['importadas']++;
    }

    private function ehDeTecnologia(string $titulo, string $texto, array $tags): bool
    {
        if (preg_match(self::TITULO_EXCLUIDO, $titulo)) {
            return false;
        }
        if (preg_match(self::TITULO_TECNOLOGIA, $titulo)) {
            return true;
        }
        // Títulos genéricos ("Engenheiro", "Analista") só valem se o resto da vaga for de tecnologia.
        return (bool) preg_match(self::TITULO_GENERICO, $titulo) && ClassificadorVaga::pareceTecnologia($titulo . ' ' . implode(' ', $tags) . ' ' . mb_substr($texto, 0, 1500));
    }

    private function montar(FonteDeVagas $fonte, array $item, string $titulo, string $texto): VagaDTO
    {
        $brasil = (bool) $item['brasil'];
        $remoto = (bool) $item['remoto'];
        $tags = implode(' ', (array) $item['tags']);
        $base = $titulo . ' ' . $tags;

        return new VagaDTO(
            titulo: mb_substr($titulo, 0, 150),
            empresa: mb_substr(trim((string) $item['empresa']) ?: 'Confidencial', 0, 150),
            area: ClassificadorVaga::area($base, AreaAtuacao::Fullstack),
            senioridade: ClassificadorVaga::senioridade($titulo, (string) $item['nivel'] . ' ' . $tags),
            stack: ClassificadorVaga::stack($base . ' ' . mb_substr($texto, 0, 3000)),
            modeloTrabalho: match (true) {
                $remoto => ModeloTrabalho::Remoto,
                !empty($item['hibrido']) => ModeloTrabalho::Hibrido,
                default => ModeloTrabalho::Presencial,
            },
            cidade: !$remoto && $item['local'] ? mb_substr((string) $item['local'], 0, 120) : null,
            salarioMin: $item['salMin'] ? (int) $item['salMin'] : null,
            salarioMax: $item['salMax'] ? (int) $item['salMax'] : null,
            link: mb_substr((string) $item['link'], 0, 500),
            descricao: $this->resumo($texto),
            regiao: $brasil ? RegiaoVagas::Brasil : RegiaoVagas::Internacional,
            moeda: Moeda::tryFrom((string) $item['moeda']) ?? ($brasil ? Moeda::Real : Moeda::Dolar),
            fonte: $fonte->chave(),
        );
    }

    /** HTML da descrição vira texto corrido. */
    private function texto(string $html): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
        $html = preg_replace('#</(p|li|div|h[1-6]|br)>|<br\s*/?>#i', ' ', $html);
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)));
    }

    private function resumo(string $texto): ?string
    {
        if ($texto === '') {
            return null;
        }
        return mb_strlen($texto) > 400 ? rtrim(mb_substr($texto, 0, 397)) . '…' : $texto;
    }
}
