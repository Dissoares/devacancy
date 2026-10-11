<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\StatusComissao;

final class Comissao
{
    public function __construct(
        public readonly int $id,
        public readonly int $usuarioIndicadorId,
        public readonly int $usuarioIndicadoId,
        public readonly int $pagamentoId,
        public readonly float $valor,
        public readonly string $moeda,
        public readonly StatusComissao $status,
        public readonly string $criadoEm,
        public readonly ?string $pagoEm,
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: (int) $linha['id'],
            usuarioIndicadorId: (int) $linha['usuario_indicador_id'],
            usuarioIndicadoId: (int) $linha['usuario_indicado_id'],
            pagamentoId: (int) $linha['pagamento_id'],
            valor: (float) $linha['valor'],
            moeda: $linha['moeda'],
            status: StatusComissao::from($linha['status']),
            criadoEm: $linha['criado_em'],
            pagoEm: $linha['pago_em'],
        );
    }
}
