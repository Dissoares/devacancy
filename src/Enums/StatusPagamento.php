<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPagamento: string
{
    use ComOpcoes;

    case Pendente = 'pendente';
    case Pago = 'pago';
    case Falhou = 'falhou';
    case Expirado = 'expirado';
    case Cancelado = 'cancelado';
    case Estornado = 'estornado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando pagamento',
            self::Pago => 'Pago',
            self::Falhou => 'Recusado',
            self::Expirado => 'Expirado',
            self::Cancelado => 'Cancelado',
            self::Estornado => 'Estornado',
        };
    }

    /** Traduz o status da cobrança na ExPay. */
    public static function doGateway(string $status): self
    {
        return match ($status) {
            'paid' => self::Pago,
            'failed' => self::Falhou,
            'expired' => self::Expirado,
            'canceled' => self::Cancelado,
            'refunded' => self::Estornado,
            default => self::Pendente,
        };
    }
}
