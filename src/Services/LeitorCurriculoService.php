<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClassificadorVaga;
use App\Enums\AreaAtuacao;
use App\Enums\NivelIngles;
use App\Enums\Senioridade;

/**
 * Lê o TEXTO de um currículo (de um PDF comum ou do PDF que o LinkedIn gera) e separa em campos do perfil.
 * Sem inteligência artificial: são regras sobre seções, datas e um vocabulário de tecnologias. Por isso o resultado
 * sempre passa por uma tela de revisão antes de ser aplicado.
 */
final class LeitorCurriculoService
{
    private const MESES = [
        'janeiro' => 1, 'jan' => 1, 'fevereiro' => 2, 'fev' => 2, 'feb' => 2, 'março' => 3, 'marco' => 3, 'mar' => 3,
        'abril' => 4, 'abr' => 4, 'apr' => 4, 'maio' => 5, 'mai' => 5, 'may' => 5, 'junho' => 6, 'jun' => 6,
        'julho' => 7, 'jul' => 7, 'agosto' => 8, 'ago' => 8, 'aug' => 8, 'setembro' => 9, 'set' => 9, 'sep' => 9,
        'outubro' => 10, 'out' => 10, 'oct' => 10, 'novembro' => 11, 'nov' => 11, 'dezembro' => 12, 'dez' => 12, 'dec' => 12,
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
    ];

    private const ESTADOS = [
        'acre' => 'AC', 'alagoas' => 'AL', 'amapá' => 'AP', 'amazonas' => 'AM', 'bahia' => 'BA', 'ceará' => 'CE',
        'distrito federal' => 'DF', 'espírito santo' => 'ES', 'goiás' => 'GO', 'maranhão' => 'MA', 'mato grosso' => 'MT',
        'mato grosso do sul' => 'MS', 'minas gerais' => 'MG', 'pará' => 'PA', 'paraíba' => 'PB', 'paraná' => 'PR',
        'pernambuco' => 'PE', 'piauí' => 'PI', 'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS', 'rondônia' => 'RO', 'roraima' => 'RR', 'santa catarina' => 'SC',
        'são paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO',
    ];

    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    /** Tecnologias comuns em currículo que o classificador de vagas não conhece. */
    private const TECNOLOGIAS_EXTRAS = [
        'Git', 'Linux', 'Bootstrap', 'jQuery', 'WordPress', 'Express', 'Redux', 'RabbitMQ', 'Elasticsearch', 'Oracle',
        'SQL Server', 'Firebase', 'Sass', 'Vite', 'Webpack', 'Hibernate', 'JUnit', 'Pandas', 'TensorFlow', 'PyTorch',
        'CI/CD', 'Jenkins', 'GitHub Actions', 'GitLab', 'Nginx', 'Apache', 'Prisma', 'Strapi', 'Supabase', 'MariaDB',
    ];

    /** Nomes que também são palavras comuns: só valem se aparecerem exatamente assim, com maiúscula. */
    private const AMBIGUAS = ['Go', 'Swift', 'Spark', 'Unity', 'Rails', 'Dart', 'Scala', 'Express', 'Apache', 'Ruby', 'Rust', 'Oracle', 'Git'];

    private const PALAVRAS_DE_CARGO = '/\b(desenvolvedor|developer|programador|engenheir[oa]|engineer|analista|arquitet[oa]|architect|tech lead|devops|sre|qa|tester|cientista|data|full[\s-]?stack|front[\s-]?end|back[\s-]?end|mobile|software|dev|coordenador|gerente|l[ií]der|especialista|consultor|desenvolvimento)\b/iu';

    private const SECOES = [
        'resumo' => '/^(resumo( profissional)?|sobre( mim)?|perfil( profissional)?|objetivo( profissional)?|summary|about( me)?|profile|professional summary)$/iu',
        'experiencia' => '/^(experi[eê]ncias?( profission(?:ais|al))?|hist[oó]rico profissional|experi[eê]ncia de trabalho|experience|work experience|professional experience|employment( history)?)$/iu',
        'formacao' => '/^(forma[cç][aã]o( acad[eê]mica)?|educa[cç][aã]o|escolaridade|education|academic background)$/iu',
        'competencias' => '/^(principais compet[eê]ncias|compet[eê]ncias|habilidades( t[eé]cnicas)?|skills|top skills|tecnologias|conhecimentos( t[eé]cnicos)?|technical skills|stack|compet[eê]ncias t[eé]cnicas)$/iu',
        'idiomas' => '/^(idiomas|languages|l[ií]nguas)$/iu',
        'outros' => '/^(certifica[cç][oõ]es?|licen[cç]as e certifica[cç][oõ]es|licenses( &| and) certifications|cursos|projetos|projects|publica[cç][oõ]es|publications|honors( &| and) awards|pr[eê]mios|contato|contact|atividades|volunt[aá]rio|voluntariado|refer[eê]ncias|informa[cç][oõ]es adicionais|additional information|interesses)$/iu',
    ];

