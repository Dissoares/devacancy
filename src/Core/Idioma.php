<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Idioma da interface: escolha explícita (?lang=en, guardada em sessão e cookie) ou o idioma do navegador.
 * Português para navegadores em português; inglês para todos os outros.
 */
final class Idioma
{
    public const SUPORTADOS = ['pt', 'en'];
    private const PADRAO = 'pt';
    private const COOKIE = 'devacancy_idioma';

    private static string $atual = self::PADRAO;
    private static array $textos = [];

    public static function detectar(Requisicao $requisicao): void
    {
        $escolhido = $requisicao->consulta('lang');
        if (is_string($escolhido) && in_array($escolhido, self::SUPORTADOS, true)) {
            Sessao::definir('idioma', $escolhido);
            setcookie(self::COOKIE, $escolhido, ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'samesite' => 'Lax']);
        }

        $candidato = Sessao::obter('idioma') ?? $_COOKIE[self::COOKIE] ?? self::doNavegador($requisicao->cabecalho('accept-language'));
        self::$atual = in_array($candidato, self::SUPORTADOS, true) ? $candidato : self::PADRAO;
    }

    public static function atual(): string
    {
        return self::$atual;
    }

    public static function definir(string $idioma): void
    {
        if (in_array($idioma, self::SUPORTADOS, true)) {
            self::$atual = $idioma;
        }
    }

    /** Valor do atributo lang do <html>. */
    public static function html(): string
    {
        return self::$atual === 'pt' ? 'pt-BR' : 'en';
    }

    /** Texto traduzido; ":nome" nos textos é trocado pelos parâmetros. Cai para o português e, por fim, para a chave. */
    public static function texto(string $chave, array $parametros = []): string
    {
        $texto = self::carregar(self::$atual)[$chave] ?? self::carregar(self::PADRAO)[$chave] ?? $chave;

        if ($parametros !== []) {
            uksort($parametros, fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));
            foreach ($parametros as $nome => $valor) {
                $texto = str_replace(':' . $nome, (string) $valor, $texto);
            }
        }

        return $texto;
    }

    private static function carregar(string $idioma): array
    {
        return self::$textos[$idioma] ??= require RAIZ . '/lang/' . $idioma . '.php';
    }

    private static function doNavegador(?string $cabecalho): string
    {
        if (!$cabecalho) {
            return self::PADRAO;
        }

        $preferido = strtolower(trim(explode(',', $cabecalho)[0]));
        return str_starts_with($preferido, 'pt') ? 'pt' : 'en';
    }
}
