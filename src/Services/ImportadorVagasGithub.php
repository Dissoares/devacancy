<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;
use App\DTOs\VagaDTO;
use App\Enums\AreaAtuacao;
use App\Enums\Moeda;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;
use App\Interfaces\VagaRepositoryInterface;
use RuntimeException;

/**
 * Importa vagas reais publicadas como issues nos repositórios de vagas da comunidade
 * (frontendbr/vagas, backend-br/vagas...). Lê do título e do texto: cargo, empresa, nível,
 * modelo de trabalho, cidade, faixa salarial, stack e um resumo da descrição.
 */
final class ImportadorVagasGithub
{
    /** Anúncios mais antigos que isso nem são importados. */
    private const DIAS_MAXIMOS = 45;

    private const TECNOLOGIAS = [
        'React Native', 'React', 'Angular', 'Vue', 'Next.js', 'Nuxt', 'Svelte', 'TypeScript', 'JavaScript', 'Node.js',
        'NestJS', 'Nest.js', 'PHP', 'Laravel', 'Symfony', 'Python', 'Django', 'FastAPI', 'Flask', 'Java', 'Spring', 'Kotlin',
        'C#', '.NET', 'Go', 'Golang', 'Ruby', 'Rails', 'Elixir', 'Rust', 'Scala', 'Swift', 'SwiftUI', 'Objective-C',
        'Flutter', 'Dart', 'Android', 'iOS', 'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Terraform', 'PostgreSQL',
        'MySQL', 'MongoDB', 'Redis', 'GraphQL', 'Kafka', 'SQL', 'Cypress', 'Selenium', 'Playwright', 'Jest',
        'Tailwind', 'HTML', 'CSS', 'Spark', 'Airflow', 'Power BI', 'Cobol', 'Solidity', 'Mainframe',
        'Delphi', 'SAP', 'ABAP', 'Salesforce', 'Unity', 'C++',
    ];

    public function __construct(
        private readonly ClienteHttp $http,
        private readonly VagaRepositoryInterface $vagas,
    ) {
    }

    /** @return array{importadas: int, repetidas: int, ignoradas: int, erros: string[]} */
    public function importar(): array
    {
        $resumo = ['importadas' => 0, 'repetidas' => 0, 'ignoradas' => 0, 'erros' => []];

        foreach ((array) configuracao('github.repositorios', []) as $repositorio => $areaPadrao) {
            try {
                $issues = $this->http->get(
                    "https://api.github.com/repos/{$repositorio}/issues?state=open&per_page=100&sort=created",
                    $this->cabecalhos(),
                    20,
                );
            } catch (RuntimeException $erro) {
                $resumo['erros'][] = "{$repositorio}: {$erro->getMessage()}";
                continue;
            }

            foreach ($issues as $issue) {
                if (isset($issue['pull_request']) || strtotime($issue['created_at']) < time() - self::DIAS_MAXIMOS * 86400) {
                    $resumo['ignoradas']++;
                    continue;
                }
                // Só issues com cara de vaga: título com [Local/Modelo] ou rótulos, e sem cara de pergunta ou aviso.
                $semCaraDeVaga = !preg_match('/\[[^\]]+\]/u', $issue['title']) && empty($issue['labels']);
                if ($semCaraDeVaga || preg_match('/^\s*(question|d[uú]vida|pergunta|aviso)\b/iu', $issue['title'])) {
                    $resumo['ignoradas']++;
                    continue;
                }
                // Vagas restritas a quem mora nos EUA/Canadá não servem para o público daqui.
                if (preg_match('/somente (para quem mora )?(nos |no )?(EUA|Estados Unidos|Canad)|only (for )?(US|U\.S\.|United States) (residents|citizens)/iu', $issue['title'] . ' ' . ($issue['body'] ?? ''))) {
                    $resumo['ignoradas']++;
                    continue;
                }
                if ($this->vagas->existeLink($issue['html_url'])) {
                    $resumo['repetidas']++;
                    continue;
                }

                $vaga = $this->interpretar($issue, AreaAtuacao::from($areaPadrao));
                // A mesma vaga costuma ser republicada em várias issues.
                if ($this->vagas->existeSemelhante($vaga->titulo, $vaga->empresa, self::DIAS_MAXIMOS)) {
                    $resumo['repetidas']++;
                    continue;
                }
                $this->vagas->criar($vaga, date('Y-m-d H:i:s', strtotime($issue['created_at'])));
                $resumo['importadas']++;
            }
        }

        return $resumo;
    }

