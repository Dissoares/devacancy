<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Pagamento;
use App\Enums\StatusPagamento;
use App\Interfaces\PagamentoRepositoryInterface;
use PDO;

final class PagamentoRepository implements PagamentoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criar(int $usuarioId, string $plano, float $valor, string $metodo): int
    {
        $this->pdo->prepare('INSERT INTO pagamentos (usuario_id, plano, valor, metodo) VALUES (?, ?, ?, ?)')
            ->execute([$usuarioId, $plano, $valor, $metodo]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorId(int $id): ?Pagamento
    {
        $consulta = $this->pdo->prepare('SELECT * FROM pagamentos WHERE id = ?');
        $consulta->execute([$id]);
        $linha = $consulta->fetch();
        return $linha ? Pagamento::deLinha($linha) : null;
    }

    public function buscarPorGatewayId(string $gatewayId): ?Pagamento
    {
        $consulta = $this->pdo->prepare('SELECT * FROM pagamentos WHERE gateway_id = ?');
        $consulta->execute([$gatewayId]);
        $linha = $consulta->fetch();
        return $linha ? Pagamento::deLinha($linha) : null;
    }

    public function registrarCobranca(int $id, string $gatewayId, ?string $copiaCola, ?string $qrBase64, ?string $expiraEm): void
    {
        $this->pdo->prepare(
            'UPDATE pagamentos SET gateway_id = ?, pix_copia_cola = ?, pix_qr_base64 = ?, expira_em = ? WHERE id = ?'
        )->execute([$gatewayId, $copiaCola, $qrBase64, $expiraEm, $id]);
    }

    public function concluir(int $id, StatusPagamento $status): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE pagamentos SET status = ?, pago_em = IF(? = 'pago', NOW(), pago_em) WHERE id = ? AND status = 'pendente'"
        );
        $consulta->execute([$status->value, $status->value, $id]);
        return $consulta->rowCount() === 1;
    }
}
