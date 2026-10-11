<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Indicacao;
use App\Core\Transacao;
use App\Core\Validador;
use App\DTOs\CadastroUsuarioDTO;
use App\DTOs\FiltroUsuariosDTO;
use App\DTOs\PerfilEmpresaDTO;
use App\DTOs\PerfilProfissionalDTO;
use App\Entities\PerfilEmpresa;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\TipoUsuario;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\PerfilEmpresaRepositoryInterface;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;
use Throwable;

final class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly PerfilProfissionalRepositoryInterface $perfisProfissionais,
        private readonly PerfilEmpresaRepositoryInterface $perfisEmpresas,
        private readonly CurriculoService $curriculos,
        private readonly Transacao $transacao,
    ) {
    }

    /** @throws ValidacaoException */
    public function cadastrar(array $dados): Usuario
    {
        Validador::validar($dados, CadastroUsuarioDTO::regras());
        $cadastro = CadastroUsuarioDTO::deArray($dados);

        if (!in_array($cadastro->tipo, TipoUsuario::publicos(), true)) {
            throw new ValidacaoException(['tipo' => t('cadastro.tipo_invalido')]);
        }
        if ($this->usuarios->emailExiste($cadastro->email)) {
            throw new ValidacaoException(['email' => t('cadastro.email_existe')]);
        }

        $usuario = $this->usuarios->criar(
            $cadastro->tipo,
            $cadastro->nome,
            $cadastro->email,
            password_hash($cadastro->senha, PASSWORD_DEFAULT),
            codigoIndicacaoUsado: Indicacao::codigoCapturado(),
        );
        Indicacao::limpar();

        return $usuario;
    }

    /**
     * Cadastro do profissional no fim do questionário: só e-mail e senha.
     * O nome começa como a parte do e-mail antes do @ e pode ser trocado depois.
     * @throws ValidacaoException
     */
    public function cadastrarProfissional(array $dados, string $idioma, ?string $pais): Usuario
    {
        Validador::validar($dados, [
            'email' => 'obrigatorio|email|max:190',
            'senha' => 'obrigatorio|min:8|max:72',
        ]);

        $email = mb_strtolower(trim($dados['email']));
        if ($this->usuarios->emailExiste($email)) {
            throw new ValidacaoException(['email' => t('cadastro.email_existe')]);
        }

        $apelido = preg_replace('/[._\-+\d]+/', ' ', strstr($email, '@', true));
        $nome = mb_convert_case(trim($apelido) ?: 'Dev', MB_CASE_TITLE);

        $usuario = $this->usuarios->criar(
            TipoUsuario::Profissional,
            mb_substr($nome, 0, 120),
            $email,
            password_hash((string) $dados['senha'], PASSWORD_DEFAULT),
            null,
            $idioma,
            $pais,
            Indicacao::codigoCapturado(),
        );
        Indicacao::limpar();

        return $usuario;
    }

    public function emailDisponivel(string $email): bool
    {
        return !$this->usuarios->emailExiste(mb_strtolower(trim($email)));
    }

    /** Contas criadas antes do programa de indicação não tinham código; gera na primeira vez que precisar. */
    public function garantirCodigoIndicacao(Usuario $usuario): string
    {
        return $this->usuarios->garantirCodigoIndicacao($usuario->id);
    }

    public function buscarPorId(int $id): Usuario
    {
        return $this->usuarios->buscarPorId($id) ?? throw new NaoEncontradoException();
    }

    /** @return Usuario[] */
    public function administradores(): array
    {
        return $this->usuarios->listarAdmins();
    }

    /**
     * Dá permissão de administrador à conta com esse e-mail (ela precisa já ter se cadastrado).
     * @throws ValidacaoException
     */
    public function promoverAAdmin(string $email): Usuario
    {
        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));
        if ($usuario === null) {
            throw new ValidacaoException(['email' => 'Nenhum cadastro com esse e-mail. A pessoa precisa criar a conta primeiro.']);
        }
        if ($usuario->ehAdmin()) {
            throw new ValidacaoException(['email' => 'Essa conta já é administradora.']);
        }

        $this->usuarios->promoverAAdmin($usuario->id);
        return $usuario;
    }

    /**
     * Exclui o cadastro e tudo que depende dele (perfil, candidaturas, assinaturas), inclusive o currículo.
     * @throws ValidacaoException
     */
    public function excluir(int $id, int $quemExclui): void
    {
        $usuario = $this->buscarPorId($id);

        if ($usuario->id === $quemExclui) {
            throw new ValidacaoException(['usuario' => 'Você não pode excluir a própria conta enquanto está logado nela.']);
        }
        if ($usuario->ehAdmin() && $this->usuarios->contarAdmins() <= 1) {
            throw new ValidacaoException(['usuario' => 'Este é o único administrador. Promova outra conta antes de excluí-lo.']);
        }

        $perfil = $this->perfilDe($usuario);
        $this->usuarios->excluir($usuario->id);
        if ($perfil instanceof PerfilProfissional) {
            $this->curriculos->remover($perfil->curriculoArquivo);
        }
    }

    public function perfilDe(Usuario $usuario): PerfilProfissional|PerfilEmpresa|null
    {
        return match ($usuario->tipo) {
            TipoUsuario::Profissional => $this->perfisProfissionais->buscarPorUsuario($usuario->id),
            TipoUsuario::Empresa => $this->perfisEmpresas->buscarPorUsuario($usuario->id),
            TipoUsuario::Admin => null,
        };
    }

    /** @throws ValidacaoException */
    public function salvarPerfilProfissional(Usuario $usuario, array $dados, ?array $upload): void
    {
        Validador::validar($dados, PerfilProfissionalDTO::regras());
        $perfil = PerfilProfissionalDTO::deArray($dados);

        if ($perfil->stack === []) {
            throw new ValidacaoException(['stack' => t('perfil.stack_vazia')]);
        }

        $anterior = $this->perfisProfissionais->buscarPorUsuario($usuario->id);
        $novoCurriculo = $upload !== null ? $this->curriculos->armazenar($upload) : null;

        try {
            $this->transacao->executar(function () use ($usuario, $perfil, $novoCurriculo): void {
                $this->perfisProfissionais->salvar(
                    $usuario->id,
                    $perfil,
                    $novoCurriculo['arquivo'] ?? null,
                    $novoCurriculo['nomeOriginal'] ?? null,
                );
                $this->usuarios->atualizarTelefone($usuario->id, $perfil->telefone);
                $this->usuarios->marcarCadastroCompleto($usuario->id);
            });
        } catch (Throwable $erro) {
            $this->curriculos->remover($novoCurriculo['arquivo'] ?? null);
            throw $erro;
        }

        if ($novoCurriculo !== null) {
            $this->curriculos->remover($anterior?->curriculoArquivo);
        }
    }

    /** @throws ValidacaoException */
    public function salvarPerfilEmpresa(Usuario $usuario, array $dados): void
    {
        Validador::validar($dados, PerfilEmpresaDTO::regras());
        $perfil = PerfilEmpresaDTO::deArray($dados);

        if ($perfil->stacksContratacao === []) {
            throw new ValidacaoException(['stacks_contratacao' => t('perfil.stack_vazia')]);
        }

        $this->transacao->executar(function () use ($usuario, $perfil): void {
            $this->perfisEmpresas->salvar($usuario->id, $perfil);
            $this->usuarios->atualizarTelefone($usuario->id, $perfil->telefone);
            $this->usuarios->marcarCadastroCompleto($usuario->id);
        });
    }

    public function caminhoCurriculo(Usuario $usuario): array
    {
        $perfil = $this->perfisProfissionais->buscarPorUsuario($usuario->id);
        if ($perfil === null || !$perfil->possuiCurriculo()) {
            throw new NaoEncontradoException();
        }

        return [
            'caminho' => $this->curriculos->caminho($perfil->curriculoArquivo),
            'nome' => $perfil->curriculoNomeOriginal ?? 'curriculo.pdf',
        ];
    }

    /** @return array{itens: array, total: int} */
    public function listar(FiltroUsuariosDTO $filtro): array
    {
        return $this->usuarios->listar($filtro);
    }

    public function listarTodos(FiltroUsuariosDTO $filtro): array
    {
        return $this->usuarios->listarTodos($filtro);
    }

    public function estatisticas(): array
    {
        return $this->usuarios->estatisticas();
    }
}
