<?php

declare(strict_types=1);

namespace App\Enums;

enum AreaAtuacao: string
{
    use ComOpcoes;

    case Frontend = 'frontend';
    case Backend = 'backend';
    case Fullstack = 'fullstack';
    case Mobile = 'mobile';
    case Dados = 'dados';
    case DevOps = 'devops';
    case Qa = 'qa';

    public function rotulo(): string
    {
        return match ($this) {
            self::Frontend => 'Front-end',
            self::Backend => 'Back-end',
            self::Fullstack => 'Full-stack',
            self::Mobile => 'Mobile',
            self::Dados => 'Dados / IA',
            self::DevOps => 'DevOps / Cloud',
            self::Qa => 'QA / Testes',
        };
    }
}
