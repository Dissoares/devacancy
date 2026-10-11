<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoUsuario: string
{
    use ComOpcoes;

    case Profissional = 'profissional';
    case Empresa = 'empresa';
    case Admin = 'admin';

    public function rotulo(): string
    {
        return t('tipo_usuario.' . $this->value);
    }

    /** Tipos que podem se cadastrar pelo site público. */
    public static function publicos(): array
    {
        return [self::Profissional, self::Empresa];
    }
}
