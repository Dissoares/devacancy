<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Ícones oficiais das tecnologias (Devicon, em public/assets/img/stacks).
 * Frameworks puxam a linguagem por trás: Spring → Java, .NET → C#, Next.js → React…
 */
final class IconesStack
{
    /** Nome normalizado (minúsculo, sem espaço/ponto) => arquivo do ícone. */
    private const ICONES = [
        'javascript' => 'javascript', 'js' => 'javascript', 'typescript' => 'typescript', 'ts' => 'typescript',
        'react' => 'react', 'reactjs' => 'react', 'reactnative' => 'react', 'nextjs' => 'nextjs', 'next' => 'nextjs',
        'vue' => 'vuejs', 'vuejs' => 'vuejs', 'nuxt' => 'nuxtjs', 'nuxtjs' => 'nuxtjs', 'angular' => 'angular',
        'svelte' => 'svelte', 'nodejs' => 'nodejs', 'node' => 'nodejs', 'nestjs' => 'nestjs', 'nest' => 'nestjs',
        'php' => 'php', 'laravel' => 'laravel', 'symfony' => 'symfony', 'python' => 'python', 'django' => 'django',
        'fastapi' => 'fastapi', 'flask' => 'flask', 'java' => 'java', 'spring' => 'spring', 'springboot' => 'spring',
        'kotlin' => 'kotlin', 'c#' => 'csharp', 'csharp' => 'csharp', 'net' => 'dot-net', 'dotnet' => 'dot-net',
        'aspnet' => 'dot-net', 'go' => 'go', 'golang' => 'go', 'ruby' => 'ruby', 'rails' => 'rails',
        'rubyonrails' => 'rails', 'elixir' => 'elixir', 'rust' => 'rust', 'scala' => 'scala', 'swift' => 'swift',
        'swiftui' => 'swift', 'flutter' => 'flutter', 'dart' => 'dart', 'android' => 'android', 'ios' => 'apple',
        'aws' => 'amazonwebservices', 'azure' => 'azure', 'gcp' => 'googlecloud', 'googlecloud' => 'googlecloud',
        'docker' => 'docker', 'kubernetes' => 'kubernetes', 'k8s' => 'kubernetes', 'terraform' => 'terraform',
        'postgresql' => 'postgresql', 'postgres' => 'postgresql', 'mysql' => 'mysql', 'mongodb' => 'mongodb',
        'redis' => 'redis', 'graphql' => 'graphql', 'kafka' => 'apachekafka', 'cypress' => 'cypressio',
        'selenium' => 'selenium', 'playwright' => 'playwright', 'jest' => 'jest', 'tailwind' => 'tailwindcss',
        'tailwindcss' => 'tailwindcss', 'html' => 'html5', 'html5' => 'html5', 'css' => 'css3', 'css3' => 'css3',
        'spark' => 'apachespark', 'airflow' => 'apacheairflow', 'solidity' => 'solidity', 'c++' => 'cplusplus',
        'cpp' => 'cplusplus', 'unity' => 'unity', 'salesforce' => 'salesforce', 'git' => 'git',
    ];

    /** Framework => linguagem que vem junto (mostrada logo depois). */
    private const IMPLICITOS = [
        'spring' => 'java', 'dot-net' => 'csharp', 'nextjs' => 'react', 'nuxtjs' => 'vuejs', 'laravel' => 'php',
        'symfony' => 'php', 'django' => 'python', 'fastapi' => 'python', 'flask' => 'python', 'rails' => 'ruby',
        'nestjs' => 'nodejs', 'flutter' => 'dart', 'angular' => 'typescript',
    ];

    /** Nome bonito para o texto alternativo de cada ícone. */
    private const NOMES = [
        'javascript' => 'JavaScript', 'typescript' => 'TypeScript', 'react' => 'React', 'nextjs' => 'Next.js',
        'vuejs' => 'Vue', 'nuxtjs' => 'Nuxt', 'angular' => 'Angular', 'nodejs' => 'Node.js', 'nestjs' => 'NestJS',
        'php' => 'PHP', 'python' => 'Python', 'java' => 'Java', 'csharp' => 'C#', 'dot-net' => '.NET', 'go' => 'Go',
        'ruby' => 'Ruby', 'dart' => 'Dart', 'amazonwebservices' => 'AWS', 'googlecloud' => 'Google Cloud',
        'postgresql' => 'PostgreSQL', 'mysql' => 'MySQL', 'mongodb' => 'MongoDB', 'apachekafka' => 'Kafka',
        'cypressio' => 'Cypress', 'tailwindcss' => 'Tailwind', 'html5' => 'HTML', 'css3' => 'CSS', 'cplusplus' => 'C++',
    ];

    /** Arquivo do ícone de uma tecnologia, ou null se não houver. */
    public static function arquivo(string $tecnologia): ?string
    {
        $chave = preg_replace('/[\s.\-_]/', '', mb_strtolower(trim($tecnologia)));
        return self::ICONES[$chave] ?? null;
    }

    /**
     * Ícones de uma stack, na ordem da vaga, com a linguagem implícita logo depois do framework
     * e sem repetir. @return string[] arquivos
     */
    public static function daStack(array $stack, int $limite = 4): array
    {
        $arquivos = [];
        foreach ($stack as $tecnologia) {
            $arquivo = self::arquivo((string) $tecnologia);
            if ($arquivo === null) {
                continue;
            }
            $arquivos[$arquivo] = true;
            if (isset(self::IMPLICITOS[$arquivo])) {
                $arquivos[self::IMPLICITOS[$arquivo]] = true;
            }
        }
        return array_slice(array_keys($arquivos), 0, $limite);
    }

    /** Nome normalizado => URL do ícone, para os scripts (tags e sugestões de stack). @return array<string, string> */
    public static function mapaParaScripts(): array
    {
        return array_map(fn (string $arquivo) => asset('img/stacks/' . $arquivo . '.svg'), self::ICONES);
    }

    public static function nome(string $arquivo): string
    {
        return self::NOMES[$arquivo] ?? ucfirst($arquivo);
    }
}
