<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Core\ListaDeTags;
use App\Enums\TamanhoEmpresa;

final class PerfilEmpresaDTO
{
    /** @param string[] $stacksContratacao */
    public function __construct(
        public readonly string $nomeEmpresa,
        public readonly ?string $site,
        public readonly TamanhoEmpresa $tamanho,
        public readonly string $cargoContato,
        public readonly array $stacksContratacao,
        public readonly int $vagasAbertas,
        public readonly string $telefone,
        public readonly ?string $mensagem,
    ) {
    }

    public static function regras(): array
    {
        return [
            'nome_empresa' => 'obrigatorio|min:2|max:150',
            'site' => 'url|max:255',
            'tamanho' => 'obrigatorio|enum:' . TamanhoEmpresa::class,
            'cargo_contato' => 'obrigatorio|min:2|max:120',
            'stacks_contratacao' => 'obrigatorio|max:600',
            'vagas_abertas' => 'obrigatorio|entre:0,999',
            'telefone' => 'obrigatorio|min:10|max:30',
            'mensagem' => 'max:1500',
        ];
    }

    public static function deArray(array $dados): self
    {
        $mensagem = trim((string) ($dados['mensagem'] ?? ''));
        $site = trim((string) ($dados['site'] ?? ''));

        return new self(
            nomeEmpresa: trim($dados['nome_empresa']),
            site: $site === '' ? null : $site,
            tamanho: TamanhoEmpresa::from($dados['tamanho']),
            cargoContato: trim($dados['cargo_contato']),
            stacksContratacao: ListaDeTags::normalizar($dados['stacks_contratacao'] ?? ''),
            vagasAbertas: (int) $dados['vagas_abertas'],
            telefone: preg_replace('/[^\d+]/', '', (string) $dados['telefone']),
            mensagem: $mensagem === '' ? null : $mensagem,
        );
    }
}
