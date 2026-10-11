<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Core\ListaDeTags;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\NivelIngles;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

final class PerfilProfissionalDTO
{
    /** @param string[] $stack @param string[] $idiomas */
    public function __construct(
        public readonly AreaAtuacao $area,
        public readonly Senioridade $senioridade,
        public readonly string $cargoDesejado,
        public readonly int $anosExperiencia,
        public readonly array $stack,
        public readonly ModeloTrabalho $modeloTrabalho,
        public readonly ?string $cidade,
        public readonly ?int $pretensaoSalarial,
        public readonly ?string $telefone,
        public readonly ?string $linkedinUrl,
        public readonly ?string $githubUrl,
        public readonly ?string $portfolioUrl,
        public readonly ?string $resumo,
        public readonly RegiaoVagas $regiaoVagas = RegiaoVagas::Brasil,
        public readonly string $moedaPretensao = 'BRL',
        public readonly array $idiomas = [],
        public readonly ?int $ultimaRemuneracao = null,
        public readonly ?NivelIngles $nivelIngles = null,
    ) {
    }

    public static function regras(): array
    {
        return [
            'area' => 'obrigatorio|enum:' . AreaAtuacao::class,
            'senioridade' => 'obrigatorio|enum:' . Senioridade::class,
            'cargo_desejado' => 'obrigatorio|min:2|max:120',
            'anos_experiencia' => 'obrigatorio|entre:0,50',
            'stack' => 'obrigatorio|max:600',
            'modelo_trabalho' => 'obrigatorio|enum:' . ModeloTrabalho::class,
            'cidade' => 'max:120',
            'pretensao_salarial' => 'entre:0,2000000',
            'telefone' => 'min:10|max:30',
            'regiao_vagas' => 'enum:' . RegiaoVagas::class,
            'moeda_pretensao' => 'enum:' . \App\Enums\Moeda::class,
            'linkedin_url' => 'url|max:255',
            'github_url' => 'url|max:255',
            'portfolio_url' => 'url|max:255',
            'resumo' => 'max:1500',
            'idiomas' => 'enum:' . \App\Enums\IdiomaFalado::class,
            'ultima_remuneracao' => 'entre:0,2000000',
            'nivel_ingles' => 'enum:' . NivelIngles::class,
        ];
    }

    public static function deArray(array $dados): self
    {
        return new self(
            area: AreaAtuacao::from($dados['area']),
            senioridade: Senioridade::from($dados['senioridade']),
            cargoDesejado: trim($dados['cargo_desejado']),
            anosExperiencia: (int) $dados['anos_experiencia'],
            stack: ListaDeTags::normalizar($dados['stack'] ?? ''),
            modeloTrabalho: ModeloTrabalho::from($dados['modelo_trabalho']),
            cidade: self::textoOuNulo($dados['cidade'] ?? null),
            pretensaoSalarial: ($dados['pretensao_salarial'] ?? '') !== '' ? (int) $dados['pretensao_salarial'] : null,
            telefone: self::textoOuNulo(preg_replace('/[^\d+]/', '', (string) ($dados['telefone'] ?? ''))),
            linkedinUrl: self::textoOuNulo($dados['linkedin_url'] ?? null),
            githubUrl: self::textoOuNulo($dados['github_url'] ?? null),
            portfolioUrl: self::textoOuNulo($dados['portfolio_url'] ?? null),
            resumo: self::textoOuNulo($dados['resumo'] ?? null),
            regiaoVagas: RegiaoVagas::tryFrom((string) ($dados['regiao_vagas'] ?? '')) ?? RegiaoVagas::Brasil,
            moedaPretensao: \App\Enums\Moeda::tryFrom((string) ($dados['moeda_pretensao'] ?? ''))?->value ?? 'BRL',
            idiomas: array_values(array_unique((array) ($dados['idiomas'] ?? []))),
            ultimaRemuneracao: ($dados['ultima_remuneracao'] ?? '') !== '' ? (int) $dados['ultima_remuneracao'] : null,
            nivelIngles: NivelIngles::tryFrom((string) ($dados['nivel_ingles'] ?? '')),
        );
    }

    private static function textoOuNulo(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
