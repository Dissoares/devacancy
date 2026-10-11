<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusCandidatura;
use App\Interfaces\CandidaturaRepositoryInterface;
use PDO;

final class CandidaturaRepository implements CandidaturaRepositoryInterface
{
    private const ASSINANTE = 'EXISTS (SELECT 1 FROM assinaturas a WHERE a.usuario_id = c.usuario_id AND a.expira_em > NOW())';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criar(int $usuarioId, array $vagaIds): int
    {
        $consulta = $this->pdo->prepare('INSERT IGNORE INTO candidaturas (usuario_id, vaga_id) VALUES (?, ?)');
        $criadas = 0;
        foreach (array_unique(array_map('intval', $vagaIds)) as $vagaId) {
            $consulta->execute([$usuarioId, $vagaId]);
            $criadas += $consulta->rowCount();
        }
        return $criadas;
    }

    public function statusPorVaga(int $usuarioId): array
    {
        $consulta = $this->pdo->prepare('SELECT vaga_id, status FROM candidaturas WHERE usuario_id = ?');
        $consulta->execute([$usuarioId]);

        $status = [];
        foreach ($consulta->fetchAll() as $linha) {
            $status[(int) $linha['vaga_id']] = StatusCandidatura::from($linha['status']);
        }
        return $status;
    }

    public function listarParaEquipe(?StatusCandidatura $status, bool $somenteAssinantes, int $limite = 300): array
    {
        $condicoes = [];
        $parametros = [];
        if ($status !== null) {
            $condicoes[] = 'c.status = ?';
            $parametros[] = $status->value;
        }
        if ($somenteAssinantes) {
            $condicoes[] = self::ASSINANTE;
        }

        $consulta = $this->pdo->prepare(
            'SELECT c.id, c.status, c.criado_em,
                    u.id AS usuario_id, u.nome, u.email, u.telefone,
                    pp.curriculo_arquivo, pp.linkedin_url,
                    v.id AS vaga_id, v.titulo, v.empresa, v.link
               FROM candidaturas c
               JOIN usuarios u ON u.id = c.usuario_id
               JOIN vagas v ON v.id = c.vaga_id
               LEFT JOIN perfis_profissionais pp ON pp.usuario_id = u.id'
            . ($condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '')
            . ' ORDER BY c.criado_em ASC LIMIT ' . max(1, $limite)
        );
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    public function atualizarStatus(int $id, StatusCandidatura $status): void
    {
        $this->pdo->prepare('UPDATE candidaturas SET status = ? WHERE id = ?')->execute([$status->value, $id]);
    }

    public function contarPorStatus(): array
    {
        $linhas = $this->pdo->query('SELECT c.status, COUNT(*) AS total FROM candidaturas c WHERE ' . self::ASSINANTE . ' GROUP BY c.status')->fetchAll();
        return array_column($linhas, 'total', 'status');
    }
}
