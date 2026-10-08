<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const CHAVE = '_csrf_token';

    public static function token(): string
    {
        $token = Sessao::obter(self::CHAVE);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Sessao::definir(self::CHAVE, $token);
        }
        return $token;
    }

    public static function validar(?string $token): bool
    {
        $esperado = Sessao::obter(self::CHAVE);
        return is_string($token) && is_string($esperado) && hash_equals($esperado, $token);
    }
}
