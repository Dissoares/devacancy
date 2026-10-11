<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\StatusCandidatura;
use App\Interfaces\CandidaturaRepositoryInterface;

/**
 * O profissional clica em "Candidatar-me" e a vaga entra na fila. Pedidos de quem ainda não assinou
 * ficam reservados e só aparecem para a equipe depois que a assinatura é ativada.
 */
final class CandidaturaService
{
    public function __construct(
        private readonly CandidaturaRepositoryInterface $candidaturas,
        private readonly VagaService $vagas,
    ) {
    }

    /**
     * Só aceita vagas compatíveis com o perfil (ignora ids de fora). Retorna quantas entraram na fila.
     * @param int[] $vagaIds
     */
    public function candidatar(Usuario $usuario, PerfilProfissional $perfil, array $vagaIds): int
    {
        $compativeis = array_map(fn (array $item) => $item['vaga']->id, $this->vagas->compativeis($perfil));
        $validas = array_values(array_intersect(array_map('intval', $vagaIds), $compativeis));

        return $validas === [] ? 0 : $this->candidaturas->criar($usuario->id, $validas);
    }

    /** @return array<int, StatusCandidatura> */
    public function statusPorVaga(Usuario $usuario): array
    {
        return $this->candidaturas->statusPorVaga($usuario->id);
    }

    public function listarParaEquipe(?StatusCandidatura $status, bool $somenteAssinantes): array
    {
        return $this->candidaturas->listarParaEquipe($status, $somenteAssinantes);
    }

    public function atualizarStatus(int $id, StatusCandidatura $status): void
    {
        $this->candidaturas->atualizarStatus($id, $status);
    }

    public function contarPorStatus(): array
    {
        return $this->candidaturas->contarPorStatus();
    }
}
