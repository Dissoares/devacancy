<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;

/** Liga/desliga as formas de pagamento disponíveis no checkout. */
final class AdminPagamentoController
{
    public function __construct(
        private readonly AssinaturaService $assinaturas,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function listar(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/pagamentos', [
            'titulo' => 'Pagamentos — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'metodos' => $this->assinaturas->catalogoDeMetodos(),
        ], 'app'));
    }

    public function alternar(Requisicao $requisicao): Resposta
    {
        $this->assinaturas->alternarMetodo((string) $requisicao->parametro('chave'));
        return Resposta::redirecionar('/admin/pagamentos');
    }
}
