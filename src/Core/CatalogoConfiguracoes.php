<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tudo o que pode ser configurado pelo painel admin, agrupado como aparece na tela.
 * Tipos: texto, url, email, segredo (nunca volta para a tela; em branco mantém o atual),
 * token (gerado pelo site e mostrado para copiar no painel do serviço) e opcao (lista fechada).
 * "avisos" são instruções e endereços que a pessoa precisa copiar para o serviço externo; {APP_URL} é trocado.
 */
final class CatalogoConfiguracoes
{
    public const GRUPOS = [
        'geral' => [
            'titulo' => 'Site',
            'descricao' => 'Nome e endereço público. O endereço é usado nos links dos e-mails e nos retornos dos pagamentos.',
            'campos' => [
                'APP_NOME' => ['rotulo' => 'Nome do site', 'tipo' => 'texto'],
                'APP_URL' => ['rotulo' => 'Endereço do site', 'tipo' => 'url', 'ajuda' => 'Com https e sem barra no final. Ex.: https://devacancy.online'],
                'APP_AMBIENTE' => ['rotulo' => 'Ambiente', 'tipo' => 'opcao', 'opcoes' => ['producao' => 'Produção', 'local' => 'Local (só na sua máquina)'], 'ajuda' => 'No servidor, sempre Produção.'],
            ],
        ],
        'asaas' => [
            'titulo' => 'Asaas (PIX e cartão)',
            'descricao' => 'Checkout da Asaas para quem está no Brasil: o cliente paga com PIX ou cartão de crédito na página da Asaas e o plano é liberado sozinho.',
            'campos' => [
                'ASAAS_API_KEY' => ['rotulo' => 'Chave de API', 'tipo' => 'segredo', 'ajuda' => 'Asaas → Integrações → Chaves de API. Começa com $aact_.'],
                'ASAAS_MODO' => ['rotulo' => 'Conta', 'tipo' => 'opcao', 'opcoes' => ['producao' => 'Produção (dinheiro de verdade)', 'sandbox' => 'Sandbox (testes)']],
                'ASAAS_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
            ],
            'avisos' => [
                'Na Asaas, em Integrações → Webhooks, crie um webhook com a URL {APP_URL}/webhooks/asaas, cole o token acima no campo de autenticação e marque o evento CHECKOUT_PAID.',
            ],
        ],
        'pix_manual' => [
            'titulo' => 'PIX manual',
            'descricao' => 'Enquanto não há gateway: o site gera o QR e o copia e cola com o valor do plano e você confirma o recebimento em Pagamentos. Some sozinho quando a Asaas ou a ExPay estiverem configuradas.',
            'campos' => [
                'PIX_CHAVE' => ['rotulo' => 'Chave PIX', 'tipo' => 'texto', 'ajuda' => 'Telefone com +55 (ex.: +5585999999999), CPF só números, e-mail ou chave aleatória.'],
                'PIX_NOME' => ['rotulo' => 'Nome de quem recebe', 'tipo' => 'texto'],
                'PIX_CIDADE' => ['rotulo' => 'Cidade', 'tipo' => 'texto'],
                'PIX_COPIA_COLA' => ['rotulo' => 'Ou: PIX copia e cola do banco', 'tipo' => 'texto', 'ajuda' => 'Opcional. Se preenchido, tem prioridade sobre a chave.'],
            ],
        ],
        'email' => [
            'titulo' => 'E-mail',
            'descricao' => 'Usado na recuperação de senha e nos avisos de pagamento. Sem servidor SMTP, o site tenta o envio da hospedagem.',
            'campos' => [
                'MAIL_DE' => ['rotulo' => 'Remetente', 'tipo' => 'email', 'ajuda' => 'Um e-mail do seu domínio, ex.: suporte@devacancy.online'],
                'MAIL_SMTP_HOST' => ['rotulo' => 'Servidor SMTP', 'tipo' => 'texto', 'ajuda' => 'Ex.: mail.devacancy.online'],
                'MAIL_SMTP_PORTA' => ['rotulo' => 'Porta', 'tipo' => 'texto', 'ajuda' => '465 (SSL) ou 587 (TLS)'],
                'MAIL_SMTP_SEGURANCA' => ['rotulo' => 'Segurança', 'tipo' => 'opcao', 'opcoes' => ['ssl' => 'SSL', 'tls' => 'TLS', 'nenhuma' => 'Nenhuma']],
                'MAIL_SMTP_USUARIO' => ['rotulo' => 'Usuário', 'tipo' => 'texto', 'ajuda' => 'O e-mail completo da caixa.'],
                'MAIL_SMTP_SENHA' => ['rotulo' => 'Senha', 'tipo' => 'segredo'],
                'MAIL_SMTP_VERIFICAR' => ['rotulo' => 'Verificar certificado', 'tipo' => 'opcao', 'opcoes' => ['sim' => 'Sim', 'nao' => 'Não (se o envio der erro de certificado)']],
            ],
            'avisos' => ['Depois de salvar, teste em Admin → E-mail.'],
        ],
        'stripe' => [
            'titulo' => 'Stripe (cartão, assinatura)',
            'descricao' => 'Cartão com cobrança recorrente, para Brasil e exterior.',
            'campos' => [
                'STRIPE_SECRET_KEY' => ['rotulo' => 'Chave secreta', 'tipo' => 'segredo', 'ajuda' => 'sk_live_… ou sk_test_…'],
                'STRIPE_WEBHOOK_SECRET' => ['rotulo' => 'Segredo do webhook', 'tipo' => 'segredo', 'ajuda' => 'whsec_…'],
            ],
            'avisos' => ['No Stripe, em Developers → Webhooks, use a URL {APP_URL}/webhooks/stripe com os eventos checkout.session.completed e invoice.paid.'],
        ],
        'paypal' => [
            'titulo' => 'PayPal',
            'descricao' => 'Só com e-mail: pagamento avulso, liberado pelo aviso do PayPal ou por você. Com Client ID e Secret (conta Business): assinatura recorrente.',
            'campos' => [
                'PAYPAL_MODO' => ['rotulo' => 'Modo', 'tipo' => 'opcao', 'opcoes' => ['live' => 'Produção (live)', 'sandbox' => 'Sandbox (testes)']],
                'PAYPAL_EMAIL' => ['rotulo' => 'E-mail da conta PayPal', 'tipo' => 'email'],
                'PAYPAL_CLIENT_ID' => ['rotulo' => 'Client ID (Business)', 'tipo' => 'texto'],
                'PAYPAL_SECRET' => ['rotulo' => 'Secret (Business)', 'tipo' => 'segredo'],
                'PAYPAL_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
                'PAYPAL_PLANO_SEMANAL_BRL' => ['rotulo' => 'ID do plano semanal (BRL)', 'tipo' => 'texto'],
                'PAYPAL_PLANO_MENSAL_BRL' => ['rotulo' => 'ID do plano mensal (BRL)', 'tipo' => 'texto'],
                'PAYPAL_PLANO_TRIMESTRAL_BRL' => ['rotulo' => 'ID do plano trimestral (BRL)', 'tipo' => 'texto'],
                'PAYPAL_PLANO_SEMANAL_USD' => ['rotulo' => 'ID do plano semanal (USD)', 'tipo' => 'texto'],
                'PAYPAL_PLANO_MENSAL_USD' => ['rotulo' => 'ID do plano mensal (USD)', 'tipo' => 'texto'],
                'PAYPAL_PLANO_TRIMESTRAL_USD' => ['rotulo' => 'ID do plano trimestral (USD)', 'tipo' => 'texto'],
            ],
            'avisos' => ['Webhook da assinatura (Business): {APP_URL}/webhooks/paypal?token=TOKEN_DO_WEBHOOK.'],
        ],
        'expay' => [
            'titulo' => 'ExPay (PIX)',
            'descricao' => 'PIX automático pela ExPay, para quem está no Brasil.',
            'campos' => [
                'EXPAY_API_KEY' => ['rotulo' => 'Chave de API', 'tipo' => 'segredo'],
                'EXPAY_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
                'EXPAY_URL' => ['rotulo' => 'Endereço da API', 'tipo' => 'url', 'ajuda' => 'Deixe em branco para usar o padrão.'],
            ],
            'avisos' => ['No painel da ExPay, use a URL {APP_URL}/webhooks/expay?token=TOKEN_DO_WEBHOOK.'],
        ],
        'login' => [
            'titulo' => 'Login social',
            'descricao' => 'Entrar com Google, GitHub e LinkedIn. Cada um precisa de um app criado no site do serviço.',
            'campos' => [
                'GOOGLE_CLIENT_ID' => ['rotulo' => 'Google: Client ID', 'tipo' => 'texto'],
                'GOOGLE_CLIENT_SECRET' => ['rotulo' => 'Google: Client secret', 'tipo' => 'segredo'],
                'GITHUB_CLIENT_ID' => ['rotulo' => 'GitHub: Client ID', 'tipo' => 'texto'],
                'GITHUB_CLIENT_SECRET' => ['rotulo' => 'GitHub: Client secret', 'tipo' => 'segredo'],
                'LINKEDIN_CLIENT_ID' => ['rotulo' => 'LinkedIn: Client ID', 'tipo' => 'texto'],
                'LINKEDIN_CLIENT_SECRET' => ['rotulo' => 'LinkedIn: Client secret', 'tipo' => 'segredo'],
            ],
            'avisos' => ['URLs de retorno: {APP_URL}/auth/google/retorno, {APP_URL}/auth/github/retorno e {APP_URL}/auth/linkedin/retorno.'],
        ],
        'vagas' => [
            'titulo' => 'Importação de vagas',
            'descricao' => 'Opcionais. O token do GitHub aumenta o limite de consultas; a Adzuna é paga.',
            'campos' => [
                'GITHUB_TOKEN' => ['rotulo' => 'Token do GitHub', 'tipo' => 'segredo'],
                'ADZUNA_APP_ID' => ['rotulo' => 'Adzuna: App ID', 'tipo' => 'texto'],
                'ADZUNA_APP_KEY' => ['rotulo' => 'Adzuna: App key', 'tipo' => 'segredo'],
            ],
        ],
    ];

    /** @return array{rotulo: string, tipo: string}|null */
    public static function campo(string $chave): ?array
    {
        foreach (self::GRUPOS as $grupo) {
            if (isset($grupo['campos'][$chave])) {
                return $grupo['campos'][$chave];
            }
        }
        return null;
    }
}
