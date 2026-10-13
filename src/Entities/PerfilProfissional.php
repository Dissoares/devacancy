<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\NivelIngles;
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
        public readonly ?NivelIngles $nivelIngles = null,
        /** @var array<int, array{cargo: string, empresa: string, inicio: ?string, fim: ?string, atual: bool, descricao: ?string}> */
        public readonly array $experiencias = [],
        /** @var array<int, array{curso: string, instituicao: string, ano: ?string}> */
        public readonly array $formacao = [],
        public readonly ?string $importadoEm = null,
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
            nivelIngles: isset($linha['nivel_ingles']) ? NivelIngles::tryFrom($linha['nivel_ingles']) : null,
            experiencias: json_decode($linha['experiencias'] ?? '[]', true) ?: [],
            formacao: json_decode($linha['formacao'] ?? '[]', true) ?: [],
            importadoEm: $linha['importado_em'] ?? null,
        );
    }

    /** O perfil no formato dos campos do formulário de edição. @return array<string, mixed> */
    public function comoFormulario(): array
    {
        return [
            'area' => $this->area->value,
            'senioridade' => $this->senioridade->value,
            'cargo_desejado' => $this->cargoDesejado,
            'anos_experiencia' => $this->anosExperiencia,
            'stack' => implode(', ', $this->stack),
            'modelo_trabalho' => $this->modeloTrabalho->value,
            'cidade' => $this->cidade,
            'pretensao_salarial' => $this->pretensaoSalarial,
            'linkedin_url' => $this->linkedinUrl,
            'github_url' => $this->githubUrl,
            'portfolio_url' => $this->portfolioUrl,
            'resumo' => $this->resumo,
            'regiao_vagas' => $this->regiaoVagas->value,
            'moeda_pretensao' => $this->moedaPretensao,
            'idiomas' => $this->idiomas,
            'nivel_ingles' => $this->nivelIngles?->value,
        ];
    }

    public function possuiCurriculo(): bool
    {
        return $this->curriculoArquivo !== null;
    }

    /**
     * Quanto o perfil está pronto para candidaturas, e o que falta para chegar a 100%.
     * "importavel": o importador de currículo preenche esse item sozinho.
     * @return array{percentual: int, pendencias: array<int, array{rotulo: string, importavel: bool}>}
     */
    public function forca(): array
    {
        $itens = [
            'Currículo em PDF' => [20, $this->possuiCurriculo(), true],
            'Stack com 3 ou mais tecnologias' => [15, count($this->stack) >= 3, true],
            'Experiências profissionais' => [15, $this->experiencias !== [], true],
            'Resumo profissional' => [10, $this->resumo !== null, true],
            'LinkedIn' => [10, $this->linkedinUrl !== null, true],
            'GitHub ou portfólio' => [10, $this->githubUrl !== null || $this->portfolioUrl !== null, true],
            'Pretensão salarial' => [10, $this->pretensaoSalarial !== null, false],
            'Formação' => [5, $this->formacao !== [], true],
            'Idiomas' => [5, $this->idiomas !== [], true],
        ];

        $percentual = 0;
        $pendencias = [];
        foreach ($itens as $rotulo => [$peso, $feito, $importavel]) {
            if ($feito) {
                $percentual += $peso;
            } else {
                $pendencias[] = ['rotulo' => $rotulo, 'importavel' => $importavel];
            }
        }
        return ['percentual' => $percentual, 'pendencias' => $pendencias];
    }
}
