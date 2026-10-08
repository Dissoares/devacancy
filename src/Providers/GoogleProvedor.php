<?php

declare(strict_types=1);

namespace App\Providers;

use App\DTOs\UsuarioSocialDTO;
use App\Enums\ProvedorSocial;

final class GoogleProvedor extends ProvedorOAuthBase
{
    public function provedor(): ProvedorSocial
    {
        return ProvedorSocial::Google;
    }

    protected function urlAutorizacaoBase(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function urlToken(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function escopos(): string
    {
        return 'openid email profile';
    }

    protected function parametrosExtras(): array
    {
        return ['prompt' => 'select_account'];
    }

    public function obterUsuario(string $codigo): UsuarioSocialDTO
    {
        $token = $this->trocarCodigoPorToken($codigo);
        $dados = $this->http->get('https://openidconnect.googleapis.com/v1/userinfo', ["Authorization: Bearer {$token}"]);

        $email = ($dados['email_verified'] ?? false) ? ($dados['email'] ?? null) : null;

        return new UsuarioSocialDTO(
            provedor: ProvedorSocial::Google,
            provedorId: (string) $dados['sub'],
            nome: $dados['name'] ?? 'Usuário',
            email: $this->exigirEmail($email),
            avatarUrl: $dados['picture'] ?? null,
        );
    }
}
