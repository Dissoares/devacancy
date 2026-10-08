<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Transacao;
use App\Entities\Pagamento;
use App\Entities\Usuario;
use App\Enums\StatusPagamento;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\AssinaturaRepositoryInterface;
use App\Interfaces\PagamentoRepositoryInterface;
use RuntimeException;

/** Planos, checkout PIX e liberação da assinatura quando o pagamento é confirmado. */
final class AssinaturaService
{
    public function __construct(
        private readonly AssinaturaRepositoryInterface $assinaturas,
        private readonly PagamentoRepositoryInterface $pagamentos,
        private readonly ExpayService $expay,
        private readonly Transacao $transacao,
    ) {
    }

    /** @return array<string, array{nome: string, valor: float, dias: int, meses: int, destaque?: bool}> */
    public function planos(): array
    {
        return configuracao('planos');
    }

    public function vigenteAte(Usuario $usuario): ?string
    {
        return $this->assinaturas->vigenteAte($usuario->id);
    }

    public function contarAtivas(): int
    {
        return $this->assinaturas->contarAtivas();
    }

    /** Sem chave da ExPay em ambiente local, o checkout gera cobranças de mentira para testar o fluxo. */
    public function modoSimulacao(): bool
    {
        return !$this->expay->configurado() && configuracao('app.ambiente') === 'local';
    }

    /** @throws ValidacaoException */
    public function iniciarPagamento(Usuario $usuario, array $dados): Pagamento
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        $cpf = preg_replace('/\D/', '', (string) ($dados['cpf'] ?? ''));

        $erros = [];
        if ($plano === null) {
            $erros['plano'] = 'Escolha um plano.';
        }
        if (!$this->cpfValido($cpf)) {
            $erros['cpf'] = 'Informe um CPF válido.';
        }
        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }
        if (!$this->expay->configurado() && !$this->modoSimulacao()) {
            throw new ValidacaoException(['geral' => 'Pagamentos indisponíveis no momento. Tente novamente em instantes.']);
        }

        $id = $this->pagamentos->criar($usuario->id, $dados['plano'], $plano['valor'], 'pix');

        if ($this->modoSimulacao()) {
            $this->pagamentos->registrarCobranca($id, 'simulado-' . $id, 'PIX-SIMULADO-DEVACANCY-' . $id, null, date('Y-m-d H:i:s', strtotime('+30 minutes')));
            return $this->pagamentos->buscarPorId($id);
        }

        try {
            $cobranca = $this->expay->criarCobrancaPix($plano['valor'], 'pagamento-' . $id, [
                'name' => $usuario->nome,
                'document' => $cpf,
                'email' => $usuario->email,
            ]);
        } catch (RuntimeException $erro) {
            error_log(sprintf("[%s] ExPay: %s\n", date('Y-m-d H:i:s'), $erro->getMessage()), 3, RAIZ . '/storage/logs/erros.log');
            $this->pagamentos->concluir($id, StatusPagamento::Falhou);
            throw new ValidacaoException(['geral' => 'Não conseguimos gerar o PIX agora. Tente novamente em instantes.']);
        }

        $this->pagamentos->registrarCobranca($id, $cobranca['id'], $cobranca['copia_cola'], $cobranca['qr_base64'], $cobranca['expira_em']);
        return $this->pagamentos->buscarPorId($id);
    }

    public function pagamentoDoUsuario(Usuario $usuario, int $id): Pagamento
    {
        $pagamento = $this->pagamentos->buscarPorId($id);
        if ($pagamento === null || $pagamento->usuarioId !== $usuario->id) {
            throw new NaoEncontradoException();
        }
        return $pagamento;
    }

    /** Confere o status no gateway (o webhook pode atrasar ou nem estar configurado). */
    public function sincronizar(Pagamento $pagamento): Pagamento
    {
        if (!$pagamento->estaPendente() || $pagamento->gatewayId === null) {
            return $pagamento;
        }

        if (str_starts_with($pagamento->gatewayId, 'simulado-')) {
            $vencido = $pagamento->expiraEm !== null && strtotime($pagamento->expiraEm) < time();
            return $vencido ? $this->aplicarStatus($pagamento, StatusPagamento::Expirado) : $pagamento;
        }

        try {
            $cobranca = $this->expay->consultarCobranca($pagamento->gatewayId);
        } catch (RuntimeException $erro) {
            error_log(sprintf("[%s] ExPay: %s\n", date('Y-m-d H:i:s'), $erro->getMessage()), 3, RAIZ . '/storage/logs/erros.log');
            return $pagamento;
        }

        return $this->aplicarStatus($pagamento, $cobranca['status']);
    }

    public function simularPagamento(Usuario $usuario, int $id): Pagamento
    {
        if (!$this->modoSimulacao()) {
            throw new NaoEncontradoException();
        }
        return $this->aplicarStatus($this->pagamentoDoUsuario($usuario, $id), StatusPagamento::Pago);
    }

    /** O webhook só serve de gatilho: o status vem sempre de uma consulta à API da ExPay. */
    public function processarWebhook(array $evento): void
    {
        $dados = $evento['data'] ?? $evento['charge'] ?? $evento;
        $gatewayId = (string) ($dados['charge_id'] ?? $dados['id'] ?? '');

        $pagamento = $gatewayId !== '' ? $this->pagamentos->buscarPorGatewayId($gatewayId) : null;
        if ($pagamento !== null) {
            $this->sincronizar($pagamento);
        }
    }

    private function aplicarStatus(Pagamento $pagamento, StatusPagamento $status): Pagamento
    {
        if ($status === StatusPagamento::Pendente) {
            return $pagamento;
        }

        $this->transacao->executar(function () use ($pagamento, $status): void {
            $mudou = $this->pagamentos->concluir($pagamento->id, $status);
            if ($mudou && $status === StatusPagamento::Pago) {
                $plano = $this->planos()[$pagamento->plano] ?? throw new RuntimeException("Plano desconhecido: {$pagamento->plano}");
                $this->assinaturas->estender($pagamento->usuarioId, $pagamento->plano, $plano['dias']);
            }
        });

        return $this->pagamentos->buscarPorId($pagamento->id);
    }

    private function cpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }
            if ((int) $cpf[$posicao] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }
}
