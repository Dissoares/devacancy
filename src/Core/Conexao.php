<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Conexao
{
    private static ?PDO $pdo = null;

    public static function obter(): PDO
    {
        if (self::$pdo === null) {
            $banco = configuracao('banco');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $banco['host'],
                $banco['porta'],
                $banco['nome'],
            );

            self::$pdo = new PDO($dsn, $banco['usuario'], $banco['senha'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}
