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

    public function definir(string $chave, bool $ativo): void
    {
        // VALUES() em vez de alias: o MariaDB da hospedagem não aceita "AS novo".
        $this->pdo->prepare('INSERT INTO metodos_pagamento (chave, ativo) VALUES (?, ?) ON DUPLICATE KEY UPDATE ativo = VALUES(ativo)')
            ->execute([$chave, $ativo ? 1 : 0]);
    }
}
