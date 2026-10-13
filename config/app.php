<?php

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\Conexao;
use App\Interfaces\AssinaturaRepositoryInterface;
use App\Interfaces\CandidaturaRepositoryInterface;
use App\Interfaces\ComissaoRepositoryInterface;
use App\Interfaces\ConfiguracaoRepositoryInterface;
use App\Interfaces\MetodoPagamentoRepositoryInterface;
use App\Interfaces\PagamentoRepositoryInterface;
use App\Interfaces\PerfilEmpresaRepositoryInterface;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use App\Interfaces\RedefinicaoSenhaRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;
use App\Interfaces\VagaRepositoryInterface;
use App\Repositories\AssinaturaRepository;
use App\Repositories\CandidaturaRepository;
use App\Repositories\ComissaoRepository;
use App\Repositories\ConfiguracaoRepository;
use App\Repositories\MetodoPagamentoRepository;
use App\Repositories\PagamentoRepository;
use App\Repositories\PerfilEmpresaRepository;
use App\Repositories\PerfilProfissionalRepository;
use App\Repositories\RedefinicaoSenhaRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\VagaRepository;

return [
    'app' => [
        'nome' => Ambiente::obter('APP_NOME', 'Devacancy'),
        'url' => Ambiente::obter('APP_URL', ''),
        'ambiente' => Ambiente::obter('APP_AMBIENTE', 'producao'),
    ],

    'banco' => [
        'host' => Ambiente::obter('DB_HOST', '127.0.0.1'),
        'porta' => Ambiente::obter('DB_PORTA', '3306'),
        'nome' => Ambiente::obter('DB_NOME', 'devacancy'),
        'usuario' => Ambiente::obter('DB_USUARIO', 'root'),
        'senha' => Ambiente::obter('DB_SENHA', ''),
    ],

    'oauth' => [
        'google' => [
            'cliente_id' => Ambiente::obter('GOOGLE_CLIENT_ID', ''),
            'cliente_segredo' => Ambiente::obter('GOOGLE_CLIENT_SECRET', ''),
        ],
        'github' => [
            'cliente_id' => Ambiente::obter('GITHUB_CLIENT_ID', ''),
            'cliente_segredo' => Ambiente::obter('GITHUB_CLIENT_SECRET', ''),
        ],
        'linkedin' => [
            'cliente_id' => Ambiente::obter('LINKEDIN_CLIENT_ID', ''),
            'cliente_segredo' => Ambiente::obter('LINKEDIN_CLIENT_SECRET', ''),
        ],
    ],

    // Planos de assinatura do profissional. Preço em reais (Brasil: PIX e PayPal) e em dólar (resto do mundo: PayPal).
    // 'paypal' traz o ID do plano criado no PayPal para cada moeda (php database/paypal-planos.php gera os IDs).
    // Quantas candidaturas por dia cada pessoa pode pedir (todos os planos): é o que o site promete ("até 2 por dia").
    'candidaturas' => ['por_dia' => 2],

    'planos' => [
        'semanal' => ['dias' => 7, 'periodo' => 'semana', 'divisor' => 1, 'precos' => ['BRL' => 19.90, 'USD' => 9.99]],
        'mensal' => ['dias' => 30, 'periodo' => 'mes', 'divisor' => 1, 'precos' => ['BRL' => 49.90, 'USD' => 24.99]],
        'trimestral' => ['dias' => 90, 'periodo' => 'mes', 'divisor' => 3, 'destaque' => true, 'precos' => ['BRL' => 119.70, 'USD' => 59.99]],
    ],

    'expay' => [
        'url' => Ambiente::obter('EXPAY_URL', 'https://api.expay.com.br/v1'),
        'chave' => Ambiente::obter('EXPAY_API_KEY', ''),
        'token_webhook' => Ambiente::obter('EXPAY_WEBHOOK_TOKEN', ''),
    ],

    // Asaas: checkout com PIX e cartão para quem está no Brasil (configurado no painel admin).
    'asaas' => [
        'chave' => Ambiente::obter('ASAAS_API_KEY', ''),
        'modo' => Ambiente::obter('ASAAS_MODO', 'producao'),
        'token_webhook' => Ambiente::obter('ASAAS_WEBHOOK_TOKEN', ''),
        'url' => Ambiente::obter('ASAAS_URL', ''),
    ],

    // PIX sem gateway: o "PIX copia e cola" estático da sua conta (do app do banco). O site refaz o código com o valor de cada plano.
    'pix' => [
        'copia_cola' => Ambiente::obter('PIX_COPIA_COLA', ''),
        // Alternativa sem o código do banco: só a chave PIX, o nome de quem recebe e a cidade.
        'chave' => Ambiente::obter('PIX_CHAVE', ''),
        'nome' => Ambiente::obter('PIX_NOME', ''),
        'cidade' => Ambiente::obter('PIX_CIDADE', ''),
    ],

    // Cartão por assinatura recorrente (Stripe Checkout). Webhook: {APP_URL}/webhooks/stripe com os eventos
    // checkout.session.completed e invoice.paid; o segredo (whsec_...) vai em STRIPE_WEBHOOK_SECRET.
    'stripe' => [
        'url' => Ambiente::obter('STRIPE_URL', 'https://api.stripe.com'),
        'chave_secreta' => Ambiente::obter('STRIPE_SECRET_KEY', ''),
        'segredo_webhook' => Ambiente::obter('STRIPE_WEBHOOK_SECRET', ''),
    ],

    'paypal' => [
        'modo' => Ambiente::obter('PAYPAL_MODO', 'sandbox'),
        // Conta que recebe pagamentos avulsos "só com e-mail" (sem API); o PayPal avisa o site por IPN.
        'email' => Ambiente::obter('PAYPAL_EMAIL', ''),
        'cliente_id' => Ambiente::obter('PAYPAL_CLIENT_ID', ''),
        'segredo' => Ambiente::obter('PAYPAL_SECRET', ''),
        'token_webhook' => Ambiente::obter('PAYPAL_WEBHOOK_TOKEN', ''),
        // Quantas candidaturas por dia cada pessoa pode pedir (todos os planos): é o que o site promete ("até 2 por dia").
    'candidaturas' => ['por_dia' => 2],

    'planos' => [
            'semanal' => ['BRL' => Ambiente::obter('PAYPAL_PLANO_SEMANAL_BRL', ''), 'USD' => Ambiente::obter('PAYPAL_PLANO_SEMANAL_USD', '')],
            'mensal' => ['BRL' => Ambiente::obter('PAYPAL_PLANO_MENSAL_BRL', ''), 'USD' => Ambiente::obter('PAYPAL_PLANO_MENSAL_USD', '')],
            'trimestral' => ['BRL' => Ambiente::obter('PAYPAL_PLANO_TRIMESTRAL_BRL', ''), 'USD' => Ambiente::obter('PAYPAL_PLANO_TRIMESTRAL_USD', '')],
        ],
    ],

    // Importação automática de vagas reais publicadas nos repositórios de vagas da comunidade (issues do GitHub).
    // Repositório => área padrão (o título da vaga pode trocar a área).
    'github' => [
        'token' => Ambiente::obter('GITHUB_TOKEN', ''),
        'repositorios' => [
            'frontendbr/vagas' => 'frontend',
            'react-brasil/vagas' => 'frontend',
            'backend-br/vagas' => 'backend',
            'soujava/vagas-java' => 'backend',
            'dotnetdevbr/vagas' => 'backend',
            'qa-brasil/vagas' => 'qa',
            'androiddevbr/vagas' => 'mobile',
            'cocoaheadsbrasil/vagas' => 'mobile',
        ],
    ],

    // E-mails do sistema (recuperação de senha). Com MAIL_SMTP_HOST usa SMTP; sem, usa o mail() do PHP.
    // MAIL_DE precisa ser um endereço do seu domínio (ex.: suporte@devacancy.online) para não cair no spam.
    'email' => [
        'de' => Ambiente::obter('MAIL_DE', ''),
        'nome' => Ambiente::obter('APP_NOME', 'Devacancy'),
        'smtp_host' => Ambiente::obter('MAIL_SMTP_HOST', ''),
        'smtp_porta' => Ambiente::obter('MAIL_SMTP_PORTA', '465'),
        'smtp_seguranca' => Ambiente::obter('MAIL_SMTP_SEGURANCA', 'ssl'),
        'smtp_usuario' => Ambiente::obter('MAIL_SMTP_USUARIO', ''),
        'smtp_senha' => Ambiente::obter('MAIL_SMTP_SENHA', ''),
        'smtp_verificar' => Ambiente::obter('MAIL_SMTP_VERIFICAR', 'sim'),
    ],

    // Rodar migrações pelo navegador em /install (sem acesso ao terminal). Sem MIGRACOES_TOKEN (16+ caracteres) a rota não existe.
    'instalador' => [
        'token' => Ambiente::obter('MIGRACOES_TOKEN', ''),
    ],

    // Buscadores: códigos de verificação do Google Search Console, Bing e Yandex, e a chave do IndexNow (opcional).
    'seo' => [
        'google' => Ambiente::obter('VERIFICACAO_GOOGLE', ''),
        'bing' => Ambiente::obter('VERIFICACAO_BING', ''),
        'yandex' => Ambiente::obter('VERIFICACAO_YANDEX', ''),
        'indexnow_chave' => Ambiente::obter('INDEXNOW_CHAVE', ''),
    ],

    // Fontes de vagas por API pública (sem chave). Quais ficam ligadas e as empresas dos painéis Greenhouse/Lever
    // se editam em Admin → Configurações → Importação de vagas.
    'vagas' => [
        'fontes' => Ambiente::obter('VAGAS_FONTES', 'gupy,arbeitnow,jobicy,remoteok,himalayas,greenhouse,lever'),
        'greenhouse' => Ambiente::obter('VAGAS_GREENHOUSE', 'quintoandar,vtex,stone,gitlab,duolingo,sumup,elastic,mongodb,datadog,cloudflare,wildlifestudios'),
        'lever' => Ambiente::obter('VAGAS_LEVER', 'spotify,jobandtalent,kavak'),
    ],

    // Importação automática via Adzuna (agregador de vagas, grátis para uso básico). Cadastro gratuito em developer.adzuna.com.
    'adzuna' => [
        'url' => Ambiente::obter('ADZUNA_URL', 'https://api.adzuna.com/v1/api'),
        'app_id' => Ambiente::obter('ADZUNA_APP_ID', ''),
        'app_key' => Ambiente::obter('ADZUNA_APP_KEY', ''),
        'pais' => 'br',
    ],

    // Interface => implementação, resolvidas pelo Container.
    'vinculos' => [
        PDO::class => fn () => Conexao::obter(),
        UsuarioRepositoryInterface::class => UsuarioRepository::class,
        PerfilProfissionalRepositoryInterface::class => PerfilProfissionalRepository::class,
        PerfilEmpresaRepositoryInterface::class => PerfilEmpresaRepository::class,
        VagaRepositoryInterface::class => VagaRepository::class,
        AssinaturaRepositoryInterface::class => AssinaturaRepository::class,
        PagamentoRepositoryInterface::class => PagamentoRepository::class,
        CandidaturaRepositoryInterface::class => CandidaturaRepository::class,
        ComissaoRepositoryInterface::class => ComissaoRepository::class,
        MetodoPagamentoRepositoryInterface::class => MetodoPagamentoRepository::class,
        RedefinicaoSenhaRepositoryInterface::class => RedefinicaoSenhaRepository::class,
        ConfiguracaoRepositoryInterface::class => ConfiguracaoRepository::class,
    ],
];
