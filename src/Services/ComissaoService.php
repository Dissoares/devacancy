<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Usuario;
use App\Interfaces\ComissaoRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;

/** Indicação: 40% do primeiro pagamento do amigo indicado, uma única vez (sem recorrência). */
final class ComissaoService
{
    private const PERCENTUAL = 0.40;

    public function __construct(
        private readonly ComissaoRepositoryInterface $comissoes,
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {
    }

    /** Chamado logo depois que um pagamento vira "pago". Só gera comissão se o pagador foi indicado por alguém. */
    public function registrarSeElegivel(int $usuarioPagadorId, int $pagamentoId, float $valorPago, string $moeda): void
    {
        $indicadorId = $this->usuarios->buscarPorId($usuarioPagadorId)?->indicadoPor;
        if ($indicadorId === null) {
            return;
        }

        $comissao = round($valorPago * self::PERCENTUAL, 2);
        $this->comissoes->criarSeNova($indicadorId, $usuarioPagadorId, $pagamentoId, $comissao, $moeda);
    }

    public function linkDeIndicacao(string $codigoIndicacao): string
    {
        return rtrim(endereco_do_site(), '/') . '/?ref=' . $codigoIndicacao;
    }

    /** @return array{itens: array, totais: array<string, array{pendente: float, paga: float}>} */
    public function painelDoUsuario(Usuario $usuario): array
    {
        return [
            'itens' => $this->comissoes->listarDoIndicador($usuario->id),
            'totais' => $this->comissoes->totaisDoIndicador($usuario->id),
        ];
    }

    public function listarParaAdmin(): array
    {
        return $this->comissoes->listarTodas();
    }

    public function marcarComoPaga(int $id): bool
    {
        return $this->comissoes->marcarComoPaga($id);
    }
}
