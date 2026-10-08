<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\TamanhoEmpresa;

final class PerfilEmpresa
{
    /** @param string[] $stacksContratacao */
    public function __construct(
        public readonly int $usuarioId,
        public readonly string $nomeEmpresa,
        public readonly ?string $site,
        public readonly TamanhoEmpresa $tamanho,
        public readonly string $cargoContato,
        public readonly array $stacksContratacao,
        public readonly int $vagasAbertas,
        public readonly ?string $mensagem,
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            usuarioId: (int) $linha['usuario_id'],
            nomeEmpresa: $linha['nome_empresa'],
            site: $linha['site'],
            tamanho: TamanhoEmpresa::from($linha['tamanho']),
            cargoContato: $linha['cargo_contato'],
            stacksContratacao: json_decode($linha['stacks_contratacao'] ?: '[]', true) ?: [],
            vagasAbertas: (int) $linha['vagas_abertas'],
            mensagem: $linha['mensagem'],
        );
    }
}
