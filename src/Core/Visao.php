<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Visao
{
    public static function renderizar(string $visao, array $dados = [], ?string $layout = null): string
    {
        $conteudo = self::incluir($visao, $dados);

        if ($layout === null) {
            return $conteudo;
        }

        return self::incluir('layouts/' . $layout, [...$dados, 'conteudo' => $conteudo]);
    }

    public static function parcial(string $visao, array $dados = []): string
    {
        return self::incluir($visao, $dados);
    }

    private static function incluir(string $visao, array $dados): string
    {
        $arquivo = RAIZ . '/views/' . $visao . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException("View não encontrada: {$visao}");
        }

        extract($dados, EXTR_SKIP);
        ob_start();
        try {
            require $arquivo;
        } catch (\Throwable $erro) {
            ob_end_clean();
            throw $erro;
        }
        return (string) ob_get_clean();
    }
}
