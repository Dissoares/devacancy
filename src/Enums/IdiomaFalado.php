<?php

declare(strict_types=1);

namespace App\Enums;

/** Idiomas que o profissional fala em nível de trabalho (pergunta do questionário). */
enum IdiomaFalado: string
{
    use ComOpcoes;

    case Portugues = 'pt';
    case Ingles = 'en';
    case Espanhol = 'es';
    case Frances = 'fr';
    case Alemao = 'de';

    public function rotulo(): string
    {
        return t('idioma_falado.' . $this->value);
    }

    /** Arquivo da bandeira (public/assets/img/bandeiras). */
    public function bandeira(): string
    {
        return match ($this) {
            self::Portugues => 'br',
            self::Ingles => 'us',
            self::Espanhol => 'es',
            self::Frances => 'fr',
            self::Alemao => 'de',
        };
    }
}
