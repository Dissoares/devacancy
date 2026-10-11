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

    public function criar(int $usuarioId, string $plano, float $valor, string $metodo, string $moeda = 'BRL'): int
    {
        $this->pdo->prepare('INSERT INTO pagamentos (usuario_id, plano, valor, moeda, metodo) VALUES (?, ?, ?, ?, ?)')
            ->execute([$usuarioId, $plano, $valor, $moeda, $metodo]);
        return (int) $this->pdo->lastInsertId();
    }

    public function registrarPagoExterno(int $usuarioId, string $plano, float $valor, string $moeda, string $metodo, string $gatewayId, string $assinaturaExterna): bool
    {
        $consulta = $this->pdo->prepare(
            "INSERT IGNORE INTO pagamentos (usuario_id, plano, valor, moeda, metodo, status, gateway_id, assinatura_externa, pago_em)
             VALUES (?, ?, ?, ?, ?, 'pago', ?, ?, NOW())"
        );
        $consulta->execute([$usuarioId, $plano, $valor, $moeda, $metodo, $gatewayId, $assinaturaExterna]);
        return $consulta->rowCount() === 1;
    }

    public function contarPagosDaAssinaturaExterna(string $assinaturaExterna): int
    {
        $consulta = $this->pdo->prepare("SELECT COUNT(*) FROM pagamentos WHERE assinatura_externa = ? AND status = 'pago'");
        $consulta->execute([$assinaturaExterna]);
        return (int) $consulta->fetchColumn();
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

    public function listarPendentesDoMetodo(string $metodo): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT p.id, p.plano, p.valor, p.moeda, p.criado_em, u.nome, u.email
               FROM pagamentos p JOIN usuarios u ON u.id = p.usuario_id
              WHERE p.metodo = ? AND p.status = 'pendente'
              ORDER BY p.criado_em DESC LIMIT 100"
        );
        $consulta->execute([$metodo]);
        return $consulta->fetchAll();
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
