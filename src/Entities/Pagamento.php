<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\StatusPagamento;

final class Pagamento
{
    public function __construct(
        public readonly int $id,
        public readonly int $usuarioId,
        public readonly string $plano,
        public readonly float $valor,
        public readonly string $metodo,
        public readonly StatusPagamento $status,
        public readonly ?string $gatewayId,
        public readonly ?string $pixCopiaCola,
        public readonly ?string $pixQrBase64,
        public readonly ?string $expiraEm,
        public readonly ?string $pagoEm,
        public readonly string $criadoEm,
        public readonly string $moeda = 'BRL',
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: (int) $linha['id'],
            usuarioId: (int) $linha['usuario_id'],
            plano: $linha['plano'],
            valor: (float) $linha['valor'],
            metodo: $linha['metodo'],
            status: StatusPagamento::from($linha['status']),
            gatewayId: $linha['gateway_id'],
            pixCopiaCola: $linha['pix_copia_cola'],
            pixQrBase64: $linha['pix_qr_base64'],
            expiraEm: $linha['expira_em'],
            pagoEm: $linha['pago_em'],
            criadoEm: $linha['criado_em'],
            moeda: $linha['moeda'] ?? 'BRL',
        );
    }

    public function estaPendente(): bool
    {
        return $this->status === StatusPagamento::Pendente;
    }
}
