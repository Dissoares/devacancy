<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidacaoException;
use BackedEnum;

/**
 * Regras aceitas: obrigatorio, email, url, min:N, max:N, inteiro, entre:A,B, enum:Classe, aceito.
 * Campos vazios e não obrigatórios pulam as demais regras.
 */
final class Validador
{
    private const MENSAGENS = [
        'obrigatorio' => 'Campo obrigatório.',
        'email' => 'Informe um e-mail válido.',
        'url' => 'Informe um link válido (começando com https://).',
        'min' => 'Mínimo de :p caracteres.',
        'max' => 'Máximo de :p caracteres.',
        'inteiro' => 'Informe um número inteiro.',
        'entre' => 'Informe um valor entre :a e :b.',
        'enum' => 'Selecione uma opção válida.',
        'aceito' => 'É preciso aceitar para continuar.',
    ];

    /** @throws ValidacaoException */
    public static function validar(array $dados, array $regras): void
    {
        $erros = [];

        foreach ($regras as $campo => $regrasCampo) {
            $valor = $dados[$campo] ?? null;
            $vazio = $valor === null || $valor === '' || $valor === [];
            $lista = is_array($regrasCampo) ? $regrasCampo : explode('|', $regrasCampo);

            if ($vazio) {
                if (in_array('obrigatorio', $lista, true) || in_array('aceito', $lista, true)) {
                    $erros[$campo] = in_array('aceito', $lista, true) ? self::MENSAGENS['aceito'] : self::MENSAGENS['obrigatorio'];
                }
                continue;
            }

            foreach ($lista as $regra) {
                $mensagem = self::verificar($regra, $valor);
                if ($mensagem !== null) {
                    $erros[$campo] = $mensagem;
                    break;
                }
            }
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }
    }

    private static function verificar(string $regra, mixed $valor): ?string
    {
        [$nome, $parametro] = array_pad(explode(':', $regra, 2), 2, null);
        $texto = is_scalar($valor) ? (string) $valor : '';

        $valido = match ($nome) {
            'obrigatorio', 'aceito' => true,
            'email' => filter_var($texto, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($texto, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $texto) === 1,
            'min' => mb_strlen($texto) >= (int) $parametro,
            'max' => mb_strlen($texto) <= (int) $parametro,
            'inteiro' => filter_var($texto, FILTER_VALIDATE_INT) !== false,
            'entre' => self::estaEntre($texto, (string) $parametro),
            'enum' => self::pertenceAoEnum($valor, (string) $parametro),
            default => true,
        };

        if ($valido) {
            return null;
        }

        $mensagem = self::MENSAGENS[$nome] ?? 'Valor inválido.';
        if ($nome === 'entre') {
            [$a, $b] = explode(',', (string) $parametro);
            return strtr($mensagem, [':a' => $a, ':b' => $b]);
        }
        return str_replace(':p', (string) $parametro, $mensagem);
    }

    private static function estaEntre(string $valor, string $limites): bool
    {
        [$minimo, $maximo] = array_map('intval', explode(',', $limites));
        return filter_var($valor, FILTER_VALIDATE_INT) !== false && (int) $valor >= $minimo && (int) $valor <= $maximo;
    }

    /** @param class-string<BackedEnum> $classe */
    private static function pertenceAoEnum(mixed $valor, string $classe): bool
    {
        $valores = is_array($valor) ? $valor : [$valor];
        foreach ($valores as $item) {
            if (!is_string($item) || $classe::tryFrom($item) === null) {
                return false;
            }
        }
        return true;
    }
}
