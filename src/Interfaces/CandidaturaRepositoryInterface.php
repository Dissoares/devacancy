<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Enums\StatusCandidatura;

interface CandidaturaRepositoryInterface
{
    /** Cria as candidaturas que ainda não existem. Retorna quantas foram criadas. */
    public function criar(int $usuarioId, array $vagaIds): int;

    /** @return array<int, StatusCandidatura> vaga_id => status */
    public function statusPorVaga(int $usuarioId): array;

    /**
     * Candidaturas para a equipe enviar, com dados do profissional e da vaga.
     * @param bool $somenteAssinantes ignora pedidos de quem ainda não assinou
     */
    public function listarParaEquipe(?StatusCandidatura $status, bool $somenteAssinantes, int $limite = 300): array;

    public function atualizarStatus(int $id, StatusCandidatura $status): void;

    /** @return array<string, int> status => total, só de assinantes */
    public function contarPorStatus(): array;
}
