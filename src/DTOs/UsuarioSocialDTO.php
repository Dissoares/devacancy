<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ProvedorSocial;

/** Dados de identidade devolvidos por Google, GitHub ou LinkedIn após o login social. */
final class UsuarioSocialDTO
{
    public function __construct(
        public readonly ProvedorSocial $provedor,
        public readonly string $provedorId,
        public readonly string $nome,
        public readonly string $email,
        public readonly ?string $avatarUrl,
        public readonly ?string $perfilUrl = null,
    ) {
    }
}
