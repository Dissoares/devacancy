<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

final class Vaga
{
    /** @param string[] $stack */
    public function __construct(
        public readonly int $id,
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
        public readonly bool $ativa,
        public readonly string $criadoEm,
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: (int) $linha['id'],
            titulo: $linha['titulo'],
            empresa: $linha['empresa'],
            area: AreaAtuacao::from($linha['area']),
            senioridade: Senioridade::from($linha['senioridade']),
            stack: json_decode($linha['stack'] ?: '[]', true) ?: [],
            modeloTrabalho: ModeloTrabalho::from($linha['modelo_trabalho']),
            cidade: $linha['cidade'],
            salarioMin: $linha['salario_min'] !== null ? (int) $linha['salario_min'] : null,
            salarioMax: $linha['salario_max'] !== null ? (int) $linha['salario_max'] : null,
            link: $linha['link'],
            descricao: $linha['descricao'],
            ativa: (bool) $linha['ativa'],
            criadoEm: $linha['criado_em'],
        );
    }

    public function faixaSalarial(): ?string
    {
        $formatar = fn (int $valor): string => 'R$ ' . number_format($valor, 0, ',', '.');

        return match (true) {
            $this->salarioMin && $this->salarioMax => $formatar($this->salarioMin) . ' – ' . $formatar($this->salarioMax),
            (bool) $this->salarioMin => 'A partir de ' . $formatar($this->salarioMin),
            (bool) $this->salarioMax => 'Até ' . $formatar($this->salarioMax),
            default => null,
        };
    }

    public function local(): string
    {
        return $this->modeloTrabalho->rotulo() . ($this->cidade ? ' · ' . $this->cidade : '');
    }
}
