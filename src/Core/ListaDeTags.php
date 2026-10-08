<?php

declare(strict_types=1);

namespace App\Core;

/** Normaliza o campo de tecnologias ("React, Node.js, PHP") em uma lista limpa e sem repetição. */
final class ListaDeTags
{
    private const MAXIMO_TAGS = 20;
    private const MAXIMO_CARACTERES = 40;

    /** @return string[] */
    public static function normalizar(string|array $entrada): array
    {
        $itens = is_array($entrada) ? $entrada : explode(',', $entrada);
        $tags = [];

        foreach ($itens as $item) {
            $tag = mb_substr(trim(strip_tags((string) $item)), 0, self::MAXIMO_CARACTERES);
            if ($tag !== '') {
                $tags[mb_strtolower($tag)] ??= $tag;
            }
        }

        return array_slice(array_values($tags), 0, self::MAXIMO_TAGS);
    }
}
