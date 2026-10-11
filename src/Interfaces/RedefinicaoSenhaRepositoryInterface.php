<?php

declare(strict_types=1);

namespace App\Interfaces;

interface RedefinicaoSenhaRepositoryInterface
{
    /** O link vale 1 hora. */
    public function criar(int $usuarioId, string $tokenHash): void;

    /** Quantos links o usuário pediu na última hora (limite contra abuso). */
    public function contarRecentes(int $usuarioId): int;

    /** Id do usuário dono do link, se ele existir, não estiver usado e não tiver expirado. */
    public function usuarioDoLinkValido(string $tokenHash): ?int;

    /** Invalida todos os links ainda abertos do usuário. */
    public function encerrarDoUsuario(int $usuarioId): void;
}
