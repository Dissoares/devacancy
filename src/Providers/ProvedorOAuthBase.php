<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\ClienteHttp;
use App\Exceptions\AutenticacaoException;
use App\Interfaces\ProvedorOAuthInterface;

/** Fluxo "authorization code" comum aos três provedores. */
abstract class ProvedorOAuthBase implements ProvedorOAuthInterface
{
    public function __construct(protected readonly ClienteHttp $http)
    {
    }

    abstract protected function urlAutorizacaoBase(): string;

    abstract protected function urlToken(): string;

    abstract protected function escopos(): string;

    public function estaConfigurado(): bool
    {
        return $this->clienteId() !== '' && $this->clienteSegredo() !== '';
    }

    public function urlAutorizacao(string $estado): string
    {
        return $this->urlAutorizacaoBase() . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clienteId(),
            'redirect_uri' => $this->urlRetorno(),
            'scope' => $this->escopos(),
            'state' => $estado,
            ...$this->parametrosExtras(),
        ]);
    }

    protected function parametrosExtras(): array
    {
        return [];
    }

    protected function trocarCodigoPorToken(string $codigo): string
    {
        $resposta = $this->http->postFormulario($this->urlToken(), [
            'grant_type' => 'authorization_code',
            'code' => $codigo,
            'redirect_uri' => $this->urlRetorno(),
            'client_id' => $this->clienteId(),
            'client_secret' => $this->clienteSegredo(),
        ]);

        return $resposta['access_token'] ?? throw new AutenticacaoException(t('auth.provedor_negou'));
    }

    protected function exigirEmail(?string $email): string
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AutenticacaoException(
                "Sua conta do {$this->provedor()->rotulo()} não tem um e-mail verificado. Cadastre-se com e-mail e senha."
            );
        }
        return mb_strtolower($email);
    }

    protected function urlRetorno(): string
    {
        return url('/auth/' . $this->provedor()->value . '/retorno');
    }

    private function clienteId(): string
    {
        return (string) configuracao('oauth.' . $this->provedor()->value . '.cliente_id', '');
    }

    private function clienteSegredo(): string
    {
        return (string) configuracao('oauth.' . $this->provedor()->value . '.cliente_segredo', '');
    }
}
