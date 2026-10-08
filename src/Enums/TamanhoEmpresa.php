<?php

declare(strict_types=1);

namespace App\Enums;

enum TamanhoEmpresa: string
{
    use ComOpcoes;

    case Startup = 'ate_10';
    case Pequena = '11_50';
    case Media = '51_200';
    case Grande = '201_1000';
    case Corporacao = 'mais_1000';

    public function rotulo(): string
    {
        return match ($this) {
            self::Startup => 'Até 10 pessoas',
            self::Pequena => '11 a 50',
            self::Media => '51 a 200',
            self::Grande => '201 a 1.000',
            self::Corporacao => 'Mais de 1.000',
        };
    }
}
