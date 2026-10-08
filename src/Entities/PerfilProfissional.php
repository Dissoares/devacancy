<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;

final class PerfilProfissional
{
    /** @param string[] $stack */
    public function __construct(
        public readonly int $usuarioId,
        public readonly AreaAtuacao $area,
        public readonly Senioridade $senioridade,
        public readonly string $cargoDesejado,
        public readonly int $anosExperiencia,
        public readonly array $stack,
        public readonly ModeloTrabalho $modeloTrabalho,
        public readonly ?string $cidade,
        public readonly ?int $pretensaoSalarial,
        public readonly ?string $linkedinUrl,
        public readonly ?string $githubUrl,
        public readonly ?string $portfolioUrl,
        public readonly ?string $curriculoArquivo,
        public readonly ?string $curriculoNomeOriginal,
        public readonly ?string $resumo,
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            usuarioId: (int) $linha['usuario_id'],
            area: AreaAtuacao::from($linha['area']),
            senioridade: Senioridade::from($linha['senioridade']),
            cargoDesejado: $linha['cargo_desejado'],
            anosExperiencia: (int) $linha['anos_experiencia'],
            stack: json_decode($linha['stack'] ?: '[]', true) ?: [],
            modeloTrabalho: ModeloTrabalho::from($linha['modelo_trabalho']),
            cidade: $linha['cidade'],
            pretensaoSalarial: $linha['pretensao_salarial'] !== null ? (int) $linha['pretensao_salarial'] : null,
            linkedinUrl: $linha['linkedin_url'],
            githubUrl: $linha['github_url'],
            portfolioUrl: $linha['portfolio_url'],
            curriculoArquivo: $linha['curriculo_arquivo'],
            curriculoNomeOriginal: $linha['curriculo_nome_original'],
            resumo: $linha['resumo'],
        );
    }

    public function possuiCurriculo(): bool
    {
        return $this->curriculoArquivo !== null;
    }
}
