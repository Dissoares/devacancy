<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\VagaDTO;
use App\Entities\Vaga;

interface VagaRepositoryInterface
{
    public function criar(VagaDTO $dados): int;

    public function alternarAtiva(int $id): void;

    /** @return Vaga[] mais recentes primeiro */
    public function listar(int $limite = 200): array;

    /**
     * Vagas ativas que passam no filtro grosso do match (o refinamento por stack fica no service).
     * @param string[] $areas @param string[] $senioridades @param string[] $modelos
     * @return Vaga[]
     */
    public function buscarCandidatas(array $areas, array $senioridades, array $modelos, int $diasRecentes): array;
}
