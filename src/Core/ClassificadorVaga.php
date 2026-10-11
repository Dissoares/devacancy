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
