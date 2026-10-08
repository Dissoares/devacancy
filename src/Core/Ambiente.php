<?php

declare(strict_types=1);

namespace App\Core;

final class Ambiente
{
    private static array $valores = [];

    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
            self::$valores[$chave] = trim($valor, "\"'");
        }
    }

    public static function obter(string $chave, ?string $padrao = null): ?string
    {
        $valor = self::$valores[$chave] ?? getenv($chave);
        return ($valor === false || $valor === '') ? $padrao : $valor;
    }
}
