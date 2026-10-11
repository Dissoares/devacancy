<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Sessao;
use App\DTOs\UsuarioSocialDTO;
use App\Enums\ProvedorSocial;
use App\Enums\TipoUsuario;
use App\Exceptions\AutenticacaoException;
use App\Exceptions\NaoEncontradoException;
use App\Interfaces\ProvedorOAuthInterface;
use App\Providers\GithubProvedor;
use App\Providers\GoogleProvedor;
use App\Providers\LinkedinProvedor;

final class OAuthService
{
    private const CHAVE_SESSAO = 'oauth';

    /** @var array<string, ProvedorOAuthInterface> */
    private array $provedores;

    public function __construct(GoogleProvedor $google, GithubProvedor $github, LinkedinProvedor $linkedin)
    {
        foreach ([$google, $github, $linkedin] as $provedor) {
            $this->provedores[$provedor->provedor()->value] = $provedor;
        }
    }

    public function provedor(string $nome): ProvedorOAuthInterface
    {
        return $this->provedores[$nome] ?? throw new NaoEncontradoException();
    }

    /** Gera a URL de autorização e guarda o "state" anti-CSRF e o tipo de conta escolhido. */
    public function iniciar(string $nome, TipoUsuario $tipo): string
    {
        $provedor = $this->provedor($nome);
        if (!$provedor->estaConfigurado()) {
            throw new AutenticacaoException(t('auth.provedor_indisponivel', ['provedor' => $provedor->provedor()->rotulo()]));
        }

        $estado = bin2hex(random_bytes(20));
        Sessao::definir(self::CHAVE_SESSAO, ['estado' => $estado, 'provedor' => $nome, 'tipo' => $tipo->value]);

        return $provedor->urlAutorizacao($estado);
    }

    /**
     * @return array{0: UsuarioSocialDTO, 1: TipoUsuario}
     * @throws AutenticacaoException
     */
    public function concluir(string $nome, ?string $codigo, ?string $estado): array
    {
        $pendente = Sessao::retirar(self::CHAVE_SESSAO);

        if (!$pendente || $pendente['provedor'] !== $nome || !is_string($estado) || !hash_equals($pendente['estado'], $estado)) {
            throw new AutenticacaoException(t('auth.falha_confirmar'));
        }
        if (!$codigo) {
            throw new AutenticacaoException(t('auth.cancelado'));
        }

        try {
            $usuario = $this->provedor($nome)->obterUsuario($codigo);
        } catch (AutenticacaoException $erro) {
            throw $erro;
        } catch (\Throwable $erro) {
            error_log('[' . date('Y-m-d H:i:s') . "] OAuth {$nome}: {$erro->getMessage()}\n", 3, RAIZ . '/storage/logs/erros.log');
            throw new AutenticacaoException(t('auth.falha_concluir'));
        }

        return [$usuario, TipoUsuario::tryFrom($pendente['tipo']) ?? TipoUsuario::Profissional];
    }

    /** @return ProvedorSocial[] provedores para exibir na tela de acordo com o tipo de conta */
    public static function provedoresPara(TipoUsuario $tipo): array
    {
        return $tipo === TipoUsuario::Empresa
            ? [ProvedorSocial::Google, ProvedorSocial::Linkedin]
            : [ProvedorSocial::Github, ProvedorSocial::Linkedin, ProvedorSocial::Google];
    }
}
