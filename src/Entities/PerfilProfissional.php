<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

final class PerfilProfissional
{
    /** @param string[] $stack @param string[] $idiomas */
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
        public readonly RegiaoVagas $regiaoVagas = RegiaoVagas::Brasil,
        public readonly string $moedaPretensao = 'BRL',
        public readonly array $idiomas = [],
        public readonly ?int $ultimaRemuneracao = null,
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
            regiaoVagas: RegiaoVagas::tryFrom($linha['regiao_vagas'] ?? '') ?? RegiaoVagas::Brasil,
            moedaPretensao: $linha['moeda_pretensao'] ?? 'BRL',
            idiomas: json_decode($linha['idiomas'] ?? '[]', true) ?: [],
            ultimaRemuneracao: isset($linha['ultima_remuneracao']) ? (int) $linha['ultima_remuneracao'] : null,
        );
    }

    public function possuiCurriculo(): bool
    {
        return $this->curriculoArquivo !== null;
    }

    /**
     * Quanto o perfil está pronto para candidaturas, e o que falta para chegar a 100%.
     * @return array{percentual: int, pendencias: string[]}
     */
    public function forca(): array
    {
        $itens = [
            'Currículo em PDF' => [30, $this->possuiCurriculo()],
            'Stack com 3 ou mais tecnologias' => [20, count($this->stack) >= 3],
            'LinkedIn' => [15, $this->linkedinUrl !== null],
            'GitHub ou portfólio' => [15, $this->githubUrl !== null || $this->portfolioUrl !== null],
            'Resumo profissional' => [10, $this->resumo !== null],
            'Pretensão salarial' => [10, $this->pretensaoSalarial !== null],
        ];

        $percentual = 0;
        $pendencias = [];
        foreach ($itens as $rotulo => [$peso, $feito]) {
            if ($feito) {
                $percentual += $peso;
            } else {
                $pendencias[] = $rotulo;
            }
        }

        return ['percentual' => $percentual, 'pendencias' => $pendencias];
    }
}
