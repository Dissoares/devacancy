<?php

declare(strict_types=1);

use App\Core\Ambiente;
use App\Core\Conexao;
use App\Interfaces\AssinaturaRepositoryInterface;
use App\Interfaces\CandidaturaRepositoryInterface;
use App\Interfaces\ComissaoRepositoryInterface;
use App\Interfaces\PagamentoRepositoryInterface;
use App\Interfaces\PerfilEmpresaRepositoryInterface;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;
use App\Interfaces\VagaRepositoryInterface;
use App\Repositories\AssinaturaRepository;
use App\Repositories\CandidaturaRepository;
use App\Repositories\ComissaoRepository;
use App\Repositories\PagamentoRepository;
use App\Repositories\PerfilEmpresaRepository;
use App\Repositories\PerfilProfissionalRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\VagaRepository;

return [
    'app' => [
        'nome' => Ambiente::obter('APP_NOME', 'Devacancy'),
        'url' => Ambiente::obter('APP_URL', 'http://devacancy.test'),
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

    'paypal' => [
        'modo' => Ambiente::obter('PAYPAL_MODO', 'sandbox'),
        'cliente_id' => Ambiente::obter('PAYPAL_CLIENT_ID', ''),
        'segredo' => Ambiente::obter('PAYPAL_SECRET', ''),
        'token_webhook' => Ambiente::obter('PAYPAL_WEBHOOK_TOKEN', ''),
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

    // Importação automática via Adzuna (agregador de vagas, grátis para uso básico). Cadastro gratuito em developer.adzuna.com.
    'adzuna' => [
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
    ],
];