    private function interpretar(array $issue, AreaAtuacao $areaPadrao): VagaDTO
    {
        $titulo = (string) $issue['title'];
        $corpo = (string) ($issue['body'] ?? '');
        $rotulos = array_map(fn (array $rotulo) => mb_strtolower($rotulo['name']), $issue['labels'] ?? []);
        $texto = $titulo . "\n" . $corpo;

        [$salarioMin, $salarioMax, $moeda] = $this->salario($corpo);
        $internacional = $moeda !== Moeda::Real || preg_match('/\binternacional\b|\bLATAM\b|\bUSD\b|\bEUA\b|\bCanad[aá]\b|\bUnited States\b/iu', $texto);

        $partes = $this->partesDoTitulo($titulo);

        return new VagaDTO(
            titulo: mb_substr($partes['cargo'], 0, 150),
            empresa: mb_substr($partes['empresa'] ?? 'Confidencial', 0, 150),
            area: $this->area($titulo, $areaPadrao),
            senioridade: $this->senioridade($titulo, $rotulos),
            stack: $this->stack($texto),
            modeloTrabalho: $this->modelo($titulo, $rotulos),
            cidade: $partes['cidade'],
            salarioMin: $salarioMin,
            salarioMax: $salarioMax,
            link: $issue['html_url'],
            descricao: $this->resumo($corpo),
            regiao: $internacional ? RegiaoVagas::Internacional : RegiaoVagas::Brasil,
            moeda: $moeda,
        );
    }

    /** Palavras que nunca são empresa nem cidade (aparecem em colchetes e segmentos do título). */
    private const RUIDO = '/^(remot[oa]|remote|h[ií]brido|hybrid|presencial|on-?site|pj|clt|usd|vaga|hiring|internacional|latam|full-?remote|urgente|freelancer?)$/iu';

    /** Capitais e polos de tecnologia escritos sem UF no título ("[São Paulo]"). */
    private const CIDADES = [
        'sao paulo' => 'São Paulo, SP', 'rio de janeiro' => 'Rio de Janeiro, RJ', 'belo horizonte' => 'Belo Horizonte, MG',
        'curitiba' => 'Curitiba, PR', 'porto alegre' => 'Porto Alegre, RS', 'florianopolis' => 'Florianópolis, SC',
        'recife' => 'Recife, PE', 'salvador' => 'Salvador, BA', 'brasilia' => 'Brasília, DF', 'campinas' => 'Campinas, SP',
        'fortaleza' => 'Fortaleza, CE', 'goiania' => 'Goiânia, GO', 'blumenau' => 'Blumenau, SC', 'joinville' => 'Joinville, SC',
        'sao jose dos campos' => 'São José dos Campos, SP', 'ribeirao preto' => 'Ribeirão Preto, SP', 'manaus' => 'Manaus, AM',
        'vitoria' => 'Vitória, ES', 'londrina' => 'Londrina, PR', 'uberlandia' => 'Uberlândia, MG',
    ];

