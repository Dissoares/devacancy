<?php

declare(strict_types=1);

namespace App\Enums;

/** Nível de inglês, só perguntado a quem marcou inglês nos idiomas que fala. */
enum NivelIngles: string
{
    use ComOpcoes;

    case Basico = 'basico';
    case Intermediario = 'intermediario';
    case Avancado = 'avancado';
    case Fluente = 'fluente';

    public function rotulo(): string
    {
        return t('nivel_ingles.' . $this->value);
    }
}
