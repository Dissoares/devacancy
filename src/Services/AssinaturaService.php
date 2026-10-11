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

/**
 * Planos, checkout e liberação da assinatura.
 * Brasil: PIX (ExPay, cobrança avulsa por período) e PayPal em reais. Resto do mundo: PayPal em dólar.
 * PayPal é assinatura recorrente: cada ciclo cobrado vira um pagamento e estende o prazo.
 */
final class AssinaturaService
{
    public function __construct(
        private readonly AssinaturaRepositoryInterface $assinaturas,
        private readonly PagamentoRepositoryInterface $pagamentos,
        private readonly ExpayService $expay,
        private readonly PaypalService $paypal,
        private readonly Transacao $transacao,
    ) {
    }

    /** @return array<string, array{dias: int, periodo: string, divisor: int, precos: array<string, float>, destaque?: bool}> */
    public function planos(): array
    {
        return configuracao('planos');
    }

    public function moedaPara(bool $noBrasil): string
    {
        return $noBrasil ? 'BRL' : 'USD';
    }

    /** Meios de pagamento oferecidos: PIX só para quem está no Brasil. @return string[] */
    public function metodosPara(bool $noBrasil): array
    {
        $metodos = [];
        if ($noBrasil && ($this->expay->configurado() || $this->simulacaoPix())) {
            $metodos[] = 'pix';
        }
        if ($this->paypal->configurado() || $this->simulacaoPaypal()) {
            $metodos[] = 'paypal';
        }
        return $metodos;
    }

    public function vigenteAte(Usuario $usuario): ?string
    {
        return $this->assinaturas->vigenteAte($usuario->id);
    }

    public function contarAtivas(): int
    {
        return $this->assinaturas->contarAtivas();
    }

    /** Sem chave da ExPay em ambiente local, o PIX gera cobranças de mentira para testar o fluxo. */
    public function simulacaoPix(): bool
    {
        return !$this->expay->configurado() && configuracao('app.ambiente') === 'local';
    }

    /** Sem credenciais do PayPal em ambiente local, o botão do PayPal vira um botão de simulação. */
    public function simulacaoPaypal(): bool
    {
        return !$this->paypal->configurado() && configuracao('app.ambiente') === 'local';
    }

    public function paypalClienteId(): string
    {
        return $this->paypal->clienteId();
    }

    /** ID do plano no PayPal para montar o botão de assinatura. */
    public function idPlanoPaypal(string $plano, string $moeda): ?string
    {
        return $this->paypal->idDoPlano($plano, $moeda);
    }

    // ---------------------------------------------------------------- PIX

    /** @throws ValidacaoException */
    public function iniciarPagamento(Usuario $usuario, array $dados, bool $noBrasil): Pagamento
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        $cpf = preg_replace('/\D/', '', (string) ($dados['cpf'] ?? ''));