    /**
     * @return array{
     *   nome: ?string, telefone: ?string, cidade: ?string, cargo: ?string, area: ?string, senioridade: ?string, anos: ?int,
     *   stack: string[], idiomas: string[], nivel_ingles: ?string, linkedin_url: ?string, github_url: ?string,
     *   portfolio_url: ?string, resumo: ?string, experiencias: array<int, array>, formacao: array<int, array>
     * }
     */
    public function ler(string $texto): array
    {
        $linhas = $this->linhas($texto);
        $corpo = implode("\n", $linhas);
        $secoes = $this->secoes($linhas);

        [$nome, $cargo, $local] = $this->cabecalho($linhas);
        $experiencias = $this->experiencias($secoes['experiencia'] ?? []);
        $anos = $this->anosDeExperiencia($experiencias, $corpo);
        $cargo ??= $experiencias[0]['cargo'] ?? null;

        $stack = $this->stack($corpo, $secoes['competencias'] ?? []);
        [$idiomas, $nivelIngles] = $this->idiomas($secoes['idiomas'] ?? [], $corpo);

        return [
            'nome' => $nome,
            'telefone' => $this->telefone($corpo),
            'cidade' => $local !== null ? $this->normalizarCidade($local) : $this->cidadeSolta($linhas),
            'cargo' => $cargo !== null ? $this->limparCargo($cargo) : null,
            'area' => $cargo !== null || $stack !== [] ? $this->area($cargo ?? '', $stack)->value : null,
            'senioridade' => ($cargo !== null || $anos !== null) ? $this->senioridade($cargo ?? '', $anos)->value : null,
            'anos' => $anos,
            'stack' => $stack,
            'idiomas' => $idiomas,
            'nivel_ingles' => $nivelIngles?->value,
            'linkedin_url' => $this->linkedin($corpo),
            'github_url' => $this->github($corpo),
            'portfolio_url' => $this->portfolio($corpo),
            'resumo' => $this->resumo($secoes['resumo'] ?? []),
            'experiencias' => $experiencias,
            'formacao' => $this->formacao($secoes['formacao'] ?? []),
        ];
    }

    // ---------------------------------------------------------------- Texto e seções

    /** @return string[] linhas limpas, sem número de página e sem linhas vazias repetidas */
    private function linhas(string $texto): array
    {
        $texto = str_replace(["\r\n", "\r", "\f", "\u{00A0}", "\u{200B}"], ["\n", "\n", "\n", ' ', ''], $texto);
        // O LinkedIn quebra o endereço do perfil em duas linhas.
        $texto = preg_replace('~(linkedin\.com/in/[\w\-%]*-)\s*\n\s*([\w\-%]+)~iu', '$1$2', $texto) ?? $texto;
        $texto = preg_replace('/^[\s•·▪●◦‣\-–—*]+(?=\S)/mu', '', $texto) ?? $texto;

        $linhas = [];
        foreach (explode("\n", $texto) as $linha) {
            $linha = trim(preg_replace('/[ \t]+/u', ' ', $linha) ?? '');
            if ($linha === '' || preg_match('/^(page|p[aá]gina)\s+\d+\s*(of|de)\s*\d+$/iu', $linha) || preg_match('/^\d+\s*\/\s*\d+$/', $linha)) {
                continue;
            }
            $linhas[] = $linha;
        }
        return $linhas;
    }

    /** @param string[] $linhas @return array<string, string[]> */
    private function secoes(array $linhas): array
    {
        $secoes = [];
        $atual = null;
        foreach ($linhas as $linha) {
            $cabecalho = trim($linha, " :");
            $achou = null;
            foreach (self::SECOES as $nome => $padrao) {
                if (mb_strlen($cabecalho) <= 40 && preg_match($padrao, $cabecalho)) {
                    $achou = $nome;
                    break;
                }
            }
            if ($achou !== null) {
                $atual = $achou === 'outros' ? null : $achou;
                if ($atual !== null) {
                    $secoes[$atual] ??= [];
                }
                continue;
            }
            if ($atual !== null) {
                $secoes[$atual][] = $linha;
            }
        }
        return $secoes;
    }

    // ---------------------------------------------------------------- Cabeçalho: nome, cargo e local

