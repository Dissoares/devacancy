<?php

declare(strict_types=1);

namespace App\Interfaces;

interface AssinaturaRepositoryInterface
{
    /** Data de expiração da assinatura, ou null se não houver assinatura vigente. */
    public function vigenteAte(int $usuarioId): ?string;

    /** Soma os dias ao prazo atual (ou a partir de agora, se já venceu). */
    public function estender(int $usuarioId, string $plano, int $dias): void;

    public function contarAtivas(): int;
}
