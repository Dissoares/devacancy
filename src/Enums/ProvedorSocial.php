<?php

declare(strict_types=1);

namespace App\Enums;

enum ProvedorSocial: string
{
    use ComOpcoes;

    case Google = 'google';
    case Github = 'github';
    case Linkedin = 'linkedin';

    public function rotulo(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Github => 'GitHub',
            self::Linkedin => 'LinkedIn',
        };
    }
}
