<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Email;
use App\Core\Idioma;
use App\Core\Sessao;
use App\Core\Transacao;
use App\Entities\Pagamento;
use App\Entities\Usuario;
use App\Enums\StatusPagamento;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\AssinaturaRepositoryInterface;
use App\Interfaces\MetodoPagamentoRepositoryInterface;
use App\Interfaces\PagamentoRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;
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
        private readonly MetodoPagamentoRepositoryInterface $metodosPagamento,
        private readonly ExpayService $expay,
        private readonly PaypalService $paypal,
        private readonly StripeService $stripe,
        private readonly AsaasService $asaas,
        private readonly PixManualService $pixManual,
        private readonly Email $email,
        private readonly NotificacaoService $notificacoes,
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly ComissaoService $comissoes,
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

    /** Meios de pagamento oferecidos: PIX só para quem está no Brasil, e só os ligados pelo admin. @return string[] */
    public function metodosPara(bool $noBrasil): array
    {
        $ativos = $this->metodosPagamento->ativos();

        $metodos = [];
        if ($noBrasil && ($ativos['asaas'] ?? true) && $this->asaas->configurado()) {
            $metodos[] = 'asaas';
        }
        if ($noBrasil && ($ativos['pix'] ?? true) && ($this->expay->configurado() || $this->simulacaoPix())) {
            $metodos[] = 'pix';
        }
        // O PIX copia e cola do próprio dono, confirmado à mão. Aparece sempre que estiver ligado e configurado (serve
        // de alternativa se o Asaas falhar); só fica de fora quando o PIX automático da ExPay já está na lista.
        if ($noBrasil && !in_array('pix', $metodos, true) && ($ativos['pix_manual'] ?? true) && $this->pixManual->configurado()) {
            $metodos[] = 'pix_manual';
        }
        // No Brasil, a Asaas já cobre PIX e cartão: mostrar a Stripe junto só aumentaria as opções sem ganho.
        $asaasCobreOBrasil = $noBrasil && in_array('asaas', $metodos, true);
        if (!$asaasCobreOBrasil && ($ativos['stripe'] ?? true) && ($this->stripe->configurado() || $this->simulacaoStripe())) {
            $metodos[] = 'stripe';
        }
        if (($ativos['paypal'] ?? true) && ($this->paypal->configurado() || $this->simulacaoPaypal())) {
            $metodos[] = 'paypal';
        }
        // O PayPal só com e-mail é a alternativa de quem não tem a conta Business com a API da assinatura.
        if (!$this->paypal->configurado() && ($ativos['paypal_email'] ?? true) && $this->paypal->pagamentoPorEmailConfigurado()) {
            $metodos[] = 'paypal_email';
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
        return !$this->expay->configurado() && ambiente_local();
    }

    /** Sem credenciais do PayPal em ambiente local, o botão do PayPal vira um botão de simulação. */
    public function simulacaoPaypal(): bool
    {
        return !$this->paypal->configurado() && ambiente_local();
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
        // O PayPal por e-mail não tem consulta: quem confirma é o aviso (IPN) ou o admin.
        if (!$pagamento->estaPendente() || $pagamento->gatewayId === null || $pagamento->metodo !== 'pix') {
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

        $pedido = null;
        $this->transacao->executar(function () use ($usuarioId, $plano, $moeda, $assinaturaId, $ciclo, $config, &$pedido): void {
            $gatewayId = "paypal-{$assinaturaId}-{$ciclo}";
            $novo = $this->pagamentos->registrarPagoExterno(
                $usuarioId, $plano, $config['precos'][$moeda], $moeda, 'paypal', $gatewayId, $assinaturaId,
            );
            if ($novo) {
                $this->assinaturas->estender($usuarioId, $plano, $config['dias']);
                $pagamento = $this->pagamentos->buscarPorGatewayId($gatewayId);
                $this->comissoes->registrarSeElegivel($usuarioId, $pagamento->id, $config['precos'][$moeda], $moeda);
                $pedido = $pagamento->id;
            }
        });

        if ($pedido !== null) {
            $this->avisarAssinatura($usuarioId, $plano, $config['precos'][$moeda], $moeda, 'paypal', $pedido);
        }
    }

    // ---------------------------------------------------------------- Stripe (cartão, assinatura recorrente)

    /** Sem chave do Stripe em ambiente local, o botão de cartão vira um botão de simulação. */
    public function simulacaoStripe(): bool
    {
        return !$this->stripe->configurado() && ambiente_local();
    }

    /**
     * Cria a página de pagamento do Stripe e devolve o endereço para onde mandar o cliente.
     * @throws ValidacaoException
     */
    public function iniciarCheckoutStripe(Usuario $usuario, array $dados, bool $noBrasil, string $idioma): string
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        if ($plano === null) {
            throw new ValidacaoException(['plano' => t('pagamento.escolha_plano')]);
        }
        if (!in_array('stripe', $this->metodosPara($noBrasil), true) || !$this->stripe->configurado()) {
            throw new ValidacaoException(['geral' => t('pagamento.indisponivel')]);
        }

        $moeda = $this->moedaPara($noBrasil);
        [$unidade, $quantidade] = $plano['periodo'] === 'semana' ? ['week', 1] : ['month', $plano['divisor']];

        try {
            $checkout = $this->stripe->criarCheckout(
                configuracao('app.nome') . ' — ' . t('plano.' . $dados['plano']),
                (int) round($plano['precos'][$moeda] * 100),
                $moeda,
                $unidade,
                $quantidade,
                $usuario->email,
                ['usuario_id' => $usuario->id, 'plano' => $dados['plano'], 'moeda' => $moeda],
                url('/assinatura/stripe/retorno') . '?session_id={CHECKOUT_SESSION_ID}',
                url('/planos?plano=' . $dados['plano']),
                $idioma === 'pt' ? 'pt-BR' : 'en',
            );
        } catch (RuntimeException $erro) {
            $this->registrarErro('Stripe', $erro);
            throw new ValidacaoException(['geral' => t('pagamento.falha_stripe')]);
        }

        return $checkout['url'];
    }

    /** Volta do Stripe: confere a sessão direto na API (nunca confia no navegador) e libera o plano. */
    public function concluirCheckoutStripe(Usuario $usuario, string $sessaoId): bool
    {
        if (!preg_match('/^cs_[A-Za-z0-9_]{6,200}$/', $sessaoId)) {
            throw new NaoEncontradoException();
        }

        try {
            $sessao = $this->stripe->sessao($sessaoId);
        } catch (RuntimeException $erro) {
            $this->registrarErro('Stripe', $erro);
            return false;
        }
        if ((string) ($sessao['client_reference_id'] ?? '') !== (string) $usuario->id) {
            throw new NaoEncontradoException();
        }

        $assinaturaId = (string) ($sessao['subscription'] ?? '');
        if ($assinaturaId === '' || ($sessao['payment_status'] ?? '') !== 'paid') {
            return false;
        }

        $this->sincronizarStripe($assinaturaId);
        return $this->vigenteAte($usuario) !== null;
    }

    /**
     * Garante o registro do pagamento de uma fatura paga (idempotente: o mesmo aviso, ou a volta do
     * checkout somada ao webhook, não duplica). Dono e plano vêm dos metadados que nós mesmos gravamos.
     */
    public function sincronizarStripe(string $assinaturaId, ?string $faturaId = null): void
    {
        try {
            $assinatura = $this->stripe->assinatura($assinaturaId);
            $fatura = $faturaId !== null ? $this->stripe->fatura($faturaId) : ($assinatura['latest_invoice'] ?? null);
        } catch (RuntimeException $erro) {
            $this->registrarErro('Stripe', $erro);
            return;
        }

        $usuarioId = (int) ($assinatura['metadata']['usuario_id'] ?? 0);
        $chavePlano = (string) ($assinatura['metadata']['plano'] ?? '');
        $plano = $this->planos()[$chavePlano] ?? null;
        if ($usuarioId === 0 || $plano === null || !is_array($fatura) || ($fatura['status'] ?? '') !== 'paid') {
            return;
        }

        $valor = ((int) ($fatura['amount_paid'] ?? 0)) / 100;
        $moeda = strtoupper((string) ($fatura['currency'] ?? ''));
        if ($valor <= 0 || !isset($plano['precos'][$moeda])) {
            return;
        }

        $this->registrarCicloStripe($usuarioId, $chavePlano, $valor, $moeda, 'stripe-' . $fatura['id'], $assinaturaId);
    }

    /** O webhook só serve de gatilho: o que vale é o que a API do Stripe responde. */
    public function processarWebhookStripe(string $corpo, string $cabecalhoAssinatura): bool
    {
        if (!$this->stripe->avisoAutentico($corpo, $cabecalhoAssinatura)) {
            return false;
        }

        $evento = json_decode($corpo, true) ?: [];
        $objeto = $evento['data']['object'] ?? [];

        switch ($evento['type'] ?? '') {
            case 'checkout.session.completed':
                if (!empty($objeto['subscription'])) {
                    $this->sincronizarStripe((string) $objeto['subscription']);
                }
                break;
            case 'invoice.paid':
                $assinaturaId = (string) ($objeto['subscription'] ?? $objeto['parent']['subscription_details']['subscription'] ?? '');
                if ($assinaturaId !== '' && !empty($objeto['id'])) {
                    $this->sincronizarStripe($assinaturaId, (string) $objeto['id']);
                }
                break;
        }

        return true;
    }

    /** Modo simulação (local, sem chave): ativa o plano como se o Stripe tivesse cobrado o primeiro ciclo. */
    public function simularStripe(Usuario $usuario, string $plano, string $moeda): void
    {
        if (!$this->simulacaoStripe() || !isset($this->planos()[$plano]['precos'][$moeda])) {
            throw new NaoEncontradoException();
        }
        $this->registrarCicloStripe(
            $usuario->id, $plano, $this->planos()[$plano]['precos'][$moeda], $moeda,
            'stripe-SIMULADO-' . strtoupper(bin2hex(random_bytes(6))), 'sub_SIMULADO',
        );
    }

    private function registrarCicloStripe(int $usuarioId, string $chavePlano, float $valor, string $moeda, string $gatewayId, string $assinaturaId): void
    {
        $config = $this->planos()[$chavePlano];

        $pedido = null;
        $this->transacao->executar(function () use ($usuarioId, $chavePlano, $valor, $moeda, $gatewayId, $assinaturaId, $config, &$pedido): void {
            if ($this->pagamentos->registrarPagoExterno($usuarioId, $chavePlano, $valor, $moeda, 'stripe', $gatewayId, $assinaturaId)) {
                $this->assinaturas->estender($usuarioId, $chavePlano, $config['dias']);
                $pedido = $this->pagamentos->buscarPorGatewayId($gatewayId)->id;
                $this->comissoes->registrarSeElegivel($usuarioId, $pedido, $valor, $moeda);
            }
        });

        if ($pedido !== null) {
            $this->avisarAssinatura($usuarioId, $chavePlano, $valor, $moeda, 'stripe', $pedido);
        }
    }

    // ---------------------------------------------------------------- PayPal só com e-mail

    /** @throws ValidacaoException */
    public function iniciarPagamentoPaypalEmail(Usuario $usuario, array $dados, bool $noBrasil): Pagamento
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        if ($plano === null) {
            throw new ValidacaoException(['plano' => t('pagamento.escolha_plano')]);
        }
        if (!in_array('paypal_email', $this->metodosPara($noBrasil), true)) {
            throw new ValidacaoException(['geral' => t('pagamento.indisponivel')]);
        }

        $moeda = $this->moedaPara($noBrasil);
        $id = $this->pagamentos->criar($usuario->id, $dados['plano'], $plano['precos'][$moeda], 'paypal_email', $moeda);
        $this->pagamentos->registrarCobranca($id, 'paypal-email-' . $id, null, null, null);

        return $this->pagamentos->buscarPorId($id);
    }

    /** Campos do formulário que leva o cliente ao PayPal com e-mail, valor e identificação já preenchidos. @return array{url: string, campos: array<string, string>} */
    public function formularioPaypalEmail(Pagamento $pagamento): array
    {
        return [
            'url' => $this->paypal->urlPagamentoDireto(),
            'campos' => [
                'cmd' => '_xclick',
                'business' => $this->paypal->emailRecebedor(),
                'item_name' => configuracao('app.nome') . ' — ' . t('plano.' . $pagamento->plano),
                'amount' => number_format($pagamento->valor, 2, '.', ''),
                'currency_code' => $pagamento->moeda,
                'custom' => 'pagamento-' . $pagamento->id,
                'no_shipping' => '1',
                'no_note' => '1',
                'charset' => 'utf-8',
                'lc' => Idioma::atual() === 'pt' ? 'BR' : 'US',
                'notify_url' => url('/webhooks/paypal-ipn'),
                'return' => url('/pagamento/' . $pagamento->id),
                'cancel_return' => url('/planos?plano=' . $pagamento->plano),
                'rm' => '1',
            ],
        ];
    }

    /**
     * Aviso (IPN) do PayPal: só vale se o PayPal confirmar que ele mesmo o enviou, se o dinheiro foi para o
     * e-mail configurado e se moeda e valor batem com o pagamento. Qualquer coisa diferente é ignorada.
     */
    public function processarIpn(string $corpo): void
    {
        parse_str($corpo, $campos);

        if (!$this->paypal->ipnVerdadeiro($corpo)) {
            return;
        }
        if (($campos['payment_status'] ?? '') !== 'Completed' || !preg_match('/^pagamento-(\d+)$/', (string) ($campos['custom'] ?? ''), $achado)) {
            return;
        }

        $pagamento = $this->pagamentos->buscarPorId((int) $achado[1]);
        if ($pagamento === null || $pagamento->metodo !== 'paypal_email' || !$pagamento->estaPendente()) {
            return;
        }

        $recebedor = mb_strtolower($this->paypal->emailRecebedor());
        $destinoCerto = mb_strtolower((string) ($campos['receiver_email'] ?? '')) === $recebedor
            || mb_strtolower((string) ($campos['business'] ?? '')) === $recebedor;
        $valorCerto = (string) ($campos['mc_currency'] ?? '') === $pagamento->moeda
            && (float) ($campos['mc_gross'] ?? 0) + 0.005 >= $pagamento->valor;

        if (!$destinoCerto || !$valorCerto) {
            $this->registrarErro('PayPal IPN', new RuntimeException("Pagamento #{$pagamento->id} recusado: destino ou valor não conferem."));
            return;
        }

        if (!empty($campos['txn_id'])) {
            try {
                $this->pagamentos->registrarCobranca($pagamento->id, (string) $campos['txn_id'], null, null, null);
            } catch (\PDOException) {
                // txn_id repetido: o aviso já foi tratado antes; a conclusão abaixo é idempotente.
            }
        }
        $this->aplicarStatus($pagamento, StatusPagamento::Pago);
    }

    // ---------------------------------------------------------------- Asaas (PIX e cartão, Brasil)

    /**
     * Cria o pagamento e a página de checkout da Asaas; devolve o endereço para onde mandar o cliente.
     * @throws ValidacaoException
     */
    public function iniciarCheckoutAsaas(Usuario $usuario, array $dados): string
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        if ($plano === null) {
            throw new ValidacaoException(['plano' => t('pagamento.escolha_plano')]);
        }
        if (!in_array('asaas', $this->metodosPara(true), true)) {
            throw new ValidacaoException(['geral' => t('pagamento.indisponivel')]);
        }

        $valor = $plano['precos']['BRL'];
        $id = $this->pagamentos->criar($usuario->id, $dados['plano'], $valor, 'asaas', 'BRL');
        $nomeApp = (string) configuracao('app.nome');

        try {
            $checkout = $this->asaas->criarCheckout(
                $nomeApp . ' ' . t('plano.' . $dados['plano']),
                t('pagamento.asaas_item', ['app' => $nomeApp, 'plano' => t('plano.' . $dados['plano'])]),
                $valor,
                'pagamento-' . $id,
                ['name' => $usuario->nome, 'email' => $usuario->email],
                url('/pagamento/' . $id),
                url('/planos?plano=' . $dados['plano']),
            );
        } catch (RuntimeException $erro) {
            $this->registrarErro('Asaas', $erro);
            $this->pagamentos->concluir($id, StatusPagamento::Falhou);
            throw new ValidacaoException(['geral' => t('pagamento.falha_asaas')]);
        }

        $this->pagamentos->registrarCobranca($id, $checkout['id'], null, null, date('Y-m-d H:i:s', strtotime('+60 minutes')));
        return $checkout['link'];
    }

    /**
     * Aviso da Asaas. Só vale com o token certo no cabeçalho; o pagamento é achado pelo id do checkout.
     * O mesmo aviso pode chegar mais de uma vez: a conclusão do pagamento é idempotente.
     */
    public function processarWebhookAsaas(string $corpo, string $token): bool
    {
        if (!$this->asaas->avisoAutentico($token)) {
            return false;
        }

        $evento = json_decode($corpo, true) ?: [];
        $checkoutId = (string) ($evento['checkout']['id'] ?? '');
        $pagamento = $checkoutId !== '' ? $this->pagamentos->buscarPorGatewayId($checkoutId) : null;
        if ($pagamento === null || $pagamento->metodo !== 'asaas') {
            return true;
        }

        $status = match ($evento['event'] ?? '') {
            'CHECKOUT_PAID' => StatusPagamento::Pago,
            'CHECKOUT_EXPIRED' => StatusPagamento::Expirado,
            'CHECKOUT_CANCELED' => StatusPagamento::Cancelado,
            default => null,
        };
        if ($status !== null) {
            $this->aplicarStatus($pagamento, $status);
        }
        return true;
    }

    // ---------------------------------------------------------------- PIX manual (copia e cola do dono)

    /**
     * Gera o pagamento e o código PIX copia e cola com o valor do plano. Sem CPF: quem confirma é o admin.
     * @throws ValidacaoException
     */
    public function iniciarPixManual(Usuario $usuario, array $dados): Pagamento
    {
        $plano = $this->planos()[$dados['plano'] ?? ''] ?? null;
        if ($plano === null) {
            throw new ValidacaoException(['plano' => t('pagamento.escolha_plano')]);
        }
        if (!in_array('pix_manual', $this->metodosPara(true), true)) {
            throw new ValidacaoException(['geral' => t('pagamento.indisponivel')]);
        }

        $valor = $plano['precos']['BRL'];
        $id = $this->pagamentos->criar($usuario->id, $dados['plano'], $valor, 'pix_manual', 'BRL');
        $this->pagamentos->registrarCobranca($id, 'pix-manual-' . $id, $this->pixManual->codigoPara($valor, 'DV' . $id), null, null);

        return $this->pagamentos->buscarPorId($id);
    }

    /** "Já paguei": avisa a equipe por e-mail para conferir o recebimento (no máximo um aviso a cada 10 minutos por pagamento). */
    public function avisarPagamentoManual(Usuario $usuario, int $id): bool
    {
        $pagamento = $this->pagamentoDoUsuario($usuario, $id);
        if ($pagamento->metodo !== 'pix_manual' || !$pagamento->estaPendente()) {
            return false;
        }

        $chave = 'aviso_pix_' . $id;
        if ((int) Sessao::obter($chave, 0) > time() - 600) {
            return true;
        }
        Sessao::definir($chave, time());

        $destino = (string) configuracao('email.de');
        $valor = moeda($pagamento->valor, $pagamento->moeda);
        $this->email->enviar(
            $destino,
            "PIX para conferir: {$usuario->nome} diz que pagou {$valor}",
            "{$usuario->nome} ({$usuario->email}) diz que pagou {$valor} do plano {$pagamento->plano} (pagamento #{$pagamento->id}).\n\n"
                . "Confira no app do banco e confirme em " . url('/admin/pagamentos') . " para liberar o plano.",
            '<p><strong>' . e($usuario->nome) . '</strong> (' . e($usuario->email) . ') diz que pagou <strong>' . e($valor) . '</strong> do plano '
                . e($pagamento->plano) . ' (pagamento #' . $pagamento->id . ').</p><p>Confira no app do banco e confirme em <a href="'
                . e(url('/admin/pagamentos')) . '">Admin → Pagamentos</a> para liberar o plano.</p>',
        );
        return true;
    }

    /** @return array<int, array> últimos pagamentos de qualquer forma, para o admin acompanhar */
    public function ultimosPagamentos(int $limite = 30): array
    {
        return $this->pagamentos->recentes($limite);
    }

    /** @return array<int, array> pagamentos manuais (PIX e PayPal por e-mail) ainda sem confirmação */
    public function pagamentosManuaisPendentes(): array
    {
        $lista = [...$this->pagamentos->listarPendentesDoMetodo('pix_manual'), ...$this->pagamentos->listarPendentesDoMetodo('paypal_email')];
        usort($lista, fn (array $a, array $b) => strcmp($b['criado_em'], $a['criado_em']));
        return $lista;
    }

    /** O admin viu o dinheiro na conta e libera o plano. */
    public function confirmarManualmente(int $pagamentoId): void
    {
        $pagamento = $this->pagamentos->buscarPorId($pagamentoId);
        if ($pagamento === null || !in_array($pagamento->metodo, ['pix_manual', 'paypal_email'], true)) {
            throw new NaoEncontradoException();
        }
        $this->aplicarStatus($pagamento, StatusPagamento::Pago);
    }

    /** @throws ValidacaoException pagamento já concluído (pago ou estornado) ou que não existe */
    public function excluirPagamentoNaoConcluido(int $id): void
    {
        if (!$this->pagamentos->excluirNaoConcluido($id)) {
            throw new ValidacaoException(['pagamento' => 'Só dá para excluir pagamentos não concluídos.']);
        }
    }

    public function limparPagamentosNaoConcluidos(): int
    {
        return $this->pagamentos->excluirNaoConcluidosAntigos();
    }

    /** @return array{ok: bool, passos: array} */
    public function testarAsaas(): array
    {
        if (!$this->asaas->configurado()) {
            return ['ok' => false, 'passos' => [['rotulo' => 'Chave de API', 'ok' => false, 'detalhe' => 'Nenhuma chave salva em Configurações → Formas de pagamento → Asaas.']]];
        }
        return $this->asaas->testar();
    }

    public function pixManualProblema(): ?string
    {
        return $this->pixManual->problema();
    }

    // ---------------------------------------------------------------- Interno

    private function aplicarStatus(Pagamento $pagamento, StatusPagamento $status): Pagamento
    {
        if ($status === StatusPagamento::Pendente) {
            return $pagamento;
        }

        $liberou = false;
        $this->transacao->executar(function () use ($pagamento, $status, &$liberou): void {
            $mudou = $this->pagamentos->concluir($pagamento->id, $status);
            if ($mudou && $status === StatusPagamento::Pago) {
                $liberou = true;
                $plano = $this->planos()[$pagamento->plano] ?? throw new RuntimeException("Plano desconhecido: {$pagamento->plano}");
                $this->assinaturas->estender($pagamento->usuarioId, $pagamento->plano, $plano['dias']);
                $this->comissoes->registrarSeElegivel($pagamento->usuarioId, $pagamento->id, $pagamento->valor, $pagamento->moeda);
            }
        });

        // O e-mail sai só depois de gravar tudo, e só na primeira vez que este pagamento é confirmado.
        if ($liberou) {
            $this->avisarAssinatura($pagamento->usuarioId, $pagamento->plano, $pagamento->valor, $pagamento->moeda, $pagamento->metodo, $pagamento->id);
        }

        return $this->pagamentos->buscarPorId($pagamento->id);
    }

    /** E-mail de assinatura confirmada (ou de renovação, se já havia pagamento antes deste). */
    private function avisarAssinatura(int $usuarioId, string $plano, float $valor, string $moeda, string $metodo, int $pedido): void
    {
        $usuario = $this->usuarios->buscarPorId($usuarioId);
        if ($usuario === null) {
            return;
        }
        $this->notificacoes->assinaturaConfirmada(
            $usuario, $plano, $valor, $moeda, $metodo, $this->vigenteAte($usuario),
            $this->pagamentos->contarPagosDoUsuario($usuarioId) > 1, $pedido,
        );
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
