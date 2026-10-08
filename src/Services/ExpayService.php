<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;
use App\Enums\StatusPagamento;

/**
 * Cliente da API de cobranças da ExPay (https://expaybrasil.com/docs).
 * Devolve as cobranças já normalizadas: id, status, copia_cola, qr_base64 e expira_em.
 */
final class ExpayService
{
    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function configurado(): bool
    {
        return configuracao('expay.chave') !== '';
    }

    /** @param array{name: string, document: string, email: string} $cliente */
    public function criarCobrancaPix(float $valor, string $referencia, array $cliente): array
    {
        $resposta = $this->http->postJson($this->url('/charges'), [
            'amount' => round($valor, 2),
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'reference' => $referencia,
            'customer' => $cliente,
        ], [
            ...$this->cabecalhos(),
            'Idempotency-Key: devacancy-' . $referencia,
        ]);

        return $this->normalizar($resposta);
    }

    public function consultarCobranca(string $id): array
    {
        return $this->normalizar($this->http->get($this->url('/charges/' . rawurlencode($id)), $this->cabecalhos()));
    }

    private function normalizar(array $resposta): array
    {
        $cobranca = $resposta['data'] ?? $resposta;
        $expiraEm = $cobranca['expires_at'] ?? null;

        return [
            'id' => (string) ($cobranca['charge_id'] ?? $cobranca['id'] ?? ''),
            'status' => StatusPagamento::doGateway((string) ($cobranca['status'] ?? '')),
            'copia_cola' => $cobranca['pix_data']['copy_paste'] ?? $cobranca['pix_data']['qr_code'] ?? null,
            'qr_base64' => $cobranca['pix_data']['qr_code_base64'] ?? null,
            'expira_em' => $expiraEm ? date('Y-m-d H:i:s', strtotime($expiraEm)) : null,
        ];
    }

    private function url(string $caminho): string
    {
        return rtrim((string) configuracao('expay.url'), '/') . $caminho;
    }

    private function cabecalhos(): array
    {
        return ['X-API-Key: ' . configuracao('expay.chave')];
    }
}
