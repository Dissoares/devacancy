<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\AssinaturaRepositoryInterface;
use PDO;

final class AssinaturaRepository implements AssinaturaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function vigenteAte(int $usuarioId): ?string
    {
        $consulta = $this->pdo->prepare('SELECT expira_em FROM assinaturas WHERE usuario_id = ? AND expira_em > NOW()');
        $consulta->execute([$usuarioId]);
        $expiraEm = $consulta->fetchColumn();
        return $expiraEm !== false ? (string) $expiraEm : null;
    }

    public function vigenteDe(int $usuarioId): ?array
    {
        $consulta = $this->pdo->prepare('SELECT plano, expira_em FROM assinaturas WHERE usuario_id = ? AND expira_em > NOW()');
        $consulta->execute([$usuarioId]);
        $linha = $consulta->fetch();
        return $linha ? ['plano' => (string) $linha['plano'], 'expira_em' => (string) $linha['expira_em']] : null;
    }

    public function estender(int $usuarioId, string $plano, int $dias): void
    {
        $this->pdo->prepare(
            'INSERT INTO assinaturas (usuario_id, plano, expira_em) VALUES (?, ?, NOW() + INTERVAL ? DAY)
             ON DUPLICATE KEY UPDATE
                plano = VALUES(plano),
                expira_em = GREATEST(assinaturas.expira_em, NOW()) + INTERVAL ? DAY'
        )->execute([$usuarioId, $plano, $dias, $dias]);
    }

    public function contarAtivas(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM assinaturas WHERE expira_em > NOW()')->fetchColumn();
    }
}