    /**
     * Separa cargo, empresa e cidade de títulos como
     * "[Remoto] Back-end Pleno na Acme", "[São Paulo/SP] Dev Java | JP&F" ou "Tech Lead | Data Foundation - Evertec".
     * @return array{cargo: string, empresa: ?string, cidade: ?string}
     */
    private function partesDoTitulo(string $titulo): array
    {
        $empresa = null;
        $cidade = null;

        // Colchetes: modelo de trabalho, cidade ou empresa.
        preg_match_all('/\[([^\]]+)\]/u', $titulo, $colchetes);
        $resto = trim(preg_replace(['/\[[^\]]*\]/u', '/^\s*\([^)]*\)\s*/u'], '', $titulo));
        foreach ($colchetes[1] as $trecho) {
            $trecho = trim(preg_replace('/^(h[ií]brido|presencial|remoto)(\s*(ou|e|\/)\s*(h[ií]brido|presencial|remoto))*\s*[-–:!\/]?\s*/iu', '', $trecho));
            if ($trecho === '' || preg_match(self::RUIDO, $trecho) || preg_match('/\b(somente|only|apenas)\b/iu', $trecho)) {
                continue;
            }
            $local = $this->cidadeDe($trecho);
            if ($local !== null) {
                $cidade ??= $local;
            } elseif ($empresa === null && $this->pareceEmpresa($trecho)) {
                $empresa = $trecho;
            }
        }

        // Segmentos separados por "|": um é o cargo, os outros podem ser cidade, salário ou empresa.
        $segmentos = array_values(array_filter(array_map('trim', explode('|', $resto))));
        $indiceCargo = 0;
        foreach ($segmentos as $indice => $segmento) {
            if (preg_match('/dev|desenvolv|engineer|engenheir|program|tech lead|\blead\b|arquitet|analista|\bqa\b|tester|\bsre\b|devops|cientista|full|front|back|mobile|software/iu', $segmento)) {
                $indiceCargo = $indice;
                break;
            }
        }
        $cargo = $segmentos[$indiceCargo] ?? $resto;
        foreach ($segmentos as $indice => $segmento) {
            if ($indice === $indiceCargo || preg_match(self::RUIDO, $segmento) || preg_match('/\d.*(k|mil|\/m[eê]s|\/month)|[$€]/iu', $segmento)) {
                continue;
            }
            if (($local = $this->cidadeDe($segmento)) !== null) {
                $cidade ??= $local;
            } elseif ($empresa === null && $indice > $indiceCargo && $this->pareceEmpresa($segmento)) {
                $empresa = $segmento;
            }
        }

        // Dentro do cargo: "(via X)", cidade no fim, "na Empresa", "(Empresa)", "- Empresa" e sobras como "USD".
        $cargo = trim(preg_replace('/\s*\(via [^)]*\)\s*$/iu', '', $cargo));
        if (preg_match('/\s[-–]\s*([\p{Lu}][\p{L}\s.]{2,40}?)\s*[-–\/]\s*([A-Z]{2})$/u', $cargo, $m)) {
            $cidade ??= trim($m[1]) . ', ' . $m[2];
            $cargo = trim(substr($cargo, 0, -strlen($m[0])));
        }
        if (preg_match('/\s(?:na|no|at|@)\s+([^()\[\]–-]{2,40}?)\s*$/iu', $cargo, $m)) {
            $empresa ??= trim($m[1]);
            $cargo = trim(substr($cargo, 0, -strlen($m[0])));
        }
        $cargo = trim(preg_replace('/[\s(-]*\bUSD\b\)?\s*$/u', '', $cargo));
        if (preg_match('/\s*\(([^),]{2,30})\)+\s*$/u', $cargo, $m) && !preg_match('/\b(remot|h[ií]brid|pj|clt)/iu', $m[1]) && !$this->ehTecnologia($m[1])) {
            $empresa ??= trim(preg_replace('/\bUSD\b/u', '', $m[1]));
            $cargo = trim(substr($cargo, 0, -strlen($m[0])));
        }
        if (preg_match('/\s*[-–]\s+([^-–]{2,40})$/u', $cargo, $m) && !preg_match('/,|\b(remot|h[ií]brid|presencial|pj|clt|j[uú]nior|pleno|s[eê]nior)/iu', $m[1]) && !$this->ehTecnologia($m[1])) {
            $empresa ??= trim($m[1]);
            $cargo = trim(substr($cargo, 0, -strlen($m[0])));
        }

        $cargo = trim(preg_replace('/\s+/u', ' ', $cargo), " -–|•");
        $empresa = $empresa !== null ? trim(preg_replace('/[^\p{L}\p{N}\s&.\'-]/u', '', $empresa)) : null;

        return ['cargo' => $cargo !== '' ? $cargo : trim($titulo), 'empresa' => $empresa ?: null, 'cidade' => $cidade];
    }

