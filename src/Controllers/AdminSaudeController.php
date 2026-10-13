<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Resposta;
use App\Core\Visao;
use App\Services\AutenticacaoService;
use App\Services\DiagnosticoService;

/** Sistema → Saúde do sistema: o que conferir antes (e depois) de colocar o site no ar. */
final class AdminSaudeController
{
    public function __construct(
        private readonly DiagnosticoService $diagnostico,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function painel(): Resposta
    {
        $resultado = $this->diagnostico->verificar();

        return Resposta::html(Visao::renderizar('admin/saude', [
            'titulo' => 'Saúde do sistema — Admin',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'resultado' => $resultado,
            'resumo' => $this->diagnostico->resumo($resultado),
        ], 'admin'));
    }
}
