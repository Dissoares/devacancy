<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ConfiguracaoRepositoryInterface;
use PDO;

final class ConfiguracaoRepository implements ConfiguracaoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function todas(): array
    {
        return $this->pdo->query('SELECT chave, valor FROM configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    public function salvar(string $chave, string $valor): void
    {
        // VALUES() em vez de alias: o MariaDB da hospedagem não aceita "AS novo".
        $this->pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
            ->execute([$chave, $valor]);
    }

    public function remover(string $chave): void
    {
        $this->pdo->prepare('DELETE FROM configuracoes WHERE chave = ?')->execute([$chave]);
    }
}