    /** "Curitiba/PR", "Curitiba - PR", "Curitiba, PR" ou uma cidade conhecida sem UF viram "Cidade, UF". */
    private function cidadeDe(string $texto): ?string
    {
        $texto = trim($texto);
        if (preg_match('/^([\p{Lu}][\p{L}\s.\']{2,40}?)\s*[\/,–-]\s*([A-Z]{2})$/u', $texto, $m)) {
            return trim($m[1]) . ', ' . $m[2];
        }
        // "em São Paulo", "São Paulo (Híbrido)" e "São Paulo - SP" sem barra também valem.
        $limpo = trim(preg_replace(['/^em\s+/iu', '/\s*\(.*\)\s*$/u', '/\s+[A-Z]{2}$/u'], '', $texto));
        $chave = mb_strtolower(sem_acentos($limpo));
        if (isset(self::CIDADES[$chave])) {
            return self::CIDADES[$chave];
        }
        return null;
    }

    /** Descarta UF solta, "em SP", "100% remota", "Home Office...", textos longos e tecnologias. */
    private function pareceEmpresa(string $texto): bool
    {
        $texto = trim($texto);
        return $texto !== ''
            && mb_strlen($texto) <= 40
            && !preg_match(self::RUIDO, $texto)
            && !preg_match('/^[A-Z]{2}$|^em\s|^\d|home office|remot|h[ií]brid|presencial|tempo determinado|meses|vaga/iu', $texto)
            && $this->cidadeDe($texto) === null
            && !$this->ehTecnologia($texto);
    }

    private function ehTecnologia(string $texto): bool
    {
        foreach (self::TECNOLOGIAS as $tecnologia) {
            if (stripos($texto, $tecnologia) !== false) {
                return true;
            }
        }
        return (bool) preg_match('/\b(llm|ia|ai|ux|ui|healthtech|fintech|saas|ehr|emr)\b/iu', $texto);
    }
    private function area(string $titulo, AreaAtuacao $padrao): AreaAtuacao
    {
        return match (true) {
            (bool) preg_match('/full[\s-]?stack/iu', $titulo) => AreaAtuacao::Fullstack,
            (bool) preg_match('/\b(mobile|android|ios|flutter|react native)\b/iu', $titulo) => AreaAtuacao::Mobile,
            (bool) preg_match('/\b(qa|quality|testes?|tester)\b/iu', $titulo) => AreaAtuacao::Qa,
            (bool) preg_match('/\b(devops|sre|cloud|infra|infraestrutura|plataforma)\b/iu', $titulo) => AreaAtuacao::DevOps,
            (bool) preg_match('/\b(dados|data|machine learning|ml|ia|ai)\b/iu', $titulo) => AreaAtuacao::Dados,
            (bool) preg_match('/front[\s-]?end/iu', $titulo) => AreaAtuacao::Frontend,
            (bool) preg_match('/back[\s-]?end/iu', $titulo) => AreaAtuacao::Backend,
            default => $padrao,
        };
    }

    /** O título manda; sem nível no título, usa o rótulo mais baixo da issue; sem nada, pleno. */
    private function senioridade(string $titulo, array $rotulos): Senioridade
    {
        $mapa = [
            Senioridade::Estagio->value => '/est[aá]gi|trainee|\bintern\b|internship/iu',
            Senioridade::Junior->value => '/j[uú]nior|\bjr\b/iu',
            Senioridade::Pleno->value => '/pleno|\bmid\b|\bpl\b/iu',
            Senioridade::Senior->value => '/s[eê]nior|\bsr\b/iu',
            Senioridade::Especialista->value => '/especialista|tech lead|staff|principal|l[ií]der/iu',
        ];

        foreach ($mapa as $nivel => $padrao) {
            if (preg_match($padrao, $titulo)) {
                return Senioridade::from($nivel);
            }
        }
        foreach ($mapa as $nivel => $padrao) {
            foreach ($rotulos as $rotulo) {
                if (preg_match($padrao, $rotulo)) {
                    return Senioridade::from($nivel);
                }
            }
        }
        return Senioridade::Pleno;
    }

