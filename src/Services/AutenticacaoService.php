<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Idioma;
use App\Core\Indicacao;
use App\Core\Rastreamento;
use App\Core\Sessao;
use App\DTOs\UsuarioSocialDTO;
use App\Entities\Usuario;
use App\Enums\TipoUsuario;
use App\Exceptions\AutenticacaoException;
use App\Interfaces\UsuarioRepositoryInterface;

final class AutenticacaoService
{
    private const LIMITE_TENTATIVAS = 5;
    private const JANELA_SEGUNDOS = 900;
    private const HASH_FICTICIO = '$2y$10$BI36CAPlsppRrZFT4g5m4OjCvE4rq.Zi0XXOz9qJd3c0OdN4WeQRS';

    private ?Usuario $usuarioAtual = null;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly NotificacaoService $notificacoes,
    ) {
    }

    /** @throws AutenticacaoException */
    public function entrar(string $email, string $senha): Usuario
    {
        $this->verificarLimiteDeTentativas();

        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));

        // Sempre roda password_verify para não revelar, pelo tempo de resposta, se o e-mail existe.
        $hash = $usuario?->senhaHash ?? self::HASH_FICTICIO;
        $senhaConfere = password_verify($senha, $hash);

        if ($usuario === null || $usuario->senhaHash === null || !$senhaConfere) {
            $this->registrarTentativaFalha();
            throw new AutenticacaoException(t('auth.falha'));
        }

        Sessao::remover('tentativas_login');
        return $usuario;
    }

    /**
     * Encontra a conta pelo login social, depois pelo e-mail (vinculando o provedor);
     * se não existir, cria uma nova conta do tipo escolhido na tela de cadastro.
     */
    public function autenticarComProvedor(UsuarioSocialDTO $social, TipoUsuario $tipoPretendido, ?string $pais = null): Usuario
    {
        $usuario = $this->usuarios->buscarPorContaSocial($social->provedor, $social->provedorId)
            ?? $this->usuarios->buscarPorEmail($social->email);

        if ($usuario === null) {
            $tipo = in_array($tipoPretendido, TipoUsuario::publicos(), true) ? $tipoPretendido : TipoUsuario::Profissional;
            $usuario = $this->usuarios->criar($tipo, $social->nome, $social->email, null, $social->avatarUrl, Idioma::atual(), $pais, Indicacao::codigoCapturado());
            Indicacao::limpar();
            Rastreamento::naProximaPagina(Rastreamento::cadastro($social->provedor->value));
            if ($usuario->ehProfissional()) {
                $this->notificacoes->boasVindas($usuario);
            }
        }

        $this->usuarios->vincularContaSocial($usuario->id, $social->provedor, $social->provedorId);
        $this->usuarios->atualizarAvatarSeVazio($usuario->id, $social->avatarUrl);

        if ($social->perfilUrl !== null) {
            Sessao::definir('sugestoes_perfil', [$social->provedor->value . '_url' => $social->perfilUrl]);
        }

        return $this->usuarios->buscarPorId($usuario->id);
    }

    public function iniciarSessao(Usuario $usuario): void
    {
        Sessao::regenerar();
        Sessao::definir('usuario', $usuario->resumoSessao());
        $this->usuarios->registrarAcesso($usuario->id);
        $this->usuarioAtual = $usuario;
    }

    public function recarregar(Usuario $usuario): Usuario
    {
        return $this->usuarios->buscarPorId($usuario->id) ?? $usuario;
    }

    public function sair(): void
    {
        $this->usuarioAtual = null;
        Sessao::destruir();
    }

    public function usuarioAtual(): ?Usuario
    {
        $id = Sessao::obter('usuario')['id'] ?? null;
        if ($id === null) {
            return null;
        }

        if ($this->usuarioAtual?->id !== $id) {
            $this->usuarioAtual = $this->usuarios->buscarPorId((int) $id);
            if ($this->usuarioAtual === null) {
                Sessao::remover('usuario');
            }
        }

        return $this->usuarioAtual;
    }

    /** Para onde mandar o usuário depois de entrar. */
    public function destinoInicial(Usuario $usuario): string
    {
        return match (true) {
            $usuario->ehAdmin() => '/admin',
            !$usuario->cadastroCompleto && $usuario->ehProfissional() => '/comecar',
            !$usuario->cadastroCompleto => '/completar-perfil',
            default => '/painel',
        };
    }

    private function verificarLimiteDeTentativas(): void
    {
        $recentes = array_filter(
            Sessao::obter('tentativas_login', []),
            fn (int $momento) => $momento > time() - self::JANELA_SEGUNDOS,
        );
        Sessao::definir('tentativas_login', array_values($recentes));

        if (count($recentes) >= self::LIMITE_TENTATIVAS) {
            throw new AutenticacaoException(t('auth.muitas_tentativas'));
        }
    }

    private function registrarTentativaFalha(): void
    {
        $tentativas = Sessao::obter('tentativas_login', []);
        $tentativas[] = time();
        Sessao::definir('tentativas_login', $tentativas);
    }
}
