<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Core\ListaDeTags;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

final class VagaDTO
{
    /** @param string[] $stack */
    public function __construct(
        public readonly string $titulo,
        public readonly string $empresa,
        public readonly AreaAtuacao $area,
        public readonly Senioridade $senioridade,
        public readonly array $stack,
        public readonly ModeloTrabalho $modeloTrabalho,
        public readonly ?string $cidade,
        public readonly ?int $salarioMin,
        public readonly ?int $salarioMax,
        public readonly ?string $link,
        public readonly ?string $descricao,
    ) {
    }

    public static function regras(): array
    {
        return [
            'titulo' => 'obrigatorio|min:2|max:150',
            'empresa' => 'obrigatorio|min:2|max:150',
            'area' => 'obrigatorio|enum:' . AreaAtuacao::class,
            'senioridade' => 'obrigatorio|enum:' . Senioridade::class,
            'stack' => 'obrigatorio|max:600',
            'modelo_trabalho' => 'obrigatorio|enum:' . ModeloTrabalho::class,
            'cidade' => 'max:120',
            'salario_min' => 'entre:0,200000',
            'salario_max' => 'entre:0,200000',
            'link' => 'url|max:500',
            'descricao' => 'max:5000',
        ];
    }

    public static function deArray(array $dados): self
    {
        return new self(
            titulo: trim($dados['titulo']),
            empresa: trim($dados['empresa']),
            area: AreaAtuacao::from($dados['area']),
            senioridade: Senioridade::from($dados['senioridade']),
            stack: ListaDeTags::normalizar($dados['stack'] ?? ''),
            modeloTrabalho: ModeloTrabalho::from($dados['modelo_trabalho']),
            cidade: self::textoOuNulo($dados['cidade'] ?? null),
            salarioMin: self::inteiroOuNulo($dados['salario_min'] ?? null),
            salarioMax: self::inteiroOuNulo($dados['salario_max'] ?? null),
            link: self::textoOuNulo($dados['link'] ?? null),
            descricao: self::textoOuNulo($dados['descricao'] ?? null),
        );
    }

    private static function textoOuNulo(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private static function inteiroOuNulo(?string $valor): ?int
    {
        return ($valor === null || trim($valor) === '') ? null : (int) $valor;
    }
}
