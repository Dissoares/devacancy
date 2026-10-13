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

    /** Grava as listas importadas (null mantém a atual) e marca a data da importação. @param array<int, array>|null $experiencias @param array<int, array>|null $formacao */
    public function salvarImportacao(int $usuarioId, ?array $experiencias, ?array $formacao): void;

    /** Troca o PDF do currículo (o arquivo anterior deve ser apagado por quem chama). */
    public function definirCurriculo(int $usuarioId, string $arquivo, string $nomeOriginal): void;
}