    /** @param string[] $linhas @return array{0: ?string, 1: ?string, 2: ?string} */
    private function cabecalho(array $linhas): array
    {
        $topo = array_slice($linhas, 0, 60);

        // Padrão do LinkedIn e de muitos modelos: nome, logo abaixo o cargo e depois o local.
        foreach ($topo as $i => $linha) {
            if ($this->pareceNome($linha) && isset($topo[$i + 1]) && $this->pareceCargo($topo[$i + 1])) {
                $local = $topo[$i + 2] ?? null;
                return [$linha, $topo[$i + 1], $local !== null && $this->pareceLocal($local) ? $local : null];
            }
        }

        $nome = null;
        foreach (array_slice($linhas, 0, 8) as $linha) {
            if ($this->pareceNome($linha)) {
                $nome = $linha;
                break;
            }
        }
        $cargo = null;
        foreach (array_slice($linhas, 0, 15) as $linha) {
            if ($this->pareceCargo($linha)) {
                $cargo = $linha;
                break;
            }
        }
        return [$nome, $cargo, null];
    }

    private function pareceNome(string $linha): bool
    {
        if (mb_strlen($linha) < 5 || mb_strlen($linha) > 60 || preg_match('/[\d@:\/|()]/u', $linha) || $this->ehCabecalhoDeSecao($linha)) {
            return false;
        }
        if (preg_match('/curr[ií]culo|curriculum|resume|contato|contact|linkedin|github/iu', $linha) || preg_match(self::PALAVRAS_DE_CARGO, $linha)) {
            return false;
        }
        $palavras = preg_split('/\s+/u', $linha) ?: [];
        if (count($palavras) < 2 || count($palavras) > 6) {
            return false;
        }
        foreach ($palavras as $palavra) {
            $particula = in_array(mb_strtolower($palavra), ['de', 'da', 'do', 'dos', 'das', 'e', 'van', 'von', 'di', 'del', 'la'], true);
            if (!$particula && !preg_match('/^\p{Lu}[\p{L}\'.-]*$/u', $palavra)) {
                return false;
            }
        }
        return true;
    }

    private function pareceCargo(string $linha): bool
    {
        return mb_strlen($linha) >= 5 && mb_strlen($linha) <= 140
            && !preg_match('~[@]|https?://|www\.~iu', $linha)
            && !$this->ehCabecalhoDeSecao($linha)
            && preg_match(self::PALAVRAS_DE_CARGO, $linha) === 1;
    }

    private function pareceLocal(string $linha): bool
    {
        return mb_strlen($linha) <= 60 && count(preg_split('/\s+/u', $linha) ?: []) <= 6 && !preg_match('/[\d@.]|https?:/u', $linha) && !$this->ehCabecalhoDeSecao($linha)
            && (bool) preg_match('/^[\p{L}\s.\'-]+(?:,|\s[-–]\s|\/)\s*[\p{L}\s.\'-]+(?:,\s*[\p{L}\s.\'-]+)?$/u', $linha);
    }

    /** Local logo abaixo do período: "Fortaleza, Ceará, Brasil" ou só "São Paulo" (curto, sem pontuação de frase). */
    private function pareceLocalCurto(string $linha): bool
    {
        if ($this->pareceLocal($linha)) {
            return true;
        }
        return mb_strlen($linha) <= 40 && count(preg_split('/\s+/u', $linha) ?: []) <= 3
            && (bool) preg_match('/^\p{Lu}[\p{L}\s.\'-]+$/u', $linha) && !preg_match('/[.:;!?]/u', $linha);
    }

    private function ehCabecalhoDeSecao(string $linha): bool
    {
        $limpa = trim($linha, ' :');
        foreach (self::SECOES as $padrao) {
            if (preg_match($padrao, $limpa)) {
                return true;
            }
        }
        return false;
    }

    private function limparCargo(string $cargo): string
    {
        // "Desenvolvedor Back-end | PHP | Laravel" ou "Dev Sênior @ Empresa": fica só o cargo.
        $cargo = preg_split('/\s[|•·@]\s|\s+(?:at|na|no|em)\s+(?=\p{Lu})/u', $cargo)[0] ?? $cargo;
        return mb_substr(trim($cargo, " -–—|"), 0, 120);
    }

    /** "Fortaleza, Ceará, Brasil" → "Fortaleza, CE". */
    private function normalizarCidade(string $local): ?string
    {
        $partes = array_map('trim', preg_split('/\s*,\s*|\s+[-–]\s+|\//u', $local) ?: []);
        $partes = array_values(array_filter($partes, fn (string $p) => $p !== '' && !preg_match('/^(brasil|brazil)$/iu', $p)));
        if ($partes === []) {
            return null;
        }
        $cidade = $partes[0];
        $uf = null;
        if (isset($partes[1])) {
            $uf = in_array(mb_strtoupper($partes[1]), self::UFS, true) ? mb_strtoupper($partes[1]) : (self::ESTADOS[mb_strtolower($partes[1])] ?? null);
        }
        return $uf !== null ? "{$cidade}, {$uf}" : $cidade;
    }

