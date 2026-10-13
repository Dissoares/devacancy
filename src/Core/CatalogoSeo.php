<?php

declare(strict_types=1);

namespace App\Core;

use App\Entities\Vaga;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

/**
 * As páginas de listagem públicas (/vagas/react, /vagas/backend, /vagas/junior, /vagas/remoto...), que respondem
 * a buscas como "vagas de react" ou "vagas remotas para desenvolvedor". Cada endereço é uma categoria de vagas.
 */
final class CatalogoSeo
{
    /** Endereço => tecnologia como aparece nas vagas. */
    public const STACKS = [
        'react' => 'React', 'python' => 'Python', 'java' => 'Java', 'php' => 'PHP', 'node-js' => 'Node.js',
        'javascript' => 'JavaScript', 'typescript' => 'TypeScript', 'dotnet' => '.NET', 'csharp' => 'C#', 'golang' => 'Go',
        'angular' => 'Angular', 'vue' => 'Vue', 'aws' => 'AWS', 'flutter' => 'Flutter', 'kubernetes' => 'Kubernetes',
        'docker' => 'Docker', 'laravel' => 'Laravel', 'ruby' => 'Ruby', 'kotlin' => 'Kotlin', 'sql' => 'SQL',
    ];

    /** Menos vagas que isso e a página fica fora do Google (conteúdo fraco) e do sitemap. */
    public const MINIMO_PARA_INDEXAR = 3;

    /** @return array<string, array{slug: string, tipo: string, rotulo: string}> todas as categorias */
    public static function todas(): array
    {
        $categorias = [];
        foreach (AreaAtuacao::cases() as $area) {
            $categorias[$area->value] = ['slug' => $area->value, 'tipo' => 'area', 'rotulo' => $area->rotulo()];
        }
        foreach (Senioridade::cases() as $nivel) {
            $categorias[$nivel->value] = ['slug' => $nivel->value, 'tipo' => 'nivel', 'rotulo' => trim(explode('/', $nivel->rotulo())[0])];
        }
        $categorias['remoto'] = ['slug' => 'remoto', 'tipo' => 'remoto', 'rotulo' => ModeloTrabalho::Remoto->rotulo()];
        foreach (self::STACKS as $slug => $nome) {
            $categorias[$slug] = ['slug' => $slug, 'tipo' => 'stack', 'rotulo' => $nome];
        }
        return $categorias;
    }

    /** @return array{slug: string, tipo: string, rotulo: string}|null */
    public static function buscar(string $slug): ?array
    {
        return self::todas()[$slug] ?? null;
    }

    /**
     * @param Vaga[] $vagas
     * @param array{slug: string, tipo: string, rotulo: string}|null $categoria null = todas as vagas
     * @return Vaga[]
     */
    public static function filtrar(array $vagas, ?array $categoria): array
    {
        if ($categoria === null) {
            return $vagas;
        }

        return array_values(array_filter($vagas, fn (Vaga $vaga): bool => match ($categoria['tipo']) {
            'area' => $vaga->area->value === $categoria['slug'],
            'nivel' => $vaga->senioridade->value === $categoria['slug'],
            'remoto' => $vaga->modeloTrabalho === ModeloTrabalho::Remoto,
            'stack' => in_array(mb_strtolower($categoria['rotulo']), array_map('mb_strtolower', $vaga->stack), true),
            default => false,
        }));
    }
}
