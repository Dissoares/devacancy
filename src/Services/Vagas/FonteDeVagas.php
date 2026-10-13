<?php

declare(strict_types=1);

namespace App\Services\Vagas;

/**
 * Uma origem de vagas (API pública de um site). Cada fonte só busca e entrega as vagas num formato comum;
 * filtrar o que é tecnologia, classificar e gravar é trabalho do ImportadorVagasExternas.
 *
 * Cada item devolvido tem: titulo, empresa, descricao (texto ou HTML), link, publicadoEm (timestamp),
 * local (?string), remoto (bool), hibrido (bool, opcional), brasil (bool), moeda ('BRL'|'USD'|'EUR'),
 * salMin (?int), salMax (?int), nivel (?string, texto livre) e tags (string[]).
 */
interface FonteDeVagas
{
    /** Identificador curto, gravado em vagas.fonte (até 20 caracteres). */
    public function chave(): string;

    /** Nome para mostrar ao público como origem da vaga. */
    public function rotulo(): string;

    /** Site de origem, usado no crédito "Fonte" (null quando não há um site geral, como no Gupy). */
    public function site(): ?string;

    /** @return iterable<array<string, mixed>> */
    public function buscar(): iterable;
}