    /** @param string[] $linhas */
    private function cidadeSolta(array $linhas): ?string
    {
        foreach (array_slice($linhas, 0, 25) as $linha) {
            if (preg_match('/([\p{Lu}][\p{L}\s\']{2,30}?)\s*[-–,\/]\s*(' . implode('|', self::UFS) . ')\b(?!\w)/u', $linha, $m)) {
                return trim($m[1]) . ', ' . $m[2];
            }
        }
        return null;
    }

    // ---------------------------------------------------------------- Contato e links

    private function telefone(string $corpo): ?string
    {
        if (preg_match_all('/(?<![\d])(?:\+?55[\s.-]?)?\(?(\d{2})\)?[\s.-]?(9?\d{4})[\s.-]?(\d{4})(?![\d])/u', $corpo, $m, PREG_SET_ORDER)) {
            foreach ($m as $achado) {
                $digitos = $achado[1] . $achado[2] . $achado[3];
                // Evita pegar intervalos de datas ("2019 2021 2022"): o DDD válido nunca começa com 0 ou 1.
                if (strlen($digitos) >= 10 && !preg_match('/^(19|20)\d{2}/', $digitos) && $digitos[0] !== '0') {
                    return '+55' . $digitos;
                }
            }
        }
        return null;
    }

    private function linkedin(string $corpo): ?string
    {
        return preg_match('~(?:https?://)?(?:[a-z]{2,3}\.)?linkedin\.com/in/([\w\-%]+)~iu', $corpo, $m)
            ? 'https://www.linkedin.com/in/' . rtrim($m[1], '-') : null;
    }

    private function github(string $corpo): ?string
    {
        return preg_match('~(?:https?://)?(?:www\.)?github\.com/([A-Za-z0-9][A-Za-z0-9\-]{0,38})(?![\w\-])~iu', $corpo, $m)
            && !in_array(mb_strtolower($m[1]), ['features', 'about', 'pricing', 'topics', 'orgs', 'sponsors'], true)
            ? 'https://github.com/' . $m[1] : null;
    }

    private function portfolio(string $corpo): ?string
    {
        if (!preg_match_all('~(?:https?://|www\.)[^\s,;)\]>]+~iu', $corpo, $m)) {
            return null;
        }
        foreach ($m[0] as $url) {
            if (preg_match('~linkedin\.com|github\.com|wa\.me|mailto:|facebook\.com|instagram\.com|twitter\.com|x\.com|youtube\.com|google\.com|gmail\.|hotmail\.|outlook\.~iu', $url)) {
                continue;
            }
            $url = rtrim($url, '.');
            return preg_match('~^https?://~i', $url) ? $url : 'https://' . $url;
        }
        return null;
    }

    // ---------------------------------------------------------------- Resumo

    /** @param string[] $linhas */
    private function resumo(array $linhas): ?string
    {
        if ($linhas === []) {
            return null;
        }
        $texto = trim(preg_replace('/\s+/u', ' ', implode(' ', $linhas)) ?? '');
        if (mb_strlen($texto) < 30) {
            return null;
        }
        return mb_strlen($texto) > 1500 ? rtrim(mb_substr($texto, 0, 1497)) . '…' : $texto;
    }

    // ---------------------------------------------------------------- Tecnologias, área e senioridade

    /**
     * @param string[] $competencias linhas da seção de competências, se existir
     * @return string[]
     */
    private function stack(string $corpo, array $competencias): array
    {
        $achadas = ClassificadorVaga::stack($corpo, 40);

        foreach (self::TECNOLOGIAS_EXTRAS as $tecnologia) {
            $padrao = '/(?<![\w.#])' . preg_quote($tecnologia, '/') . '(?![\w#])/' . (in_array($tecnologia, self::AMBIGUAS, true) ? '' : 'i');
            if (preg_match($padrao, $corpo)) {
                $achadas[] = $tecnologia;
            }
        }

        // Nomes que também são palavras comuns só valem com a grafia exata (ex.: "Go", não "go to market").
        $achadas = array_filter($achadas, function (string $tecnologia) use ($corpo): bool {
            if (!in_array($tecnologia, self::AMBIGUAS, true)) {
                return true;
            }
            return (bool) preg_match('/(?<![\w.#])' . preg_quote($tecnologia, '/') . '(?![\w#])/', $corpo);
        });

        // As citadas na seção de competências vêm primeiro: são o que a própria pessoa destacou.
        $destacadas = [];
        $textoCompetencias = implode("\n", $competencias);
        foreach ($achadas as $tecnologia) {
            if ($textoCompetencias !== '' && stripos($textoCompetencias, $tecnologia) !== false) {
                $destacadas[] = $tecnologia;
            }
        }
        $ordenadas = array_values(array_unique([...$destacadas, ...$achadas]));
        return array_slice($ordenadas, 0, 20);
    }

