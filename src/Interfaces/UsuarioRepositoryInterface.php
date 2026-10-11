<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\FiltroUsuariosDTO;
use App\Entities\Usuario;
use App\Enums\ProvedorSocial;
use App\Enums\TipoUsuario;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    public function buscarPorContaSocial(ProvedorSocial $provedor, string $provedorId): ?Usuario;

    public function emailExiste(string $email): bool;

    public function criar(TipoUsuario $tipo, string $nome, string $email, ?string $senhaHash, ?string $avatarUrl = null, string $idioma = 'pt', ?string $pais = null): Usuario;

    public function vincularContaSocial(int $usuarioId, ProvedorSocial $provedor, string $provedorId): void;

    public function atualizarAvatarSeVazio(int $usuarioId, ?string $avatarUrl): void;

    /** Telefone nulo mantém o atual. */
    public function atualizarTelefone(int $usuarioId, ?string $telefone): void;

    public function marcarCadastroCompleto(int $usuarioId): void;

    public function registrarAcesso(int $usuarioId): void;

    /** @return array{itens: array<int, array>, total: int} linhas já unidas com os perfis */
    public function listar(FiltroUsuariosDTO $filtro): array;

    /** @return array<int, array> todas as linhas do filtro, sem paginação (exportação) */
    public function listarTodos(FiltroUsuariosDTO $filtro): array;

    /** @return array{profissionais: int, empresas: int, novos_semana: int, completos: int} */
    public function estatisticas(): array;
}
