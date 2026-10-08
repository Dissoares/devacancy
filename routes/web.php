<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AdminVagaController;
use App\Controllers\AssinaturaController;
use App\Controllers\AutenticacaoController;
use App\Controllers\LandingController;
use App\Controllers\UsuarioController;
use App\Core\Roteador;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\AutenticadoMiddleware;
use App\Middlewares\PerfilCompletoMiddleware;
use App\Middlewares\VisitanteMiddleware;

return function (Roteador $rotas): void {
    $visitante = [VisitanteMiddleware::class];
    $logado = [AutenticadoMiddleware::class];
    $admin = [AutenticadoMiddleware::class, AdminMiddleware::class];
    $perfilCompleto = [...$logado, PerfilCompletoMiddleware::class];

    $rotas->get('/', [LandingController::class, 'inicio']);

    // Cadastro e login
    $rotas->get('/cadastro', [UsuarioController::class, 'formularioCadastro'], $visitante);
    $rotas->post('/cadastro', [UsuarioController::class, 'cadastrar'], $visitante);
    $rotas->post('/cadastro/verificar-email', [UsuarioController::class, 'verificarEmail']);
    $rotas->get('/entrar', [AutenticacaoController::class, 'formularioEntrar'], $visitante);
    $rotas->post('/entrar', [AutenticacaoController::class, 'entrar'], $visitante);
    $rotas->post('/sair', [AutenticacaoController::class, 'sair']);
    $rotas->get('/auth/{provedor}', [AutenticacaoController::class, 'redirecionarParaProvedor'], $visitante);
    $rotas->get('/auth/{provedor}/retorno', [AutenticacaoController::class, 'retornoDoProvedor']);

    // Área do profissional / empresa
    $rotas->get('/completar-perfil', [UsuarioController::class, 'formularioPerfil'], $logado);
    $rotas->post('/completar-perfil', [UsuarioController::class, 'salvarPerfil'], $logado);
    $rotas->get('/painel', [UsuarioController::class, 'painel'], $perfilCompleto);

    // Funil de assinatura do profissional
    $rotas->get('/vagas-compativeis', [AssinaturaController::class, 'vagasCompativeis'], $perfilCompleto);
    $rotas->get('/planos', [AssinaturaController::class, 'planos'], $perfilCompleto);
    $rotas->post('/assinar', [AssinaturaController::class, 'assinar'], $perfilCompleto);
    $rotas->get('/pagamento/{id}', [AssinaturaController::class, 'pagamento'], $perfilCompleto);
    $rotas->get('/pagamento/{id}/status', [AssinaturaController::class, 'statusPagamento'], $perfilCompleto);
    $rotas->post('/pagamento/{id}/simular', [AssinaturaController::class, 'simularPagamento'], $perfilCompleto);
    $rotas->post('/webhooks/expay', [AssinaturaController::class, 'webhook']);

    // Administração
    $rotas->get('/admin', [AdminController::class, 'painel'], $admin);
    $rotas->get('/admin/usuarios', [AdminController::class, 'listar'], $admin);
    $rotas->get('/admin/usuarios/exportar', [AdminController::class, 'exportar'], $admin);
    $rotas->get('/admin/usuarios/{id}', [AdminController::class, 'detalhe'], $admin);
    $rotas->get('/admin/usuarios/{id}/curriculo', [AdminController::class, 'curriculo'], $admin);
    $rotas->get('/admin/vagas', [AdminVagaController::class, 'listar'], $admin);
    $rotas->post('/admin/vagas', [AdminVagaController::class, 'cadastrar'], $admin);
    $rotas->post('/admin/vagas/{id}/alternar', [AdminVagaController::class, 'alternar'], $admin);
};
