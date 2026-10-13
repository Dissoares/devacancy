<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Conexao;
use App\Interfaces\MetodoPagamentoRepositoryInterface;
use Throwable;

/**
 * Confere o servidor e a configuração antes de ir ao ar (e depois, de vez em quando).
 * Cada item é "ok" (tudo certo), "aviso" (funciona, mas vale resolver) ou "erro" (precisa resolver antes de vender).
 */
final class DiagnosticoService
{
    public function __construct(
        private readonly MetodoPagamentoRepositoryInterface $metodos,
        private readonly AssinaturaService $assinaturas,
        private readonly StripeService $stripe,
        private readonly AsaasService $asaas,
        private readonly PaypalService $paypal,
        private readonly PixManualService $pixManual,
    ) {
    }

    /** @return array<string, array<int, array{rotulo: string, estado: string, detalhe: string}>> */
    public function verificar(): array
    {
        return [
            'Servidor' => $this->servidor(),
            'Segurança' => $this->seguranca(),
            'Banco de dados' => $this->banco(),
            'Pagamentos' => $this->pagamentos(),
            'E-mail e conteúdo' => $this->conteudo(),
        ];
    }

    /** @param array<string, array<int, array{estado: string}>> $resultado @return array{erros: int, avisos: int, ok: int} */
    public function resumo(array $resultado): array
    {
        $contagem = ['erros' => 0, 'avisos' => 0, 'ok' => 0];
        foreach ($resultado as $itens) {
            foreach ($itens as $item) {
                $contagem[['erro' => 'erros', 'aviso' => 'avisos'][$item['estado']] ?? 'ok']++;
            }
        }
        return $contagem;
    }

    // ---------------------------------------------------------------- Servidor

    private function servidor(): array
    {
        $itens = [];
        $itens[] = $this->item('Versão do PHP', version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'erro', 'PHP ' . PHP_VERSION . ' (o site exige 8.3 ou mais).');

        foreach (['pdo_mysql' => 'erro', 'curl' => 'erro', 'mbstring' => 'erro', 'fileinfo' => 'erro', 'gd' => 'aviso', 'intl' => 'aviso'] as $extensao => $gravidade) {
            $carregada = extension_loaded($extensao);
            $efeito = ['gd' => 'sem ela, as imagens de compartilhamento das vagas usam a imagem padrão', 'intl' => 'sem ela, a remoção de acentos usa um método mais simples'][$extensao] ?? 'o site precisa dela';
            $itens[] = $this->item("Extensão {$extensao}", $carregada ? 'ok' : $gravidade, $carregada ? 'Ativa.' : "Desativada: {$efeito}.");
        }

        foreach (['storage/logs', 'storage/uploads/curriculos', 'storage/cache'] as $pasta) {
            $caminho = RAIZ . '/' . $pasta;
            if (!is_dir($caminho)) {
                @mkdir($caminho, 0775, true);
            }
            $grava = is_dir($caminho) && is_writable($caminho);
            $itens[] = $this->item("Pasta {$pasta}", $grava ? 'ok' : 'erro', $grava ? 'Pode gravar.' : 'O servidor não consegue gravar aqui. Ajuste a permissão (775) da pasta.');
        }

        $upload = $this->emBytes((string) ini_get('upload_max_filesize'));
        $post = $this->emBytes((string) ini_get('post_max_size'));
        $uploadOk = $upload >= 6 * 1024 * 1024 && $post >= 6 * 1024 * 1024;
        $itens[] = $this->item(
            'Envio de currículo (PDF)',
            $uploadOk ? 'ok' : 'aviso',
            $uploadOk ? 'Aceita arquivos de até ' . ini_get('upload_max_filesize') . '.' : 'O servidor só aceita ' . ini_get('upload_max_filesize') . ' por arquivo, e o site aceita PDFs de até 5 MB. Peça 6 MB ou mais (upload_max_filesize e post_max_size).',
        );
        $itens[] = $this->item('Fonte da imagem de compartilhamento', is_file(RAIZ . '/storage/fontes/Poppins-Bold.ttf') ? 'ok' : 'aviso', is_file(RAIZ . '/storage/fontes/Poppins-Bold.ttf') ? 'Encontrada.' : 'Suba a pasta storage/fontes junto com o site.');
        $itens[] = $this->item('Leitor de PDF do perfil', is_file(RAIZ . '/public/assets/js/vendor/pdfjs/pdf.min.js') ? 'ok' : 'erro', is_file(RAIZ . '/public/assets/js/vendor/pdfjs/pdf.min.js') ? 'Instalado.' : 'Falta a pasta public/assets/js/vendor/pdfjs: sem ela o importador de currículo não funciona.');

        return $itens;
    }

