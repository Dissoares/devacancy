<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\VagaDTO;
use App\Entities\Vaga;

interface VagaRepositoryInterface
{
    /** $criadoEm nulo usa agora (vagas importadas guardam a data original do anúncio). */
    public function criar(VagaDTO $dados, ?string $criadoEm = null): int;

    public function existeLink(string $link): bool;

    /** Mesma vaga (título e empresa) já cadastrada nos últimos N dias. */
    public function existeSemelhante(string $titulo, string $empresa, int $dias): bool;

    public function alternarAtiva(int $id): void;

    /** @return array<string, array{total: int, ativas: int}> contagem por fonte (manual, github, adzuna...) */
    public function contarPorFonte(): array;

    /** @return Vaga[] mais recentes primeiro; $ativa filtra por status (null = todas) */
    public function listar(?bool $ativa = null, int $limite = 200): array;

    public function excluir(int $id): void;

    /** @return Vaga[] todas as vagas ativas cadastradas nos últimos N dias */
    public function listarAtivas(int $diasRecentes, int $limite = 2000): array;

    /**
     * Vagas ativas que passam no filtro grosso do match (o refinamento por stack fica no service).
     * @param string[] $areas @param string[] $senioridades @param string[] $modelos @param string[] $regioes
     * @return Vaga[]
     */
    public function buscarCandidatas(array $areas, array $senioridades, array $modelos, array $regioes, int $diasRecentes): array;
}
