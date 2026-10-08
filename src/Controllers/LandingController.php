<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;

final class LandingController
{
    public function inicio(Requisicao $requisicao): Resposta
    {
        return Resposta::html(Visao::renderizar('landing/inicio', [
            'titulo' => 'Devacancy — Você programa. A gente se candidata.',
            'descricao' => 'Cadastre seu perfil uma vez e receba candidaturas diárias às vagas que combinam com a sua stack e o seu nível. Empresas encontram devs já qualificados.',
        ], 'site'));
    }
}
