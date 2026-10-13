<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/**
 * Painéis de vagas públicos de empresas que usam o Lever (a lista de empresas é configurada no painel).
 * Cada empresa tem seu endereço: api.lever.co/v0/postings/{empresa}. Sem chave.
 */
final class FonteLever implements FonteDeVagas
{
    /** @param string[] $empresas identificadores dos painéis (ex.: "spotify") */
    public function __construct(private readonly ClienteHttp $http, private readonly array $empresas)
    {
    }

    public function chave(): string
    {
        return 'lever';
    }

    public function rotulo(): string
    {
        return 'Lever';
    }

    public function site(): ?string
    {
        return null;
    }

    public function buscar(): iterable
    {
        foreach ($this->empresas as $empresa) {
            try {
                $lista = $this->http->get("https://api.lever.co/v0/postings/{$empresa}?mode=json", [], 60);
            } catch (RuntimeException) {
                continue;
            }

            foreach ($lista as $vaga) {
                if (!is_array($vaga)) {
                    continue;
                }
                $categorias = (array) ($vaga['categories'] ?? []);
                $local = trim((string) ($categorias['location'] ?? ''));
                $modelo = strtolower((string) ($vaga['workplaceType'] ?? ''));
                yield [
                    'titulo' => (string) ($vaga['text'] ?? ''),
                    'empresa' => ucfirst($empresa),
                    'descricao' => (string) ($vaga['descriptionPlain'] ?? ''),
                    'link' => (string) ($vaga['hostedUrl'] ?? ''),
                    'publicadoEm' => (int) round(((int) ($vaga['createdAt'] ?? 0)) / 1000) ?: time(),
                    'local' => $local !== '' ? $local : null,
                    'remoto' => $modelo === 'remote' || (bool) preg_match('/remot/i', $local),
                    'hibrido' => $modelo === 'hybrid',
                    'brasil' => ($vaga['country'] ?? '') === 'BR' || (bool) preg_match('/brazil|brasil|são paulo|sao paulo/i', $local),
                    'moeda' => 'USD',
                    'salMin' => null,
                    'salMax' => null,
                    'nivel' => (string) ($categorias['commitment'] ?? ''),
                    'tags' => array_filter([(string) ($categorias['team'] ?? '')]),
                ];
            }
        }
    }
}
