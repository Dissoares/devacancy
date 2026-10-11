<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ComissaoRepositoryInterface;
use PDO;

final class ComissaoRepository implements ComissaoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criarSeNova(int $indicadorId, int $indicadoId, int $pagamentoId, float $valor, string $moeda): bool
    {
        $consulta = $this->pdo->prepare(
            'INSERT IGNORE INTO comissoes (usuario_indicador_id, usuario_indicado_id, pagamento_id, valor, moeda)
             VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$indicadorId, $indicadoId, $pagamentoId, $valor, $moeda]);

        return $consulta->rowCount() === 1;
    }

    public function listarDoIndicador(int $indicadorId): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT c.*, u.nome AS indicado_nome, u.email AS indicado_email
             FROM comissoes c
             JOIN usuarios u ON u.id = c.usuario_indicado_id
             WHERE c.usuario_indicador_id = ?
             ORDER BY c.criado_em DESC'
        );
        $consulta->execute([$indicadorId]);

        return $consulta->fetchAll();
    }

    public function totaisDoIndicador(int $indicadorId): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT moeda,
                    SUM(IF(status = 'pendente', valor, 0)) AS pendente,
                    SUM(IF(status = 'paga', valor, 0)) AS paga
             FROM comissoes WHERE usuario_indicador_id = ? GROUP BY moeda"
        );
        $consulta->execute([$indicadorId]);

        $totais = [];
        foreach ($consulta->fetchAll() as $linha) {
            $totais[$linha['moeda']] = ['pendente' => (float) $linha['pendente'], 'paga' => (float) $linha['paga']];
        }

        return $totais;
    }

    public function listarTodas(): array
    {
        return $this->pdo->query(
            'SELECT c.*, ind.nome AS indicador_nome, ind.email AS indicador_email,
                    ado.nome AS indicado_nome, ado.email AS indicado_email
             FROM comissoes c
             JOIN usuarios ind ON ind.id = c.usuario_indicador_id
             JOIN usuarios ado ON ado.id = c.usuario_indicado_id
             ORDER BY c.criado_em DESC'
        )->fetchAll();
    }

    public function marcarComoPaga(int $id): bool
    {
        $consulta = $this->pdo->prepare("UPDATE comissoes SET status = 'paga', pago_em = NOW() WHERE id = ? AND status = 'pendente'");
        $consulta->execute([$id]);

        return $consulta->rowCount() === 1;
    }
}
