<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TipoUsuario;

final class CadastroUsuarioDTO
{
    public function __construct(
        public readonly TipoUsuario $tipo,
        public readonly string $nome,
        public readonly string $email,
        public readonly string $senha,
    ) {
    }

    public static function regras(): array
    {
        return [
            'tipo' => 'obrigatorio|enum:' . TipoUsuario::class,
            'nome' => 'obrigatorio|min:3|max:120',
            'email' => 'obrigatorio|email|max:190',
            'senha' => 'obrigatorio|min:8|max:72',
            'termos' => 'aceito',
        ];
    }

    public static function deArray(array $dados): self
    {
        return new self(
            tipo: TipoUsuario::from($dados['tipo']),
            nome: preg_replace('/\s+/', ' ', trim($dados['nome'])),
            email: mb_strtolower(trim($dados['email'])),
            senha: (string) $dados['senha'],
        );
    }
}