    /** @param string[] $stack */
    private function area(string $cargo, array $stack): AreaAtuacao
    {
        $contem = fn (array $lista): int => count(array_intersect(array_map('mb_strtolower', $stack), array_map('mb_strtolower', $lista)));
        $front = $contem(['React', 'Angular', 'Vue', 'Next.js', 'Nuxt', 'Svelte', 'HTML', 'CSS', 'Tailwind', 'Sass', 'Bootstrap', 'Redux', 'jQuery']);
        $back = $contem(['Node.js', 'PHP', 'Laravel', 'Symfony', 'Python', 'Django', 'FastAPI', 'Flask', 'Java', 'Spring', 'C#', '.NET', 'Go', 'Ruby', 'Rails', 'Elixir', 'NestJS', 'Express', 'Kotlin']);
        $mobile = $contem(['Flutter', 'Dart', 'Android', 'iOS', 'Swift', 'SwiftUI', 'React Native', 'Objective-C']);
        $infra = $contem(['Docker', 'Kubernetes', 'Terraform', 'AWS', 'Azure', 'GCP', 'Jenkins', 'CI/CD', 'Linux', 'Nginx']);
        $dados = $contem(['Spark', 'Airflow', 'Power BI', 'Pandas', 'TensorFlow', 'PyTorch']);

        $padrao = match (true) {
            $mobile >= 2 => AreaAtuacao::Mobile,
            $dados >= 2 => AreaAtuacao::Dados,
            $infra >= 3 && $back + $front <= 1 => AreaAtuacao::DevOps,
            $front >= 1 && $back >= 1 => AreaAtuacao::Fullstack,
            $front > $back => AreaAtuacao::Frontend,
            $back > 0 => AreaAtuacao::Backend,
            default => AreaAtuacao::Fullstack,
        };

        return $cargo !== '' ? ClassificadorVaga::area($cargo, $padrao) : $padrao;
    }

    private function senioridade(string $cargo, ?int $anos): Senioridade
    {
        $doCargo = $cargo !== '' ? ClassificadorVaga::senioridadeOuNulo($cargo) : null;
        if ($doCargo !== null) {
            return $doCargo;
        }
        return match (true) {
            $anos === null => Senioridade::Pleno,
            $anos <= 2 => Senioridade::Junior,
            $anos <= 5 => Senioridade::Pleno,
            default => Senioridade::Senior,
        };
    }

    // ---------------------------------------------------------------- Idiomas

    /**
     * @param string[] $linhas seção de idiomas
     * @return array{0: string[], 1: ?NivelIngles}
     */
    private function idiomas(array $linhas, string $corpo): array
    {
        $mapa = [
            'pt' => '/\b(portugu[eê]s|portuguese)\b/iu', 'en' => '/\b(ingl[eê]s|english)\b/iu', 'es' => '/\b(espanhol|spanish|espa[nñ]ol)\b/iu',
            'fr' => '/\b(franc[eê]s|french)\b/iu', 'de' => '/\b(alem[aã]o|german|deutsch)\b/iu',
        ];

        $codigos = [];
        $nivelIngles = null;
        // Com seção própria de idiomas, vale ela. Sem seção, só frases explícitas como "Inglês avançado".
        $fonte = $linhas !== [] ? $linhas : (preg_match_all('/^.*\b(ingl[eê]s|english|espanhol|spanish|franc[eê]s|french|alem[aã]o|german)\b.*\b(nativo|native|fluente|fluent|avan[cç]ado|advanced|intermedi[aá]rio|intermediate|b[aá]sico|basic)\b.*$/imu', $corpo, $m) ? $m[0] : []);

        foreach ($fonte as $indice => $linha) {
            foreach ($mapa as $codigo => $padrao) {
                if (!preg_match($padrao, $linha)) {
                    continue;
                }
                $codigos[] = $codigo;
                if ($codigo === 'en') {
                    $contexto = $linha . ' ' . ($fonte[$indice + 1] ?? '');
                    $nivelIngles = $this->nivelDoInglesNoTexto($contexto);
                }
            }
        }
        return [array_values(array_unique($codigos)), $nivelIngles];
    }