    private function modelo(string $titulo, array $rotulos): ModeloTrabalho
    {
        $alvo = $titulo . ' ' . implode(' ', $rotulos);
        return match (true) {
            (bool) preg_match('/remot/iu', $alvo) => ModeloTrabalho::Remoto,
            (bool) preg_match('/h[ií]brid/iu', $alvo) => ModeloTrabalho::Hibrido,
            default => ModeloTrabalho::Presencial,
        };
    }

    /** "R$ 8.000 a R$ 12.000", "USD 4.000 a 8.000", "até R$ 10 mil". @return array{0: ?int, 1: ?int, 2: Moeda} */
    private function salario(string $corpo): array
    {
        $numero = fn (string $valor, ?string $mil = null): int => (int) round((float) str_replace(['.', ','], ['', '.'], $valor) * ($mil ? 1000 : 1));
        $moedas = ['R\$' => Moeda::Real, '(?:US\$|USD|\$)' => Moeda::Dolar, '(?:€|EUR)' => Moeda::Euro];

        foreach ($moedas as $simbolo => $moeda) {
            $valor = '([\d.,]+)\s*(k|mil)?';
            if (preg_match("/{$simbolo}\s*{$valor}\s*(?:a|-|–|até|to)\s*(?:{$simbolo})?\s*{$valor}/iu", $corpo, $m)) {
                $minimo = $numero($m[1], ($m[2] ?? '') ?: null);
                $maximo = $numero($m[3], ($m[4] ?? '') ?: null);
                // Valores muito baixos são por hora ("USD 30 a 100"): não servem como salário mensal/anual.
                if ($minimo >= 500 && $maximo >= $minimo) {
                    return [$minimo, $maximo, $moeda];
                }
            }
            if (preg_match("/(?:até|up to)\s*{$simbolo}\s*{$valor}/iu", $corpo, $m)) {
                $maximo = $numero($m[1], ($m[2] ?? '') ?: null);
                if ($maximo >= 500) {
                    return [null, $maximo, $moeda];
                }
            }
        }
        return [null, null, preg_match('/\bUSD\b|US\$/', $corpo) ? Moeda::Dolar : Moeda::Real];
    }

    /** @return string[] até 8 tecnologias citadas, na ordem da lista (as mais específicas primeiro). */
    private function stack(string $texto): array
    {
        $achadas = [];
        foreach (self::TECNOLOGIAS as $tecnologia) {
            $padrao = '/(?<![\w.#])' . preg_quote($tecnologia, '/') . '(?![\w#])/i';
            if (preg_match($padrao, $texto)) {
                $achadas[] = $tecnologia === 'Golang' ? 'Go' : $tecnologia;
            }
        }
        if (in_array('React Native', $achadas, true)) {
            $achadas = array_values(array_diff($achadas, ['React']));
        }
        return array_slice(array_values(array_unique($achadas)), 0, 8);
    }

    /** Primeiro parágrafo útil da descrição, sem markdown, até ~400 caracteres. */
    private function resumo(string $corpo): ?string
    {
        $texto = preg_replace('/^#+\s*(descri[cç][aã]o da vaga|descri[cç][aã]o|sobre a vaga|job description)\s*:?\s*$/imu', '', $corpo);
        $texto = preg_replace(['/!\[[^\]]*\]\([^)]*\)/', '/\[([^\]]*)\]\([^)]*\)/', '/[#*_`>|]+/', '/<[^>]+>/'], ['', '$1', '', ''], $texto);
        $paragrafos = array_filter(array_map('trim', preg_split('/\n\s*\n/', $texto)), fn (string $p) => mb_strlen($p) > 60);
        $primeiro = preg_replace('/\s+/u', ' ', (string) reset($paragrafos));

        if ($primeiro === '') {
            return null;
        }
        return mb_strlen($primeiro) > 400 ? rtrim(mb_substr($primeiro, 0, 397)) . '…' : $primeiro;
    }

    private function cabecalhos(): array
    {
        $token = (string) configuracao('github.token');
        return array_filter(['Accept: application/vnd.github+json', $token !== '' ? "Authorization: Bearer {$token}" : null]);
    }
}
