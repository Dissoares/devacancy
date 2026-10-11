<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ComissaoRepositoryInterface
{
    /** Cria a comissão só se o indicado ainda não tiver uma (chave única trava a segunda vez). */
    public function criarSeNova(int $indicadorId, int $indicadoId, int $pagamentoId, float $valor, string $moeda): bool;

    /** @return array<int, array> comissões do indicador, com nome/e-mail de quem foi indicado */
    public function listarDoIndicador(int $indicadorId): array;

    /** @return array<string, array{pendente: float, paga: float}> somas por moeda (ex: ['BRL' => ['pendente' => 40.0, 'paga' => 19.96]]) */
    public function totaisDoIndicador(int $indicadorId): array;

    /** @return array<int, array> todas as comissões, com nome/e-mail de indicador e indicado, mais recentes primeiro */
    public function listarTodas(): array;

    public function marcarComoPaga(int $id): bool;
}
