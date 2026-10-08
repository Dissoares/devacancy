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
        return match ($this) {
            self::Remoto => 'Remoto',
            self::Hibrido => 'Híbrido',
            self::Presencial => 'Presencial',
            self::Indiferente => 'Tanto faz',
        };
    }
}
