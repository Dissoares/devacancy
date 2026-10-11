<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RedefinicaoSenhaRepositoryInterface;
use PDO;

final class RedefinicaoSenhaRepository implements RedefinicaoSenhaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criar(int $usuarioId, string $tokenHash): void
    {
        $this->pdo->prepare('INSERT INTO redefinicoes_senha (usuario_id, token_hash, expira_em) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)')
            ->execute([$usuarioId, $tokenHash]);
    }

    public function contarRecentes(int $usuarioId): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM redefinicoes_senha WHERE usuario_id = ? AND criado_em > (NOW() - INTERVAL 1 HOUR)'
        );
        $consulta->execute([$usuarioId]);
        return (int) $consulta->fetchColumn();
    }

    public function usuarioDoLinkValido(string $tokenHash): ?int
    {
        $consulta = $this->pdo->prepare(
            'SELECT usuario_id FROM redefinicoes_senha WHERE token_hash = ? AND usado_em IS NULL AND expira_em > NOW()'
        );
        $consulta->execute([$tokenHash]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function encerrarDoUsuario(int $usuarioId): void
    {
        $this->pdo->prepare('UPDATE redefinicoes_senha SET usado_em = NOW() WHERE usuario_id = ? AND usado_em IS NULL')
            ->execute([$usuarioId]);
    }
}
