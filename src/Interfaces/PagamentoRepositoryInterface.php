<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Entities\Pagamento;
use App\Enums\StatusPagamento;

interface PagamentoRepositoryInterface
{
    public function criar(int $usuarioId, string $plano, float $valor, string $metodo, string $moeda = 'BRL'): int;

    /** Registra uma cobrança já paga de uma assinatura externa (PayPal). False se o gatewayId já existia. */
    public function registrarPagoExterno(int $usuarioId, string $plano, float $valor, string $moeda, string $metodo, string $gatewayId, string $assinaturaExterna): bool;

    public function contarPagosDaAssinaturaExterna(string $assinaturaExterna): int;

    public function buscarPorId(int $id): ?Pagamento;

    public function buscarPorGatewayId(string $gatewayId): ?Pagamento;

    public function registrarCobranca(int $id, string $gatewayId, ?string $copiaCola, ?string $qrBase64, ?string $expiraEm): void;

    /** Últimos pagamentos de qualquer forma, com o usuário. @return array<int, array> */
    public function recentes(int $limite): array;

    /** Pagamentos ainda pendentes de um método, com o usuário, para a confirmação manual do admin. @return array<int, array> */
    public function listarPendentesDoMetodo(string $metodo): array;

    /** Sai de "pendente" para o novo status. Retorna false se outro processo já mudou o status. */
    public function concluir(int $id, StatusPagamento $status): bool;
}
