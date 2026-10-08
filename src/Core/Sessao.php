<?php

declare(strict_types=1);

namespace App\Core;

final class Sessao
{
    private const CHAVE_FLASH = '_flash';
    private const CHAVE_FLASH_ANTIGO = '_flash_antigo';

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        session_name('devacancy_sessao');
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 14,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        // Mensagens flash vivem por exatamente uma requisição.
        $_SESSION[self::CHAVE_FLASH_ANTIGO] = $_SESSION[self::CHAVE_FLASH] ?? [];
        $_SESSION[self::CHAVE_FLASH] = [];
    }

    public static function obter(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[$chave] ?? $padrao;
    }

    public static function definir(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public static function remover(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    public static function retirar(string $chave, mixed $padrao = null): mixed
    {
        $valor = $_SESSION[$chave] ?? $padrao;
        unset($_SESSION[$chave]);
        return $valor;
    }

    public static function flash(string $chave, mixed $valor): void
    {
        $_SESSION[self::CHAVE_FLASH][$chave] = $valor;
    }

    public static function obterFlash(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[self::CHAVE_FLASH_ANTIGO][$chave] ?? $padrao;
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $parametros['path'], $parametros['domain'] ?? '', $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
    }
}
