<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\TipoUsuario;

final class Usuario
{
    public function __construct(
        public readonly int $id,
        public readonly TipoUsuario $tipo,
        public readonly string $nome,
        public readonly string $email,
        public readonly ?string $senhaHash,
        public readonly ?string $avatarUrl,
        public readonly ?string $telefone,
        public readonly bool $cadastroCompleto,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $criadoEm,
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: (int) $linha['id'],
            tipo: TipoUsuario::from($linha['tipo']),
            nome: $linha['nome'],
            email: $linha['email'],
            senhaHash: $linha['senha_hash'],
            avatarUrl: $linha['avatar_url'],
            telefone: $linha['telefone'],
            cadastroCompleto: (bool) $linha['cadastro_completo'],
            ultimoAcessoEm: $linha['ultimo_acesso_em'],
            criadoEm: $linha['criado_em'],
        );
    }

    public function ehAdmin(): bool
    {
        return $this->tipo === TipoUsuario::Admin;
    }

    public function ehProfissional(): bool
    {
        return $this->tipo === TipoUsuario::Profissional;
    }

    public function ehEmpresa(): bool
    {
        return $this->tipo === TipoUsuario::Empresa;
    }

    public function primeiroNome(): string
    {
        return explode(' ', trim($this->nome))[0];
    }

    /** Dados mínimos guardados na sessão — nunca inclui a senha. */
    public function resumoSessao(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'tipo' => $this->tipo->value,
            'avatar' => $this->avatarUrl,
        ];
    }
}
