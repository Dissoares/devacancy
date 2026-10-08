<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\PerfilEmpresaDTO;
use App\Entities\PerfilEmpresa;

interface PerfilEmpresaRepositoryInterface
{
    public function buscarPorUsuario(int $usuarioId): ?PerfilEmpresa;

    public function salvar(int $usuarioId, PerfilEmpresaDTO $dados): void;
}