    private function nivelDoInglesNoTexto(string $contexto): ?NivelIngles
    {
        return match (true) {
            (bool) preg_match('/nativ|native|bilingu|bilingual|fluente|fluent|full professional|profici[eê]ncia plena/iu', $contexto) => NivelIngles::Fluente,
            (bool) preg_match('/avan[cç]ado|advanced|professional working|profici[eê]ncia profissional/iu', $contexto) => NivelIngles::Avancado,
            (bool) preg_match('/intermedi[aá]rio|intermediate|limited working|profici[eê]ncia limitada/iu', $contexto) => NivelIngles::Intermediario,
            (bool) preg_match('/b[aá]sico|basic|elementar|elementary/iu', $contexto) => NivelIngles::Basico,
            default => null,
        };
    }

    // ---------------------------------------------------------------- Experiência

    /**
     * Cada linha com um período de datas é o "âncora" de uma experiência: o cargo e a empresa ficam logo acima
     * (padrão do LinkedIn) ou na própria linha do período; a descrição vai até a próxima experiência.
     * @param string[] $linhas
     * @return array<int, array{cargo: string, empresa: string, inicio: ?string, fim: ?string, atual: bool, descricao: ?string}>
     */
    private function experiencias(array $linhas): array
    {
        $ancoras = [];
        foreach ($linhas as $i => $linha) {
            $periodo = $this->periodo($linha);
            if ($periodo !== null) {
                $solto = trim(preg_replace('/\(.*?\)/u', '', $periodo['resto']) ?? '', " -–—|,·•");
                $ancoras[] = ['indice' => $i, 'solto' => mb_strlen($solto) > 3 ? $solto : '', ...$periodo];
            }
        }

        $experiencias = [];
        foreach ($ancoras as $n => $ancora) {
            $i = $ancora['indice'];
            $proxima = $ancoras[$n + 1]['indice'] ?? count($linhas);

            $cargo = '';
            $empresa = '';
            if ($ancora['solto'] !== '') {
                [$cargo, $empresa] = $this->cargoEEmpresa($ancora['solto']);
            }
            $descricaoDe = $i + 1;
            if ($cargo === '') {
                // O período fica numa linha só dele: o cargo (e a empresa) estão acima. Padrão do LinkedIn: empresa,
                // cargo e depois o período; em muitos modelos, uma linha só "Cargo - Empresa".
                if ($this->linhasDeCabecalho($linhas, $i) === 1) {
                    [$cargo, $empresa] = $this->cargoEEmpresa($linhas[$i - 1] ?? '');
                } else {
                    $cargo = $linhas[$i - 1] ?? '';
                    $empresa = $linhas[$i - 2] ?? '';
                }
                if ($this->ehCabecalhoDeSecao($empresa) || $this->periodo($empresa) !== null) {
                    $empresa = '';
                }
                if ($this->periodo($cargo) !== null) {
                    continue;
                }
                // A linha de baixo pode ser o local ("Fortaleza, Ceará" ou só "São Paulo"); a descrição começa depois.
                if (isset($linhas[$i + 1]) && $i + 1 < $proxima && $this->pareceLocalCurto($linhas[$i + 1])) {
                    $descricaoDe = $i + 2;
                }
            }

            // A descrição para antes das linhas (empresa e/ou cargo) que abrem a próxima experiência.
            $fimDaDescricao = isset($ancoras[$n + 1]) && $ancoras[$n + 1]['solto'] === ''
                ? max($descricaoDe, $proxima - $this->linhasDeCabecalho($linhas, $proxima))
                : $proxima;
            $descricao = trim(implode(' ', array_slice($linhas, $descricaoDe, max(0, $fimDaDescricao - $descricaoDe))));
            $descricao = preg_replace('/\s+/u', ' ', $descricao) ?? '';

            if ($cargo === '' || mb_strlen($cargo) > 140) {
                continue;
            }
            $experiencias[] = [
                'cargo' => mb_substr($cargo, 0, 120),
                'empresa' => mb_substr($empresa, 0, 120),
                'inicio' => $ancora['inicio'],
                'fim' => $ancora['fim'],
                'atual' => $ancora['atual'],
                'descricao' => $descricao !== '' ? (mb_strlen($descricao) > 500 ? rtrim(mb_substr($descricao, 0, 497)) . '…' : $descricao) : null,
            ];
            if (count($experiencias) >= 12) {
                break;
            }
        }

        usort($experiencias, fn (array $a, array $b) => strcmp((string) ($b['atual'] ? '9999-12' : $b['fim']), (string) ($a['atual'] ? '9999-12' : $a['fim'])));
        return $experiencias;
    }

