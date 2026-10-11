<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\NaoEncontradoException;
use App\Services\AutenticacaoService;
use App\Services\MigracaoService;
use RuntimeException;

/**
 * Roda as migrações do banco pelo navegador: no painel admin (logado) e, para quem ainda não tem
 * acesso ao admin, em /install com um token secreto do .env (MIGRACOES_TOKEN). Sem token no .env, /install não existe.
 */
final class AdminMigracaoController
{
    private const MAX_TENTATIVAS = 5;
    private const JANELA_SEGUNDOS = 900;

    public function __construct(private readonly AutenticacaoService $autenticacao)
    {
    }

    // ---------------------------------------------------------------- Painel admin

    public function painel(): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/migracoes', [
            'titulo' => 'Migrações — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'estado' => $this->servico()->estado(),
            'resultado' => Sessao::retirar('resultado_migracoes'),
        ], 'app'));
    }

    public function executar(): Resposta
    {
        Sessao::definir('resultado_migracoes', $this->rodar());
        return Resposta::redirecionar('/admin/migracoes');
    }

    // ---------------------------------------------------------------- /install (token)

    public function formularioToken(): Resposta
    {
        $this->exigirTokenConfigurado();
        return $this->telaToken(null, null);
    }

    public function executarComToken(Requisicao $requisicao): Resposta
    {
        $esperado = $this->exigirTokenConfigurado();

        $tentativas = Sessao::obter('tentativas_migrar', ['n' => 0, 'desde' => time()]);
        if (time() - $tentativas['desde'] > self::JANELA_SEGUNDOS) {
            $tentativas = ['n' => 0, 'desde' => time()];
        }
        if ($tentativas['n'] >= self::MAX_TENTATIVAS) {
            return $this->telaToken('Muitas tentativas. Aguarde alguns minutos e tente de novo.', null);
        }

        if (!hash_equals($esperado, (string) $requisicao->entrada('token', ''))) {
            $tentativas['n']++;
            Sessao::definir('tentativas_migrar', $tentativas);
            usleep(400000);
            return $this->telaToken('Token inválido.', null);
        }

        Sessao::remover('tentativas_migrar');

        $resultado = $this->rodar();
        $admin = null;
        if ($resultado['erro'] === null) {
            $admin = $this->prepararAdmin(trim((string) $requisicao->entrada('admin_email', '')), (string) $requisicao->entrada('admin_senha', ''));
        }

        return $this->telaToken(null, $resultado, $admin);
    }

    // ---------------------------------------------------------------- Interno

    private function servico(): MigracaoService
    {
        return new MigracaoService(Conexao::obter(), RAIZ . '/database/migrations');
    }

    /** @return array{executadas: string[], erro: ?array{arquivo: string, mensagem: string}} */
    private function rodar(): array
    {
        try {
            return $this->servico()->executarPendentes();
        } catch (RuntimeException $erro) {
            return ['executadas' => [], 'erro' => ['arquivo' => '—', 'mensagem' => $erro->getMessage()]];
        }
    }

    private function exigirTokenConfigurado(): string
    {
        $token = (string) configuracao('instalador.token', '');
        if (strlen($token) < 16) {
            throw new NaoEncontradoException();
        }

        return $token;
    }

    /**
     * Cria o administrador, ou promove a conta que já existe com esse e-mail (ex.: criada antes pelo Google),
     * e define a senha dele. Devolve a mensagem para a tela, ou null se os campos vieram vazios.
     */
    private function prepararAdmin(string $email, string $senha): ?string
    {
        if ($email === '' && $senha === '') {
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($senha) < 8 || mb_strlen($senha) > 72) {
            return 'Administrador não alterado: informe um e-mail válido e uma senha de 8 a 72 caracteres.';
        }

        Conexao::obter()->prepare(
            "INSERT INTO usuarios (tipo, nome, email, senha_hash, cadastro_completo) VALUES ('admin', 'Administrador', ?, ?, 1)
             ON DUPLICATE KEY UPDATE tipo = 'admin', senha_hash = VALUES(senha_hash), cadastro_completo = 1"
        )->execute([mb_strtolower($email), password_hash($senha, PASSWORD_DEFAULT)]);

        return 'Administrador pronto: ' . mb_strtolower($email) . '. Já pode entrar em /entrar com essa senha.';
    }

    private function telaToken(?string $erro, ?array $resultado, ?string $admin = null): Resposta
    {
        return Resposta::html(Visao::renderizar('instalador/migrar', [
            'titulo' => 'Migrações — Devacancy',
            'erro' => $erro,
            'resultado' => $resultado,
            'admin' => $admin,
        ], 'site'));
    }
}
