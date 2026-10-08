<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\PerfilProfissionalDTO;
use App\Entities\PerfilProfissional;

interface PerfilProfissionalRepositoryInterface
{
    public function buscarPorUsuario(int $usuarioId): ?PerfilProfissional;

    /** Cria ou atualiza o perfil. Currículo nulo mantém o arquivo atual. */
    public function salvar(int $usuarioId, PerfilProfissionalDTO $dados, ?string $curriculoArquivo, ?string $curriculoNomeOriginal): void;
}
