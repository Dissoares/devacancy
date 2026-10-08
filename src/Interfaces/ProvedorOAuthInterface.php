<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\UsuarioSocialDTO;
use App\Enums\ProvedorSocial;

interface ProvedorOAuthInterface
{
    public function provedor(): ProvedorSocial;

    public function estaConfigurado(): bool;

    public function urlAutorizacao(string $estado): string;

    /** Troca o código de autorização pelos dados do usuário. */
    public function obterUsuario(string $codigo): UsuarioSocialDTO;
}
