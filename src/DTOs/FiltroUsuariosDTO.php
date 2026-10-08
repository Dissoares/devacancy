<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Core\Requisicao;
use App\Enums\AreaAtuacao;
use App\Enums\Senioridade;
use App\Enums\TipoUsuario;

final class FiltroUsuariosDTO
{
    public const POR_PAGINA = 15;

    public function __construct(
        public readonly ?string $busca = null,
        public readonly ?TipoUsuario $tipo = null,
        public readonly ?Senioridade $senioridade = null,
        public readonly ?AreaAtuacao $area = null,
        public readonly int $pagina = 1,
    ) {
    }

    public static function deRequisicao(Requisicao $requisicao): self
    {
        $busca = trim((string) $requisicao->consulta('busca', ''));

        return new self(
            busca: $busca === '' ? null : mb_substr($busca, 0, 100),
            tipo: TipoUsuario::tryFrom((string) $requisicao->consulta('tipo', '')),
            senioridade: Senioridade::tryFrom((string) $requisicao->consulta('senioridade', '')),
            area: AreaAtuacao::tryFrom((string) $requisicao->consulta('area', '')),
            pagina: max(1, (int) $requisicao->consulta('pagina', 1)),
        );
    }

    public function deslocamento(): int
    {
        return ($this->pagina - 1) * self::POR_PAGINA;
    }

    /** Parâmetros atuais para montar links de paginação e exportação. */
    public function paraConsulta(array $sobrescrever = []): string
    {
        return http_build_query(array_filter([
            'busca' => $this->busca,
            'tipo' => $this->tipo?->value,
            'senioridade' => $this->senioridade?->value,
            'area' => $this->area?->value,
            'pagina' => $this->pagina > 1 ? $this->pagina : null,
            ...$sobrescrever,
        ], fn ($valor) => $valor !== null && $valor !== ''));
    }
}
