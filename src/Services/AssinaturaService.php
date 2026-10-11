<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Idioma;
use App\Core\Transacao;
use App\Entities\Pagamento;
use App\Entities\Usuario;
use App\Enums\StatusPagamento;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\AssinaturaRepositoryInterface;
use App\Interfaces\MetodoPagamentoRepositoryInterface;
use App\Interfaces\PagamentoRepositoryInterface;
use RuntimeException;

/**
 * Planos, checkout e liberação da assinatura.
 * Brasil: PIX (ExPay, cobrança avulsa por período) e PayPal em reais. Resto do mundo: PayPal em dólar.
 * PayPal é assinatura recorrente: cada ciclo cobrado vira um pagamento e estende o prazo.
 */
final class AssinaturaService
{
    /** Catálogo fixo (nome, região, moedas) de cada forma de pagamento, pra tela admin. O "ativo" vem do banco. */
    private const CATALOGO = [
        'pix' => ['nome' => 'PIX (ExPay)', 'regiao' => 'Brasil', 'moedas' => ['BRL']],
        'paypal' => ['nome' => 'PayPal (assinatura, conta Business)', 'regiao' => 'Brasil e internacional', 'moedas' => ['BRL', 'USD']],
        'paypal_email' => ['nome' => 'PayPal (só e-mail, pagamento avulso)', 'regiao' => 'Brasil e internacional', 'moedas' => ['BRL', 'USD']],
    ];

    public function __construct(
        private readonly AssinaturaRepositoryInterface $assinaturas,
        private readonly PagamentoRepositoryInterface $pagamentos,
        private readonly MetodoPagamentoRepositoryInterface $metodosPagamento,
        private readonly ExpayService $expay,
        private readonly PaypalService $paypal,
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
        if ($noBrasil && ($ativos['pix'] ?? true) && ($this->expay->configurado() || $this->simulacaoPix())) {
            $metodos[] = 'pix';
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

    /** Pra tela admin: cada forma de pagamento com nome, região, moedas, se tem chave no .env e se está ligada. */
    public function catalogoDeMetodos(): array
    {
        $ativos = $this->metodosPagamento->ativos();
        $configurado = [
            'pix' => $this->expay->configurado(),
            'paypal' => $this->paypal->configurado(),
            'paypal_email' => $this->paypal->pagamentoPorEmailConfigurado(),
        ];

        $catalogo = [];
        foreach (self::CATALOGO as $chave => $info) {
            $catalogo[] = [...$info, 'chave' => $chave, 'ativo' => $ativos[$chave] ?? true, 'configurado' => $configurado[$chave]];
        }

        return $catalogo;
    }

    public function alternarMetodo(string $chave): void
    {
        if (isset(self::CATALOGO[$chave])) {
            $this->metodosPagamento->alternar($chave);
        }
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

        $this->transacao->executar(function () use ($usuarioId, $plano, $moeda, $assinaturaId, $ciclo, $config): void {
            $gatewayId = "paypal-{$assinaturaId}-{$ciclo}";
            $novo = $this->pagamentos->registrarPagoExterno(
                $usuarioId, $plano, $config['precos'][$moeda], $moeda, 'paypal', $gatewayId, $assinaturaId,
            );
            if ($novo) {
                $this->assinaturas->estender($usuarioId, $plano, $config['dias']);
                $pagamento = $this->pagamentos->buscarPorGatewayId($gatewayId);
                $this->comissoes->registrarSeElegivel($usuarioId, $pagamento->id, $config['precos'][$moeda], $moeda);
            }
        });
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

    /** @return array<int, array> pagamentos por e-mail ainda sem confirmação (para o admin conferir no app do PayPal) */
    public function paypalEmailPendentes(): array
    {
        return $this->pagamentos->listarPendentesDoMetodo('paypal_email');
    }

    /** O admin viu o dinheiro na conta PayPal e libera o plano. */
    public function confirmarPaypalEmailManualmente(int $pagamentoId): void
    {
        $pagamento = $this->pagamentos->buscarPorId($pagamentoId);
        if ($pagamento === null || $pagamento->metodo !== 'paypal_email') {
            throw new NaoEncontradoException();
        }
        $this->aplicarStatus($pagamento, StatusPagamento::Pago);
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
                $this->comissoes->registrarSeElegivel($pagamento->usuarioId, $pagamento->id, $pagamento->valor, $pagamento->moeda);
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
