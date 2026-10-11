<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Captura o código de quem indicou (?ref=CODIGO) e guarda na sessão até o cadastro acontecer.
 * Primeiro toque: se a pessoa já veio com um código antes, um segundo link não sobrescreve.
 */
final class Indicacao
{
    private const CHAVE_SESSAO = 'indicado_por_codigo';

    public static function capturar(Requisicao $requisicao): void
    {
        $codigo = $requisicao->consulta('ref');
        if (is_string($codigo) && $codigo !== '' && Sessao::obter(self::CHAVE_SESSAO) === null) {
            Sessao::definir(self::CHAVE_SESSAO, mb_strtoupper(trim(mb_substr($codigo, 0, 10))));
        }
    }

    public static function codigoCapturado(): ?string
    {
        return Sessao::obter(self::CHAVE_SESSAO);
    }

    public static function limpar(): void
    {
        Sessao::remover(self::CHAVE_SESSAO);
    }
}
