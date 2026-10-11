<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusComissao: string
{
    use ComOpcoes;

    case Pendente = 'pendente';
    case Paga = 'paga';

    public function rotulo(): string
    {
        return t('status_comissao.' . $this->value);
    }
}
