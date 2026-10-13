<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

/**
 * Classifica área, senioridade, modelo de trabalho e stack a partir de texto livre.
 * Compartilhado pelos importadores de vaga (GitHub, Adzuna...) para não duplicar as regras entre fontes.
 */
final class ClassificadorVaga
{
    private const TECNOLOGIAS = [
        'React Native', 'React', 'Angular', 'Vue', 'Next.js', 'Nuxt', 'Svelte', 'TypeScript', 'JavaScript', 'Node.js',
        'NestJS', 'Nest.js', 'PHP', 'Laravel', 'Symfony', 'Python', 'Django', 'FastAPI', 'Flask', 'Java', 'Spring', 'Kotlin',
        'C#', '.NET', 'Go', 'Golang', 'Ruby', 'Rails', 'Elixir', 'Rust', 'Scala', 'Swift', 'SwiftUI', 'Objective-C',
        'Flutter', 'Dart', 'Android', 'iOS', 'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Terraform', 'PostgreSQL',
        'MySQL', 'MongoDB', 'Redis', 'GraphQL', 'Kafka', 'SQL', 'Cypress', 'Selenium', 'Playwright', 'Jest',
        'Tailwind', 'HTML', 'CSS', 'Spark', 'Airflow', 'Power BI', 'Cobol', 'Solidity', 'Mainframe',
        'Delphi', 'SAP', 'ABAP', 'Salesforce', 'Unity', 'C++',
    ];

    private const MAPA_SENIORIDADE = [
        'estagio' => '/est[aá]gi|trainee|\bintern\b|internship/iu',
        'junior' => '/j[uú]nior|\bjr\b/iu',
        'pleno' => '/pleno|\bmid\b|\bpl\b/iu',
        'senior' => '/s[eê]nior|\bsr\b/iu',
        'especialista' => '/especialista|tech lead|staff|principal|l[ií]der/iu',
    ];

    public static function area(string $texto, AreaAtuacao $padrao): AreaAtuacao
    {
        return match (true) {
            (bool) preg_match('/full[\s-]?stack/iu', $texto) => AreaAtuacao::Fullstack,
            (bool) preg_match('/\b(mobile|android|ios|flutter|react native)\b/iu', $texto) => AreaAtuacao::Mobile,
            (bool) preg_match('/\b(qa|quality|testes?|tester)\b/iu', $texto) => AreaAtuacao::Qa,
            (bool) preg_match('/\b(devops|sre|cloud|infra|infraestrutura|plataforma)\b/iu', $texto) => AreaAtuacao::DevOps,
            (bool) preg_match('/\b(dados|data|machine learning|ml|ia|ai)\b/iu', $texto) => AreaAtuacao::Dados,
            (bool) preg_match('/front[\s-]?end/iu', $texto) => AreaAtuacao::Frontend,
            (bool) preg_match('/back[\s-]?end/iu', $texto) => AreaAtuacao::Backend,
            default => $padrao,
        };
    }

    /**
     * Área provável quando o título não diz nada, olhando as tecnologias citadas: só front-end = Front-end,
     * só servidor e banco = Back-end, os dois = Full-stack. Sem pista nenhuma, Full-stack.
     * @param string[] $stack
     */
    public static function areaPelaStack(array $stack): AreaAtuacao
    {
        $grupos = [
            'front' => ['React', 'Angular', 'Vue', 'Next.js', 'Nuxt', 'Svelte', 'HTML', 'CSS', 'Tailwind'],
            'back' => ['PHP', 'Laravel', 'Symfony', 'Python', 'Django', 'FastAPI', 'Flask', 'Java', 'Spring', 'C#', '.NET', 'Go', 'Ruby', 'Rails', 'Elixir', 'Rust', 'Scala', 'NestJS', 'Nest.js', 'Node.js', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Kafka', 'GraphQL', 'Cobol', 'Mainframe', 'Delphi', 'SAP', 'ABAP'],
            'mobile' => ['Flutter', 'Dart', 'Swift', 'SwiftUI', 'Android', 'iOS', 'React Native', 'Objective-C', 'Kotlin'],
            'infra' => ['AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Terraform'],
            'dados' => ['Spark', 'Airflow', 'Power BI'],
            'qa' => ['Cypress', 'Selenium', 'Playwright', 'Jest'],
        ];
        $conta = array_map(fn (array $nomes) => count(array_intersect($nomes, $stack)), $grupos);

        return match (true) {
            $conta['front'] > 0 && $conta['back'] > 0 => AreaAtuacao::Fullstack,
            $conta['mobile'] > 0 && $conta['front'] === 0 && $conta['back'] === 0 => AreaAtuacao::Mobile,
            $conta['front'] > 0 => AreaAtuacao::Frontend,
            $conta['back'] > 0 => AreaAtuacao::Backend,
            $conta['dados'] > 0 => AreaAtuacao::Dados,
            $conta['qa'] > 0 => AreaAtuacao::Qa,
            $conta['infra'] > 0 => AreaAtuacao::DevOps,
            default => AreaAtuacao::Fullstack,
        };
    }

    /** A senioridade dita no texto, ou null se o texto não diz nada (sem assumir Pleno). */
    public static function senioridadeOuNulo(string $texto): ?Senioridade
    {
        foreach (self::MAPA_SENIORIDADE as $nivel => $padrao) {
            if (preg_match($padrao, $texto)) {
                return Senioridade::from($nivel);
            }
        }
        return null;
    }

    /** $textoFallback só é consultado se nada bater em $texto (ex: título falha, aí tenta nos rótulos). */
    public static function senioridade(string $texto, ?string $textoFallback = null): Senioridade
    {
        foreach (self::MAPA_SENIORIDADE as $nivel => $padrao) {
            if (preg_match($padrao, $texto)) {
                return Senioridade::from($nivel);
            }
        }
        if ($textoFallback !== null) {
            foreach (self::MAPA_SENIORIDADE as $nivel => $padrao) {
                if (preg_match($padrao, $textoFallback)) {
                    return Senioridade::from($nivel);
                }
            }
        }
        return Senioridade::Pleno;
    }

    public static function modelo(string $texto): ModeloTrabalho
    {
        return match (true) {
            (bool) preg_match('/remot/iu', $texto) => ModeloTrabalho::Remoto,
            (bool) preg_match('/h[ií]brid/iu', $texto) => ModeloTrabalho::Hibrido,
            default => ModeloTrabalho::Presencial,
        };
    }

    /** @return string[] até $limite tecnologias citadas, na ordem da lista (as mais específicas primeiro) */
    public static function stack(string $texto, int $limite = 8): array
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
        return array_slice(array_values(array_unique($achadas)), 0, $limite);
    }

    public static function pareceTecnologia(string $texto): bool
    {
        foreach (self::TECNOLOGIAS as $tecnologia) {
            if (stripos($texto, $tecnologia) !== false) {
                return true;
            }
        }
        return (bool) preg_match('/\b(llm|ia|ai|ux|ui|healthtech|fintech|saas|ehr|emr)\b/iu', $texto);
    }
}