    /** Quantas linhas acima do período formam o cabeçalho: 1 se for "Cargo - Empresa", 2 se forem empresa e cargo separados. */
    private function linhasDeCabecalho(array $linhas, int $indicePeriodo): int
    {
        $acima = $linhas[$indicePeriodo - 1] ?? '';
        return $acima !== '' && $this->cargoEEmpresa($acima)[1] !== '' ? 1 : 2;
    }

    /** @return array{0: string, 1: string} [cargo, empresa] */
    private function cargoEEmpresa(string $texto): array
    {
        $partes = preg_split('/\s+(?:[-–—|@·]|at|na|no|em)\s+/iu', $texto, 2) ?: [$texto];
        if (count($partes) === 2) {
            // Se a primeira metade não parece cargo e a segunda parece, estavam trocados ("Empresa - Cargo").
            return $this->pareceCargo($partes[0]) || !$this->pareceCargo($partes[1]) ? [trim($partes[0]), trim($partes[1])] : [trim($partes[1]), trim($partes[0])];
        }
        return [trim($texto), ''];
    }

    /**
     * Acha um período de datas ("março de 2021 - presente", "Jan 2020 – Mar 2022", "03/2019 - 05/2021", "2018 - 2020").
     * @return array{inicio: ?string, fim: ?string, atual: bool, resto: string}|null resto = o que sobra na linha, sem o período
     */
    private function periodo(string $linha): ?array
    {
        $mes = '(?:(' . implode('|', array_map('preg_quote', array_keys(self::MESES))) . ')[a-zç]*\.?\s*(?:de\s+|,\s*)?)';
        $atual = '(presente|atual(?:mente)?|hoje|present|current|now|ongoing)';
        $inicioOuFim = '(?:' . $mes . '?(\d{4})|(\d{1,2})\/(\d{4}))';
        $padrao = '/' . $inicioOuFim . '\s*(?:[-–—]|a|to|até)\s*(?:' . $atual . '|' . $mes . '?(\d{4})|(\d{1,2})\/(\d{4}))/iu';

        if (!preg_match($padrao, $linha, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $valor = fn (int $i): string => $m[$i][0] ?? '';
        $mesDe = fn (string $nome): ?int => $nome === '' ? null : (self::MESES[mb_strtolower($nome)] ?? null);

        // Grupos: 1 mês-ini, 2 ano-ini, 3 mm-ini, 4 aaaa-ini | 5 atual, 6 mês-fim, 7 ano-fim, 8 mm-fim, 9 aaaa-fim
        $anoIni = $valor(2) !== '' ? $valor(2) : $valor(4);
        $mesIni = $valor(2) !== '' ? $mesDe($valor(1)) : ($valor(3) !== '' ? (int) $valor(3) : null);
        $ehAtual = $valor(5) !== '';
        $anoFim = $valor(7) !== '' ? $valor(7) : $valor(9);
        $mesFim = $valor(7) !== '' ? $mesDe($valor(6)) : ($valor(8) !== '' ? (int) $valor(8) : null);

        if ($anoIni === '' || (int) $anoIni < 1970 || (int) $anoIni > (int) date('Y') + 1) {
            return null;
        }

        $inicio = sprintf('%04d-%02d', (int) $anoIni, $mesIni ?? 1);
        $fim = $ehAtual ? null : ($anoFim !== '' ? sprintf('%04d-%02d', (int) $anoFim, $mesFim ?? 12) : null);
        $resto = trim(substr($linha, 0, $m[0][1]) . ' ' . substr($linha, $m[0][1] + strlen($m[0][0])));

        return ['inicio' => $inicio, 'fim' => $fim, 'atual' => $ehAtual, 'resto' => $resto];
    }

    /** @param array<int, array{inicio: ?string, fim: ?string, atual: bool}> $experiencias */
    private function anosDeExperiencia(array $experiencias, string $corpo): ?int
    {
        $meses = [];
        foreach ($experiencias as $experiencia) {
            if ($experiencia['inicio'] === null) {
                continue;
            }
            $inicio = $this->mesAbsoluto($experiencia['inicio']);
            $fim = $experiencia['atual'] || $experiencia['fim'] === null ? (int) date('Y') * 12 + (int) date('n') : $this->mesAbsoluto($experiencia['fim']);
            if ($fim >= $inicio) {
                $meses[] = [$inicio, $fim];
            }
        }

        if ($meses !== []) {
            // União dos períodos: dois empregos ao mesmo tempo não contam em dobro.
            usort($meses, fn (array $a, array $b) => $a[0] <=> $b[0]);
            $total = 0;
            [$ini, $fim] = $meses[0];
            foreach (array_slice($meses, 1) as [$i, $f]) {
                if ($i <= $fim) {
                    $fim = max($fim, $f);
                } else {
                    $total += $fim - $ini;
                    [$ini, $fim] = [$i, $f];
                }
            }
            $total += $fim - $ini;
            return (int) min(50, floor($total / 12));
        }

        if (preg_match('/(\d{1,2})\s*\+?\s*(?:anos|years)\s+(?:de\s+)?(?:experi[eê]ncia|experience)/iu', $corpo, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function mesAbsoluto(string $anoMes): int
    {
        [$ano, $mes] = array_map('intval', explode('-', $anoMes));
        return $ano * 12 + $mes;
    }

    // ---------------------------------------------------------------- Formação

    /** "Bacharelado em Ciência da Computação, Ciência da Computação" vira só o primeiro trecho. */
    private function semRepeticao(string $curso): string
    {
        $partes = array_map('trim', explode(',', $curso));
        if (count($partes) === 2 && $partes[1] !== '' && mb_stripos($partes[0], $partes[1]) !== false) {
            return $partes[0];
        }
        return $curso;
    }

    /**
     * @param string[] $linhas
     * @return array<int, array{curso: string, instituicao: string, ano: ?string}>
     */
    private function formacao(array $linhas): array
    {
        $instituicao = '/\b(universidade|faculdade|instituto|centro universit[aá]rio|escola|college|university|institute|school|unifor|ufc|usp|unicamp|ifce|ifsp|ufmg|ufrj|uece|uninassau|est[aá]cio|unip|anhanguera|senac|senai|fiap|alura|rocketseat|udemy|coursera|harvard|mit)\b/iu';
        $curso = '/\b(bacharelado|licenciatura|tecn[oó]log[oa]|gradua[cç][aã]o|p[oó]s[\s-]?gradua[cç][aã]o|mba|mestrado|doutorado|t[eé]cnico|curso|ci[eê]ncia|engenharia|an[aá]lise e desenvolvimento|sistemas de informa[cç][aã]o|bachelor|master|degree|computer science|information systems)\b/iu';

        $itens = [];
        $atual = ['curso' => '', 'instituicao' => '', 'ano' => null];
        $fechar = function () use (&$itens, &$atual): void {
            if ($atual['curso'] !== '' || $atual['instituicao'] !== '') {
                $itens[] = $atual;
            }
            $atual = ['curso' => '', 'instituicao' => '', 'ano' => null];
        };

        foreach ($linhas as $linha) {
            $ano = preg_match_all('/\b(19[7-9]\d|20[0-4]\d)\b/', $linha, $anos) ? max($anos[1]) : null;
            $semAno = trim(preg_replace('/\(?\b(?:19|20)\d{2}\b\)?(\s*[-–—]\s*(?:\(?(?:19|20)\d{2}\)?|presente|atual|present))?/iu', '', $linha) ?? '', " ,-–—·|");

            if ($semAno !== '' && preg_match($instituicao, $semAno) && $atual['instituicao'] !== '') {
                $fechar();
            }
            if ($semAno !== '') {
                if (preg_match($instituicao, $semAno) && $atual['instituicao'] === '') {
                    // "Curso, Instituição" na mesma linha: separa.
                    $partes = preg_split('/\s*[,\-–—|·]\s*/u', $semAno, 2) ?: [$semAno];
                    if (count($partes) === 2 && preg_match($curso, $partes[1]) && !preg_match($curso, $partes[0])) {
                        $atual['instituicao'] = $partes[0];
                        $atual['curso'] = $partes[1];
                    } elseif (count($partes) === 2 && preg_match($curso, $partes[0])) {
                        $atual['curso'] = $partes[0];
                        $atual['instituicao'] = $partes[1];
                    } else {
                        $atual['instituicao'] = $semAno;
                    }
                } elseif ($atual['curso'] === '' && (preg_match($curso, $semAno) || $atual['instituicao'] !== '')) {
                    $atual['curso'] = $semAno;
                } elseif ($atual['instituicao'] === '' && $atual['curso'] !== '') {
                    $atual['instituicao'] = $semAno;
                }
            }
            if ($ano !== null) {
                $atual['ano'] = (string) $ano;
                if ($atual['curso'] !== '' && $atual['instituicao'] !== '') {
                    $fechar();
                }
            }
        }
        $fechar();

        return array_slice(array_map(fn (array $item) => [
            'curso' => mb_substr($this->semRepeticao($item['curso']), 0, 150),
            'instituicao' => mb_substr($item['instituicao'], 0, 150),
            'ano' => $item['ano'],
        ], $itens), 0, 8);
    }
}
