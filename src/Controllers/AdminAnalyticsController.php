<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Resposta;
use App\Core\Visao;
use App\Repositories\AnalyticsRepository;
use App\Services\AutenticacaoService;

final class AdminAnalyticsController
{
    public function __construct(
        private readonly AnalyticsRepository $analytics,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function painel(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/analytics', [
            'titulo' => 'Analytics — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'funil' => $this->analytics->funil(),
            'receita' => $this->analytics->receita(),
            'porSenioridade' => $this->analytics->conversaoPorColuna('senioridade'),
            'porArea' => $this->analytics->conversaoPorColuna('area'),
            'porModelo' => $this->analytics->conversaoPorColuna('modelo_trabalho'),
            'porRegiao' => $this->analytics->conversaoPorColuna('regiao_vagas'),
            'porStack' => $this->analytics->conversaoPorStack(),
        ], 'admin'));
    }
}
