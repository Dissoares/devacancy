<?php

declare(strict_types=1);

namespace App\Services;

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

        return $this->usuarios->criar(
            $cadastro->tipo,
            $cadastro->nome,
            $cadastro->email,
            password_hash($cadastro->senha, PASSWORD_DEFAULT),
        );
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

        return $this->usuarios->criar(
            TipoUsuario::Profissional,
            mb_substr($nome, 0, 120),
            $email,
            password_hash((string) $dados['senha'], PASSWORD_DEFAULT),
            null,
            $idioma,
            $pais,
        );
    }

    public function emailDisponivel(string $email): bool
    {
        return !$this->usuarios->emailExiste(mb_strtolower(trim($email)));
    }

    public function buscarPorId(int $id): Usuario
    {
        return $this->usuarios->buscarPorId($id) ?? throw new NaoEncontradoException();
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
