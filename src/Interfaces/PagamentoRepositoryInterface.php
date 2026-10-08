<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Entities\Pagamento;
use App\Enums\StatusPagamento;

interface PagamentoRepositoryInterface
{
    public function criar(int $usuarioId, string $plano, float $valor, string $metodo): int;

    public function buscarPorId(int $id): ?Pagamento;

    public function buscarPorGatewayId(string $gatewayId): ?Pagamento;

    public function registrarCobranca(int $id, string $gatewayId, ?string $copiaCola, ?string $qrBase64, ?string $expiraEm): void;

    /** Sai de "pendente" para o novo status. Retorna false se outro processo já mudou o status. */
    public function concluir(int $id, StatusPagamento $status): bool;
}
