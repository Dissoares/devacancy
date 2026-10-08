<?php

declare(strict_types=1);

namespace App\Providers;

use App\DTOs\UsuarioSocialDTO;
use App\Enums\ProvedorSocial;

final class GithubProvedor extends ProvedorOAuthBase
{
    public function provedor(): ProvedorSocial
    {
        return ProvedorSocial::Github;
    }

    protected function urlAutorizacaoBase(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function urlToken(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function escopos(): string
    {
        return 'read:user user:email';
    }

    public function obterUsuario(string $codigo): UsuarioSocialDTO
    {
        $token = $this->trocarCodigoPorToken($codigo);
        $cabecalhos = ["Authorization: Bearer {$token}", 'X-GitHub-Api-Version: 2022-11-28'];

        $perfil = $this->http->get('https://api.github.com/user', $cabecalhos);

        // O e-mail público pode estar oculto: buscamos o e-mail principal verificado.
        $email = null;
        foreach ($this->http->get('https://api.github.com/user/emails', $cabecalhos) as $item) {
            if (($item['primary'] ?? false) && ($item['verified'] ?? false)) {
                $email = $item['email'];
                break;
            }
        }

        return new UsuarioSocialDTO(
            provedor: ProvedorSocial::Github,
            provedorId: (string) $perfil['id'],
            nome: $perfil['name'] ?: $perfil['login'],
            email: $this->exigirEmail($email),
            avatarUrl: $perfil['avatar_url'] ?? null,
            perfilUrl: $perfil['html_url'] ?? null,
        );
    }
}
