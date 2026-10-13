<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Sessao;
use App\Core\Validador;
use App\Exceptions\AutenticacaoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\RedefinicaoSenhaRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;

/**
 * "Esqueci minha senha": envia um link de uso único (1 hora) e troca a senha.
 * Vale também para quem criou a conta pelo Google/GitHub e quer passar a ter senha.
 */
final class RecuperacaoSenhaService
{
    private const PEDIDOS_POR_HORA_POR_CONTA = 3;
    private const PEDIDOS_POR_SESSAO = 5;
    private const JANELA_SEGUNDOS = 900;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly RedefinicaoSenhaRepositoryInterface $redefinicoes,
        private readonly NotificacaoService $notificacoes,
    ) {
    }

    /**
     * Sempre termina igual, exista ou não a conta, para não revelar quem está cadastrado.
     * @throws ValidacaoException e-mail em formato inválido
     * @throws AutenticacaoException pedidos demais seguidos
     */
    public function solicitar(string $email): void
    {
        Validador::validar(['email' => $email], ['email' => 'obrigatorio|email|max:190']);
        $this->verificarLimiteDaSessao();

        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));
        if ($usuario === null || $this->redefinicoes->contarRecentes($usuario->id) >= self::PEDIDOS_POR_HORA_POR_CONTA) {
            return;
        }

        // O link vai por e-mail: só vale o endereço salvo pelo administrador, nunca o do cabeçalho Host da requisição.
        if (trim((string) configuracao('app.url')) === '') {
            error_log('Recuperação de senha: defina o endereço do site em Admin → Configurações → Site.');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->redefinicoes->criar($usuario->id, hash('sha256', $token));

        $link = url('/redefinir-senha/' . $token);
        $this->notificacoes->recuperacaoDeSenha($usuario, $link);
    }

    public function linkValido(string $token): bool
    {
        return $this->redefinicoes->usuarioDoLinkValido(hash('sha256', $token)) !== null;
    }

    /**
     * @throws ValidacaoException senha curta ou confirmação diferente
     * @throws AutenticacaoException link inválido, usado ou expirado
     */
    public function redefinir(string $token, string $senha, string $confirmacao): void
    {
        Validador::validar(['senha' => $senha], ['senha' => 'obrigatorio|min:8|max:72']);
        if ($senha !== $confirmacao) {
            throw new ValidacaoException(['confirmacao' => t('recuperar.senhas_diferentes')]);
        }

        $usuarioId = $this->redefinicoes->usuarioDoLinkValido(hash('sha256', $token));
        if ($usuarioId === null) {
            throw new AutenticacaoException(t('recuperar.link_invalido'));
        }

        $this->usuarios->atualizarSenha($usuarioId, password_hash($senha, PASSWORD_DEFAULT));
        // Usa o link e qualquer outro pedido em aberto.
        $this->redefinicoes->encerrarDoUsuario($usuarioId);
    }

    private function verificarLimiteDaSessao(): void
    {
        $recentes = array_values(array_filter(
            Sessao::obter('pedidos_recuperacao', []),
            fn (int $momento) => $momento > time() - self::JANELA_SEGUNDOS,
        ));
        if (count($recentes) >= self::PEDIDOS_POR_SESSAO) {
            throw new AutenticacaoException(t('auth.muitas_tentativas'));
        }

        $recentes[] = time();
        Sessao::definir('pedidos_recuperacao', $recentes);
    }
}
