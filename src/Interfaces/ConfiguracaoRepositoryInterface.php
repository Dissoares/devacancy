<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ConfiguracaoRepositoryInterface
{
    /** @return array<string, string> chave => valor salvos no painel */
    public function todas(): array;

    public function salvar(string $chave, string $valor): void;

    public function remover(string $chave): void;
}