        $erros = [];
        if ($plano === null) {
            $erros['plano'] = t('pagamento.escolha_plano');
        }
        if (!$this->cpfValido($cpf)) {
            $erros['cpf'] = t('pagamento.cpf_invalido');
        }
        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }
        if (!in_array('pix', $this->metodosPara($noBrasil), true)) {
            throw new ValidacaoException(['geral' => t('pagamento.indisponivel')]);
        }

        $valor = $plano['precos']['BRL'];
        $id = $this->pagamentos->criar($usuario->id, $dados['plano'], $valor, 'pix', 'BRL');

        if ($this->simulacaoPix()) {
            $this->pagamentos->registrarCobranca($id, 'simulado-' . $id, 'PIX-SIMULADO-DEVACANCY-' . $id, null, date('Y-m-d H:i:s', strtotime('+30 minutes')));
            return $this->pagamentos->buscarPorId($id);
        }

        try {
            $cobranca = $this->expay->criarCobrancaPix($valor, 'pagamento-' . $id, [
                'name' => $usuario->nome,
                'document' => $cpf,
                'email' => $usuario->email,
            ]);
        } catch (RuntimeException $erro) {
            $this->registrarErro('ExPay', $erro);
            $this->pagamentos->concluir($id, StatusPagamento::Falhou);
            throw new ValidacaoException(['geral' => t('pagamento.falha_pix')]);
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
            $this->registrarErro('ExPay', $erro);
            return $pagamento;
        }

        return $this->aplicarStatus($pagamento, $cobranca['status']);
    }

    public function simularPagamento(Usuario $usuario, int $id): Pagamento
    {
        if (!$this->simulacaoPix()) {
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

    // ---------------------------------------------------------------- PayPal

    /**
     * Chamado depois que a pessoa aprova a assinatura no botão do PayPal.
     * Confere na API se a assinatura é mesmo desta pessoa. Retorna true se a assinatura já está ativa.
     */
    public function confirmarPaypal(Usuario $usuario, string $assinaturaId): bool
    {
        if (!preg_match('/^[A-Z0-9-]{6,64}$/', $assinaturaId)) {
            throw new NaoEncontradoException();
        }

        $dados = $this->paypal->assinatura($assinaturaId);
        if ((int) ($dados['custom_id'] ?? 0) !== $usuario->id) {
            throw new NaoEncontradoException();
        }

        $this->sincronizarPaypal($assinaturaId, $dados);
        return $this->vigenteAte($usuario) !== null;
    }

    /**
     * Garante um pagamento para cada ciclo já cobrado pelo PayPal (idempotente: rodar de novo não duplica).
     * Usado no retorno do checkout, na página de espera e no webhook de renovação.
     */
    public function sincronizarPaypal(string $assinaturaId, ?array $dados = null): void
    {
        try {
            $dados ??= $this->paypal->assinatura($assinaturaId);
        } catch (RuntimeException $erro) {
            $this->registrarErro('PayPal', $erro);
            return;
        }

        $interno = $this->paypal->planoInterno((string) ($dados['plan_id'] ?? ''));
        $usuarioId = (int) ($dados['custom_id'] ?? 0);
        if ($interno === null || $usuarioId === 0) {
            return;
        }

        [$plano, $moeda] = $interno;
        $ciclos = 0;
        foreach ($dados['billing_info']['cycle_executions'] ?? [] as $execucao) {
            $ciclos += (int) ($execucao['cycles_completed'] ?? 0);
        }

        for ($ciclo = $this->pagamentos->contarPagosDaAssinaturaExterna($assinaturaId) + 1; $ciclo <= $ciclos; $ciclo++) {
            $this->registrarCicloPaypal($usuarioId, $plano, $moeda, $assinaturaId, $ciclo);
        }
    }

    /** O PayPal manda o ID da assinatura em resource.id (eventos de assinatura) ou resource.billing_agreement_id (cobranças). */
    public function processarWebhookPaypal(array $evento): void
    {
        $recurso = $evento['resource'] ?? [];
        $assinaturaId = (string) ($recurso['billing_agreement_id'] ?? (str_starts_with((string) ($evento['event_type'] ?? ''), 'BILLING.SUBSCRIPTION') ? ($recurso['id'] ?? '') : ''));

        if ($assinaturaId !== '') {
            $this->sincronizarPaypal($assinaturaId);
        }
    }

    /** Modo simulação (local, sem credenciais): ativa a assinatura como se o PayPal tivesse cobrado o primeiro ciclo. */
    public function simularPaypal(Usuario $usuario, string $plano, string $moeda): void
    {
        if (!$this->simulacaoPaypal() || !isset($this->planos()[$plano]['precos'][$moeda])) {
            throw new NaoEncontradoException();
        }
        $this->registrarCicloPaypal($usuario->id, $plano, $moeda, 'SIMULADO-' . strtoupper(bin2hex(random_bytes(6))), 1);
    }

    private function registrarCicloPaypal(int $usuarioId, string $plano, string $moeda, string $assinaturaId, int $ciclo): void
    {
        $config = $this->planos()[$plano] ?? throw new RuntimeException("Plano desconhecido: {$plano}");

        $this->transacao->executar(function () use ($usuarioId, $plano, $moeda, $assinaturaId, $ciclo, $config): void {
            $novo = $this->pagamentos->registrarPagoExterno(
                $usuarioId, $plano, $config['precos'][$moeda], $moeda, 'paypal', "paypal-{$assinaturaId}-{$ciclo}", $assinaturaId,
            );
            if ($novo) {
                $this->assinaturas->estender($usuarioId, $plano, $config['dias']);
            }
        });
    }

    // ---------------------------------------------------------------- Interno

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

    private function registrarErro(string $gateway, RuntimeException $erro): void
    {
        error_log(sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $gateway, $erro->getMessage()), 3, RAIZ . '/storage/logs/erros.log');
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
