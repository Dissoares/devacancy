<?php

declare(strict_types=1);

namespace App\Enums;

enum Senioridade: string
{
    use ComOpcoes;

    case Estagio = 'estagio';
    case Junior = 'junior';
    case Pleno = 'pleno';
    case Senior = 'senior';
    case Especialista = 'especialista';

    public function rotulo(): string
    {
        return t('senioridade.' . $this->value);
    }
}
