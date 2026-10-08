<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ValidacaoException extends RuntimeException
{
    /** @param array<string, string> $erros campo => mensagem */
    public function __construct(private readonly array $erros)
    {
        parent::__construct('Dados inválidos.');
    }

    public function erros(): array
    {
        return $this->erros;
    }
}