    // ---------------------------------------------------------------- Segurança

    private function seguranca(): array
    {
        $itens = [];
        $url = endereco_do_site();
        $https = str_starts_with($url, 'https://');
        $itens[] = $this->item('Endereço do site (APP_URL)', $https || ambiente_local() ? 'ok' : 'erro', $https || ambiente_local() ? $url : "{$url}: em produção o endereço precisa começar com https:// (Configurações → Site).");

        $itens[] = $this->item(
            'Ambiente',
            configuracao('app.ambiente') === 'local' && !ambiente_local() ? 'erro' : (configuracao('app.ambiente') === 'producao' ? 'ok' : 'aviso'),
            configuracao('app.ambiente') === 'producao' ? 'Produção.' : 'Configurado como "' . configuracao('app.ambiente') . '". No servidor use Produção (Configurações → Site).',
        );

        // Arquivos que nunca podem ser abertos pelo navegador.
        $expostos = [];
        $naoVerificados = 0;
        foreach (['/.env', '/composer.json', '/storage/logs/erros.log', '/src/Core/Aplicacao.php', '/vendor/autoload.php', '/database/instalar.php'] as $caminho) {
            $codigo = $this->codigoHttp(url($caminho));
            if ($codigo === null) {
                $naoVerificados++;
            } elseif ($codigo === 200) {
                $expostos[] = $caminho;
            }
        }
        if ($expostos !== []) {
            $itens[] = $this->item('Arquivos privados', 'erro', 'ABERTOS PARA QUALQUER PESSOA: ' . implode(', ', $expostos) . '. Confira se o site aponta para a pasta public/ ou se o .htaccess da raiz foi enviado.');
        } else {
            $itens[] = $this->item('Arquivos privados', $naoVerificados > 0 ? 'aviso' : 'ok', $naoVerificados > 0 ? 'Não foi possível testar daqui (o site não respondeu a si mesmo).' : '.env, logs, código e vendor não abrem pelo navegador.');
        }

        $padrao = $this->consultaUnica("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin' AND email = 'admin@devacancy.online'");
        $admins = $this->consultaUnica("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin'");
        $itens[] = $this->item(
            'Administrador padrão',
            $padrao > 0 ? 'aviso' : 'ok',
            $padrao > 0 ? 'A conta admin@devacancy.online ainda existe. Promova a sua conta em Sistema → Administradores e exclua essa.' : "{$admins} administrador(es), nenhum com a conta padrão.",
        );
        $itens[] = $this->item('Chave de instalação (MIGRACOES_TOKEN)', (string) configuracao('instalador.token') === '' ? 'ok' : 'aviso', (string) configuracao('instalador.token') === '' ? 'Desligada.' : 'Está no .env: remova depois de usar o /install.');

        return $itens;
    }

    // ---------------------------------------------------------------- Banco

    private function banco(): array
    {
        try {
            $pendentes = count((new MigracaoService(Conexao::obter(), RAIZ . '/database/migrations'))->pendentes());
            $versao = (string) Conexao::obter()->query('SELECT VERSION()')->fetchColumn();
            return [
                $this->item('Conexão', 'ok', 'Conectado (' . $versao . ').'),
                $this->item('Atualizações do banco', $pendentes === 0 ? 'ok' : 'erro', $pendentes === 0 ? 'Tudo aplicado.' : "{$pendentes} pendente(s): rode em Sistema → Banco de dados."),
            ];
        } catch (Throwable $erro) {
            return [$this->item('Conexão', 'erro', 'Falhou: ' . $erro->getMessage())];
        }
    }

    // ---------------------------------------------------------------- Pagamentos

