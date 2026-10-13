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

    /** Quantas candidaturas a pessoa pode pedir por dia. */
    public function limiteDiario(): int
    {
        return max(1, (int) configuracao('candidaturas.por_dia', 2));
    }

    /** Quantas candidaturas ainda cabem hoje. */
    public function restantesHoje(Usuario $usuario): int
    {
        return max(0, $this->limiteDiario() - $this->candidaturas->contarDoDia($usuario->id));
    }

    /**
     * Só aceita vagas compatíveis com o perfil (ignora ids de fora) e que ainda não foram pedidas. Respeita o limite
     * diário: o que passar dele fica de fora, na ordem recebida (a tela manda as melhores primeiro).
     * @param int[] $vagaIds
     * @return array{criadas: int, limitou: bool}
     */
    public function candidatar(Usuario $usuario, PerfilProfissional $perfil, array $vagaIds): array
    {
        $compativeis = array_map(fn (array $item) => $item['vaga']->id, $this->vagas->compativeis($perfil));
        $jaPedidas = array_keys($this->candidaturas->statusPorVaga($usuario->id));
        $novas = array_values(array_diff(array_intersect(array_map('intval', $vagaIds), $compativeis), $jaPedidas));
        if ($novas === []) {
            return ['criadas' => 0, 'limitou' => false];
        }

        $permitidas = array_slice($novas, 0, $this->restantesHoje($usuario));
        return [
            'criadas' => $permitidas === [] ? 0 : $this->candidaturas->criar($usuario->id, $permitidas),
            'limitou' => count($permitidas) < count($novas),
        ];
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
