<?php

declare(strict_types=1);

namespace App\Enums;

/** Onde ficam as vagas: no Brasil ou no exterior (remotas internacionais). O perfil pode querer as duas. */
enum RegiaoVagas: string
{
    use ComOpcoes;

    case Brasil = 'brasil';
    case Internacional = 'internacional';
    case Ambas = 'ambas';

    public function rotulo(): string
    {
        return t('regiao.' . $this->value);
    }

    /** Regiões que uma vaga pode ter (sem "ambas"). */
    public static function deVagas(): array
    {
        return [self::Brasil, self::Internacional];
    }

    /** @return self[] regiões de vaga aceitas por um perfil */
    public function aceitas(): array
    {
        return $this === self::Ambas ? self::deVagas() : [$this];
    }
}
