<?php

declare(strict_types=1);

namespace App\Interfaces;

interface MetodoPagamentoRepositoryInterface
{
    /** @return array<string, bool> chave => ativo */
    public function ativos(): array;

    /** Liga ou desliga a forma de pagamento no checkout. */
    public function definir(string $chave, bool $ativo): void;
}
