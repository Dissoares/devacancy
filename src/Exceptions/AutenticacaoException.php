<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Falha de login ou de autenticação social, com mensagem segura para exibir ao usuário. */
final class AutenticacaoException extends RuntimeException
{
}
