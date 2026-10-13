<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Freio contra tentativas em massa (senha, cadastro, recuperação). Guarda no banco, por chave (IP ou e-mail), então
 * vale mesmo para quem apaga o cookie de sessão a cada tentativa. Se o banco ou a tabela não estiverem disponíveis,
 * o freio fica desligado: um defeito aqui nunca pode derrubar o login.
 */
final class LimiteDeTentativas
{
    public static function excedeu(string $chave, int $maximo): bool
    {
        try {
            $consulta = Conexao::obter()->prepare('SELECT tentativas FROM limites_tentativas WHERE chave = ? AND expira_em > NOW()');
            $consulta->execute([self::normalizar($chave)]);
            return (int) $consulta->fetchColumn() >= $maximo;
        } catch (Throwable) {
            return false;
        }
    }

    /** Conta uma tentativa. A janela de tempo começa na primeira e só recomeça depois de expirar. */
    public static function registrar(string $chave, int $janelaSegundos): void
    {
        try {
            $pdo = Conexao::obter();
            // VALUES()/expressões sem alias: o MariaDB da hospedagem não aceita "AS novo".
            $pdo->prepare(
                'INSERT INTO limites_tentativas (chave, tentativas, expira_em) VALUES (?, 1, NOW() + INTERVAL ? SECOND)
                 ON DUPLICATE KEY UPDATE
                    tentativas = IF(expira_em < NOW(), 1, tentativas + 1),
                    expira_em = IF(expira_em < NOW(), NOW() + INTERVAL ? SECOND, expira_em)'
            )->execute([self::normalizar($chave), $janelaSegundos, $janelaSegundos]);

            if (random_int(1, 100) === 1) {
                $pdo->exec('DELETE FROM limites_tentativas WHERE expira_em < NOW() - INTERVAL 1 DAY');
            }
        } catch (Throwable) {
            // sem freio, mas sem derrubar a página
        }
    }

    public static function limpar(string $chave): void
    {
        try {
            Conexao::obter()->prepare('DELETE FROM limites_tentativas WHERE chave = ?')->execute([self::normalizar($chave)]);
        } catch (Throwable) {
        }
    }

    /** Chaves com e-mail viram hash: ficam curtas e o e-mail não é guardado nesta tabela. */
    private static function normalizar(string $chave): string
    {
        return strlen($chave) <= 100 && !str_contains($chave, '@') ? $chave : substr($chave, 0, strpos($chave, ':') ?: 20) . ':' . hash('sha256', $chave);
    }
}
