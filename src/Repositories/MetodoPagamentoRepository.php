<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\MetodoPagamentoRepositoryInterface;
use PDO;

final class MetodoPagamentoRepository implements MetodoPagamentoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ativos(): array
    {
        $linhas = $this->pdo->query('SELECT chave, ativo FROM metodos_pagamento')->fetchAll();

        $ativos = [];
        foreach ($linhas as $linha) {
            $ativos[$linha['chave']] = (bool) $linha['ativo'];
        }

        return $ativos;
    }

    public function alternar(string $chave): void
    {
        $this->pdo->prepare('UPDATE metodos_pagamento SET ativo = 1 - ativo WHERE chave = ?')->execute([$chave]);
    }
}
