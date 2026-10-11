<?php

declare(strict_types=1);

namespace App\Enums;

enum ModeloTrabalho: string
{
    use ComOpcoes;

    case Remoto = 'remoto';
    case Hibrido = 'hibrido';
    case Presencial = 'presencial';
    case Indiferente = 'indiferente';

    public function rotulo(): string
    {
        return t('modelo.' . $this->value);
    }
}
