<?php

declare(strict_types=1);

namespace App\Enums;

enum Moeda: string
{
    use ComOpcoes;

    case Real = 'BRL';
    case Dolar = 'USD';
    case Euro = 'EUR';

    public function rotulo(): string
    {
        return match ($this) {
            self::Real => 'R$ (BRL)',
            self::Dolar => 'US$ (USD)',
            self::Euro => '€ (EUR)',
        };
    }
}
