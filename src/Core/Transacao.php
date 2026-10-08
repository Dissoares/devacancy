<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/** Permite que os services agrupem operações de vários repositórios em uma transação. */
final class Transacao
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @template T
     * @param callable(): T $operacao
     * @return T
     */
    public function executar(callable $operacao): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $operacao();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }
    }
}
