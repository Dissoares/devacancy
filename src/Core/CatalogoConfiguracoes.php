<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tudo o que pode ser configurado pelo painel admin. Cada SEÇÃO é uma página do menu lateral; cada CARTÃO é um
 * serviço com o seu próprio formulário e o seu próprio botão de salvar.
 *
 * Tipos de campo: texto, url, email, segredo (nunca volta para a tela; em branco mantém o atual),
 * token (gerado pelo site, para copiar no painel do serviço) e opcao (lista fechada).
 * Cartão com "metodo": ganha a chave "ativo no checkout" ligada à forma de pagamento de mesmo nome.
 * "obrigatorios": lista de grupos; o cartão está configurado quando cada grupo tem ao menos um campo preenchido.
 * "avisos": o que precisa ser copiado para o painel do serviço; {APP_URL} vira o endereço do site.
 */
final class CatalogoConfiguracoes
{
    public const SECOES = [
        'site' => [
            'titulo' => 'Site',
            'descricao' => 'Nome e endereço público do site.',
            'cartoes' => [
                'site' => [
                    'titulo' => 'Identidade e endereço',
                    'descricao' => 'O endereço é usado nos links dos e-mails e nos retornos dos pagamentos.',
                    'campos' => [
                        'APP_NOME' => ['rotulo' => 'Nome do site', 'tipo' => 'texto'],
                        'APP_URL' => ['rotulo' => 'Endereço do site', 'tipo' => 'url', 'ajuda' => 'Com https e sem barra no final. Ex.: https://devacancy.online'],
                        'APP_AMBIENTE' => ['rotulo' => 'Ambiente', 'tipo' => 'opcao', 'opcoes' => ['producao' => 'Produção', 'local' => 'Local (só na sua máquina)'], 'ajuda' => 'No servidor, sempre Produção.'],
                    ],
                ],
                'buscadores' => [
                    'titulo' => 'Buscadores',
                    'icone' => 'busca',
                    'descricao' => 'Para o Google, o Bing e o Yandex confirmarem que o site é seu. Copie só o código (o valor de content) da tag que cada painel mostra. O endereço do sitemap é {APP_URL}/sitemap.xml.',
                    'campos' => [
                        'VERIFICACAO_GOOGLE' => ['rotulo' => 'Google Search Console', 'tipo' => 'texto', 'ajuda' => 'search.google.com/search-console → Adicionar propriedade → Prefixo do URL → Tag HTML.'],
                        'VERIFICACAO_BING' => ['rotulo' => 'Bing Webmaster Tools', 'tipo' => 'texto', 'ajuda' => 'bing.com/webmasters → Adicionar site → Meta tag (msvalidate.01).'],
                        'VERIFICACAO_YANDEX' => ['rotulo' => 'Yandex Webmaster', 'tipo' => 'texto', 'ajuda' => 'webmaster.yandex.com → Adicionar site → Meta tag.'],
                    ],
                ],
            ],
        ],

        'pagamentos' => [
            'titulo' => 'Formas de pagamento',
            'descricao' => 'Ligue só o que estiver configurado. Quem está no Brasil vê Asaas e o PIX manual (se ligado), cartão e PayPal; quem está fora vê cartão e PayPal.',
            'cartoes' => [
                'asaas' => [
                    'titulo' => 'Asaas · PIX e cartão',
                    'descricao' => 'Checkout da Asaas, só para o Brasil. O cliente escolhe PIX ou cartão de crédito na página da Asaas e o plano é liberado sozinho.',
                    'metodo' => 'asaas',
                    'obrigatorios' => [['ASAAS_API_KEY']],
                    'campos' => [
                        'ASAAS_API_KEY' => ['rotulo' => 'Chave de API', 'tipo' => 'segredo', 'ajuda' => 'Asaas → Integrações → Chaves de API. Começa com $aact_.'],
                        'ASAAS_MODO' => ['rotulo' => 'Conta', 'tipo' => 'opcao', 'opcoes' => ['producao' => 'Produção (dinheiro de verdade)', 'sandbox' => 'Sandbox (testes)']],
                        'ASAAS_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
                    ],
                    'avisos' => ['Na Asaas, em Integrações → Webhooks, crie um webhook com a URL {APP_URL}/webhooks/asaas, cole o token acima na autenticação e marque o evento CHECKOUT_PAID.'],
                ],
                'pix_manual' => [
                    'titulo' => 'PIX manual',
                    'descricao' => 'Enquanto não há gateway: o site gera o QR e o copia e cola com o valor do plano e você confirma o recebimento em Financeiro → Pagamentos. Some sozinho quando a Asaas ou a ExPay estiverem configuradas.',
                    'metodo' => 'pix_manual',
                    'obrigatorios' => [['PIX_CHAVE', 'PIX_COPIA_COLA']],
                    'campos' => [
                        'PIX_CHAVE' => ['rotulo' => 'Chave PIX', 'tipo' => 'texto', 'ajuda' => 'Telefone com +55 (ex.: +5585999999999), CPF só números, e-mail ou chave aleatória.'],
                        'PIX_NOME' => ['rotulo' => 'Nome de quem recebe', 'tipo' => 'texto'],
                        'PIX_CIDADE' => ['rotulo' => 'Cidade', 'tipo' => 'texto'],
                        'PIX_COPIA_COLA' => ['rotulo' => 'Ou: PIX copia e cola do banco', 'tipo' => 'texto', 'ajuda' => 'Opcional. Se preenchido, tem prioridade sobre a chave.'],
                    ],
                ],
                'stripe' => [
                    'titulo' => 'Stripe · cartão',
                    'descricao' => 'Cartão com cobrança recorrente, para Brasil e exterior.',
                    'metodo' => 'stripe',
                    'obrigatorios' => [['STRIPE_SECRET_KEY'], ['STRIPE_WEBHOOK_SECRET']],
                    'campos' => [
                        'STRIPE_SECRET_KEY' => ['rotulo' => 'Chave secreta', 'tipo' => 'segredo', 'ajuda' => 'sk_live_… ou sk_test_…'],
                        'STRIPE_WEBHOOK_SECRET' => ['rotulo' => 'Segredo do webhook', 'tipo' => 'segredo', 'ajuda' => 'whsec_…'],
                    ],
                    'avisos' => ['No Stripe, em Developers → Webhooks, use a URL {APP_URL}/webhooks/stripe com os eventos checkout.session.completed e invoice.paid.'],
                ],
                'paypal_email' => [
                    'titulo' => 'PayPal · só com e-mail',
                    'descricao' => 'Pagamento avulso para quem não tem conta Business. O PayPal avisa o site quando o pagamento conclui; se o aviso não chegar, você confirma em Financeiro → Pagamentos.',
                    'metodo' => 'paypal_email',
                    'obrigatorios' => [['PAYPAL_EMAIL']],
                    'campos' => [
                        'PAYPAL_EMAIL' => ['rotulo' => 'E-mail da conta PayPal', 'tipo' => 'email'],
                        'PAYPAL_MODO' => ['rotulo' => 'Modo', 'tipo' => 'opcao', 'opcoes' => ['live' => 'Produção (live)', 'sandbox' => 'Sandbox (testes)'], 'ajuda' => 'Só Produção recebe dinheiro de verdade.'],
                    ],
                ],
                'paypal' => [
                    'titulo' => 'PayPal · assinatura (Business)',
                    'descricao' => 'Assinatura recorrente automática. Exige conta Business e os planos criados no PayPal. Quando configurado, substitui o PayPal só com e-mail.',
                    'metodo' => 'paypal',
                    'obrigatorios' => [['PAYPAL_CLIENT_ID'], ['PAYPAL_SECRET']],
                    'campos' => [
                        'PAYPAL_CLIENT_ID' => ['rotulo' => 'Client ID', 'tipo' => 'texto'],
                        'PAYPAL_SECRET' => ['rotulo' => 'Secret', 'tipo' => 'segredo'],
                        'PAYPAL_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
                        'PAYPAL_PLANO_SEMANAL_BRL' => ['rotulo' => 'Plano semanal (BRL)', 'tipo' => 'texto'],
                        'PAYPAL_PLANO_MENSAL_BRL' => ['rotulo' => 'Plano mensal (BRL)', 'tipo' => 'texto'],
                        'PAYPAL_PLANO_TRIMESTRAL_BRL' => ['rotulo' => 'Plano trimestral (BRL)', 'tipo' => 'texto'],
                        'PAYPAL_PLANO_SEMANAL_USD' => ['rotulo' => 'Plano semanal (USD)', 'tipo' => 'texto'],
                        'PAYPAL_PLANO_MENSAL_USD' => ['rotulo' => 'Plano mensal (USD)', 'tipo' => 'texto'],
                        'PAYPAL_PLANO_TRIMESTRAL_USD' => ['rotulo' => 'Plano trimestral (USD)', 'tipo' => 'texto'],
                    ],
                    'avisos' => ['Webhook no PayPal: {APP_URL}/webhooks/paypal?token=TOKEN_DO_WEBHOOK'],
                ],
                'expay' => [
                    'titulo' => 'ExPay · PIX',
                    'descricao' => 'PIX automático pela ExPay, para quem está no Brasil.',
                    'metodo' => 'pix',
                    'obrigatorios' => [['EXPAY_API_KEY']],
                    'campos' => [
                        'EXPAY_API_KEY' => ['rotulo' => 'Chave de API', 'tipo' => 'segredo'],
                        'EXPAY_WEBHOOK_TOKEN' => ['rotulo' => 'Token do webhook', 'tipo' => 'token'],
                        'EXPAY_URL' => ['rotulo' => 'Endereço da API', 'tipo' => 'url', 'ajuda' => 'Deixe em branco para usar o padrão.'],
                    ],
                    'avisos' => ['No painel da ExPay, use a URL {APP_URL}/webhooks/expay?token=TOKEN_DO_WEBHOOK'],
                ],
            ],
        ],

        'login' => [
            'titulo' => 'Login social',
            'descricao' => 'Entrar com Google, GitHub ou LinkedIn. Cada um precisa de um app criado no site do serviço; o botão só aparece para quem tem as duas chaves.',
            'cartoes' => [
                'google' => [
                    'titulo' => 'Google',
                    'icone' => 'google',
                    'descricao' => 'Console do Google Cloud → APIs e serviços → Credenciais → ID do cliente OAuth.',
                    'obrigatorios' => [['GOOGLE_CLIENT_ID'], ['GOOGLE_CLIENT_SECRET']],
                    'campos' => [
                        'GOOGLE_CLIENT_ID' => ['rotulo' => 'Client ID', 'tipo' => 'texto'],
                        'GOOGLE_CLIENT_SECRET' => ['rotulo' => 'Client secret', 'tipo' => 'segredo'],
                    ],
                    'avisos' => ['URI de redirecionamento autorizado: {APP_URL}/auth/google/retorno'],
                ],
                'github' => [
                    'titulo' => 'GitHub',
                    'icone' => 'github',
                    'descricao' => 'GitHub → Settings → Developer settings → OAuth Apps.',
                    'obrigatorios' => [['GITHUB_CLIENT_ID'], ['GITHUB_CLIENT_SECRET']],
                    'campos' => [
                        'GITHUB_CLIENT_ID' => ['rotulo' => 'Client ID', 'tipo' => 'texto'],
                        'GITHUB_CLIENT_SECRET' => ['rotulo' => 'Client secret', 'tipo' => 'segredo'],
                    ],
                    'avisos' => ['Authorization callback URL: {APP_URL}/auth/github/retorno'],
                ],
                'linkedin' => [
                    'titulo' => 'LinkedIn',
                    'icone' => 'linkedin',
                    'descricao' => 'LinkedIn Developers → seu app → Auth. Ative o produto "Sign In with LinkedIn using OpenID Connect".',
                    'obrigatorios' => [['LINKEDIN_CLIENT_ID'], ['LINKEDIN_CLIENT_SECRET']],
                    'campos' => [
                        'LINKEDIN_CLIENT_ID' => ['rotulo' => 'Client ID', 'tipo' => 'texto'],
                        'LINKEDIN_CLIENT_SECRET' => ['rotulo' => 'Client secret', 'tipo' => 'segredo'],
                    ],
                    'avisos' => ['Authorized redirect URL: {APP_URL}/auth/linkedin/retorno'],
                ],
            ],
        ],

        'email' => [
            'titulo' => 'E-mail',
            'descricao' => 'Usado na recuperação de senha e nos avisos de pagamento. Sem servidor SMTP, o site tenta o envio da hospedagem.',
            'extra' => 'teste_email',
            'cartoes' => [
                'smtp' => [
                    'titulo' => 'Servidor de envio (SMTP)',
                    'descricao' => 'Dados da caixa de e-mail criada na hospedagem.',
                    'obrigatorios' => [['MAIL_DE']],
                    'campos' => [
                        'MAIL_DE' => ['rotulo' => 'Remetente', 'tipo' => 'email', 'ajuda' => 'Um e-mail do seu domínio, ex.: suporte@devacancy.online'],
                        'MAIL_SMTP_HOST' => ['rotulo' => 'Servidor SMTP', 'tipo' => 'texto', 'ajuda' => 'Ex.: mail.devacancy.online'],
                        'MAIL_SMTP_PORTA' => ['rotulo' => 'Porta', 'tipo' => 'texto', 'ajuda' => '465 (SSL) ou 587 (TLS)'],
                        'MAIL_SMTP_SEGURANCA' => ['rotulo' => 'Segurança', 'tipo' => 'opcao', 'opcoes' => ['ssl' => 'SSL', 'tls' => 'TLS', 'nenhuma' => 'Nenhuma']],
                        'MAIL_SMTP_USUARIO' => ['rotulo' => 'Usuário', 'tipo' => 'texto', 'ajuda' => 'O e-mail completo da caixa.'],
                        'MAIL_SMTP_SENHA' => ['rotulo' => 'Senha', 'tipo' => 'segredo'],
                        'MAIL_SMTP_VERIFICAR' => ['rotulo' => 'Verificar certificado', 'tipo' => 'opcao', 'opcoes' => ['sim' => 'Sim', 'nao' => 'Não (se o envio der erro de certificado)']],
                    ],
                ],
            ],
        ],

        'vagas' => [
            'titulo' => 'Importação de vagas',
            'descricao' => 'Fontes das vagas importadas. A importação em si fica em Operação → Vagas.',
            'cartoes' => [
                'github' => [
                    'titulo' => 'GitHub',
                    'icone' => 'github',
                    'descricao' => 'Vagas dos repositórios da comunidade. O token é opcional e aumenta o limite de consultas (sem ele, 60 por hora).',
                    'campos' => [
                        'GITHUB_TOKEN' => ['rotulo' => 'Token de acesso', 'tipo' => 'segredo'],
                    ],
                ],
                'fontes' => [
                    'titulo' => 'Fontes por API pública',
                    'icone' => 'busca',
                    'descricao' => 'Sem chave. Gupy traz as vagas do Brasil; as demais trazem vagas remotas do mundo todo. A origem de cada vaga aparece na página dela.',
                    'campos' => [
                        'VAGAS_FONTES' => ['rotulo' => 'Fontes ligadas', 'tipo' => 'texto', 'ajuda' => 'Separe por vírgula: gupy, arbeitnow, jobicy, remoteok, himalayas, greenhouse, lever.'],
                        'VAGAS_GREENHOUSE' => ['rotulo' => 'Empresas no Greenhouse', 'tipo' => 'texto', 'ajuda' => 'Identificadores dos painéis, separados por vírgula (o final de boards.greenhouse.io/empresa).'],
                        'VAGAS_LEVER' => ['rotulo' => 'Empresas no Lever', 'tipo' => 'texto', 'ajuda' => 'Identificadores dos painéis, separados por vírgula (o final de jobs.lever.co/empresa).'],
                    ],
                ],
                'adzuna' => [
                    'titulo' => 'Adzuna',
                    'descricao' => 'Agregador de vagas (plano pago da Adzuna).',
                    'obrigatorios' => [['ADZUNA_APP_ID'], ['ADZUNA_APP_KEY']],
                    'campos' => [
                        'ADZUNA_APP_ID' => ['rotulo' => 'App ID', 'tipo' => 'texto'],
                        'ADZUNA_APP_KEY' => ['rotulo' => 'App key', 'tipo' => 'segredo'],
                    ],
                ],
            ],
        ],
    ];
}