    private function pagamentos(): array
    {
        $ativos = $this->metodos->ativos();
        $liga = fn (string $chave) => $ativos[$chave] ?? true;

        $brasil = ($liga('asaas') && $this->asaas->configurado()) || ($liga('pix_manual') && $this->pixManual->configurado())
            || ($liga('pix') && (string) configuracao('expay.chave') !== '');
        $exterior = ($liga('stripe') && $this->stripe->configurado()) || ($liga('paypal') && $this->paypal->configurado())
            || ($liga('paypal_email') && $this->paypal->pagamentoPorEmailConfigurado());

        $itens = [
            $this->item('Receber no Brasil (PIX/cartão)', $brasil ? 'ok' : 'erro', $brasil ? 'Há pelo menos uma forma ligada e configurada.' : 'Nenhuma forma de pagamento pronta para o Brasil (Configurações → Formas de pagamento).'),
            $this->item('Receber do exterior (cartão/PayPal)', $exterior ? 'ok' : 'erro', $exterior ? 'Há pelo menos uma forma ligada e configurada.' : 'Nenhuma forma pronta para fora do Brasil: sem ela, quem está no exterior não consegue assinar.'),
        ];

        if ($this->asaas->configurado()) {
            $itens[] = $this->item('Webhook da Asaas', (string) configuracao('asaas.token_webhook') !== '' ? 'ok' : 'aviso', (string) configuracao('asaas.token_webhook') !== '' ? 'Token pronto. Confirme que o webhook {APP_URL}/webhooks/asaas está cadastrado na Asaas com o evento CHECKOUT_PAID.' : 'Sem o webhook, o plano não é liberado sozinho. Cadastre {APP_URL}/webhooks/asaas na Asaas com o evento CHECKOUT_PAID.');
        }
        if ($this->stripe->configurado()) {
            $itens[] = $this->item('Webhook do Stripe', (string) configuracao('stripe.segredo_webhook') !== '' ? 'ok' : 'aviso', (string) configuracao('stripe.segredo_webhook') !== '' ? 'Segredo preenchido. Confirme que {APP_URL}/webhooks/stripe está cadastrado no Stripe (checkout.session.completed e invoice.paid).' : 'O segredo do webhook (whsec_...) precisa estar preenchido, senão as renovações não são registradas.');
        }
        if (str_starts_with((string) configuracao('stripe.chave_secreta'), 'sk_test_')) {
            $itens[] = $this->item('Stripe em modo de teste', 'aviso', 'A chave começa com sk_test_: cobranças não são reais. Troque por sk_live_ ao abrir de verdade.');
        }
        if ($this->paypal->pagamentoPorEmailConfigurado() && configuracao('paypal.modo') !== 'live') {
            $itens[] = $this->item('PayPal em modo de teste', 'aviso', 'Para receber de verdade, mude o modo do PayPal para Produção.');
        }
        if (configuracao('asaas.modo') === 'sandbox' && $this->asaas->configurado()) {
            $itens[] = $this->item('Asaas em Sandbox', 'aviso', 'A conta está em teste. Para vender de verdade, escolha Produção.');
        }

        return $itens;
    }

    // ---------------------------------------------------------------- E-mail e conteúdo

    private function conteudo(): array
    {
        $smtp = (string) configuracao('email.smtp_host') !== '';
        $vagas = $this->consultaUnica('SELECT COUNT(*) FROM vagas WHERE ativa = 1');
        $recente = $this->consultaUnica('SELECT COUNT(*) FROM vagas WHERE ativa = 1 AND criado_em >= NOW() - INTERVAL 14 DAY');

        return [
            $this->item('Envio de e-mail', $smtp ? 'ok' : 'aviso', $smtp ? 'SMTP configurado. Teste em Configurações → E-mail.' : 'Usando o envio da hospedagem, que costuma cair no spam. Configure o SMTP (Configurações → E-mail).'),
            $this->item('Vagas abertas', $vagas >= 20 ? 'ok' : ($vagas >= 5 ? 'aviso' : 'erro'), "{$vagas} vaga(s) ativa(s). Quem se cadastra precisa ver vagas compatíveis: o ideal é ter dezenas."),
            $this->item('Vagas novas (14 dias)', $recente >= 5 ? 'ok' : 'aviso', "{$recente} vaga(s) nova(s) nas últimas duas semanas. Importe de novo em Operação → Vagas."),
            $this->item('Páginas legais', 'ok', 'Privacidade e Termos publicados (/privacidade e /termos). Peça a revisão de um advogado e preencha os dados da empresa quando tiver CNPJ.'),
        ];
    }

    // ---------------------------------------------------------------- Auxiliares

    /** @return array{rotulo: string, estado: string, detalhe: string} */
    private function item(string $rotulo, string $estado, string $detalhe): array
    {
        return ['rotulo' => $rotulo, 'estado' => $estado, 'detalhe' => str_replace('{APP_URL}', rtrim(endereco_do_site(), '/'), $detalhe)];
    }

    private function consultaUnica(string $sql): int
    {
        try {
            return (int) Conexao::obter()->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function emBytes(string $valor): int
    {
        $numero = (int) $valor;
        return match (strtoupper(substr(trim($valor), -1))) {
            'G' => $numero * 1024 ** 3,
            'M' => $numero * 1024 ** 2,
            'K' => $numero * 1024,
            default => $numero,
        };
    }

    /** Código HTTP de um endereço do próprio site, ou null se não deu para testar. */
    private function codigoHttp(string $url): ?int
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true]);
        $ok = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return $ok === false || $codigo === 0 ? null : $codigo;
    }
}
