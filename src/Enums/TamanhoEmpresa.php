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
        return t('tamanho.' . $this->value);
    }
}
