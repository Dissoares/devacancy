<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusCandidatura: string
{
    use ComOpcoes;

    case NaFila = 'na_fila';
    case Enviada = 'enviada';
    case Entrevista = 'entrevista';
    case Encerrada = 'encerrada';

    public function rotulo(): string
    {
        return t('candidatura.' . $this->value);
    }

    public function classeSelo(): string
    {
        return match ($this) {
            self::NaFila => 'selo-alerta',
            self::Enviada => 'selo-primario',
            self::Entrevista => 'selo-sucesso',
            self::Encerrada => 'selo-neutro',
        };
    }
}
