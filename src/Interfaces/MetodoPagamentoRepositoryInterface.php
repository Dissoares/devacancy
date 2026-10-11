<?php

declare(strict_types=1);

namespace App\Interfaces;

interface MetodoPagamentoRepositoryInterface
{
    /** @return array<string, bool> chave => ativo */
    public function ativos(): array;

    public function alternar(string $chave): void;
}
