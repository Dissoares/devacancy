<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Core\Requisicao;
use App\Core\Resposta;

interface MiddlewareInterface
{
    /** @param callable(Requisicao): Resposta $proximo */
    public function processar(Requisicao $requisicao, callable $proximo): Resposta;
}
