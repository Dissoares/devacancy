<?php

declare(strict_types=1);

namespace App\Providers;

use App\DTOs\UsuarioSocialDTO;
use App\Enums\ProvedorSocial;

/** Usa o produto "Sign In with LinkedIn using OpenID Connect". */
final class LinkedinProvedor extends ProvedorOAuthBase
{
    public function provedor(): ProvedorSocial
    {
        return ProvedorSocial::Linkedin;
    }

    protected function urlAutorizacaoBase(): string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization';
    }

    protected function urlToken(): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    protected function escopos(): string
    {
        return 'openid profile email';
    }

    public function obterUsuario(string $codigo): UsuarioSocialDTO
    {
        $token = $this->trocarCodigoPorToken($codigo);
        $dados = $this->http->get('https://api.linkedin.com/v2/userinfo', ["Authorization: Bearer {$token}"]);

        $email = ($dados['email_verified'] ?? true) ? ($dados['email'] ?? null) : null;

        return new UsuarioSocialDTO(
            provedor: ProvedorSocial::Linkedin,
            provedorId: (string) $dados['sub'],
            nome: $dados['name'] ?? trim(($dados['given_name'] ?? '') . ' ' . ($dados['family_name'] ?? '')) ?: 'Usuário',
            email: $this->exigirEmail($email),
            avatarUrl: $dados['picture'] ?? null,
        );
    }
}
