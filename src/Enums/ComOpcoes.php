<?php

declare(strict_types=1);

namespace App\Enums;

/** Para enums com rotulo(): gera a lista valor => rótulo usada em selects e chips. */
trait ComOpcoes
{
    /** @return array<string, string> */
    public static function opcoes(): array
    {
        $opcoes = [];
        foreach (self::cases() as $caso) {
            $opcoes[$caso->value] = $caso->rotulo();
        }
        return $opcoes;
    }

    public static function rotuloDe(?string $valor): string
    {
        return $valor !== null && ($caso = self::tryFrom($valor)) ? $caso->rotulo() : '—';
    }
}
