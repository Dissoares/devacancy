<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/**
 * Painéis de vagas públicos de empresas que usam o Greenhouse (a lista de empresas é configurada no painel).
 * Cada empresa tem seu endereço: boards-api.greenhouse.io/v1/boards/{empresa}/jobs. Sem chave.
 */
final class FonteGreenhouse implements FonteDeVagas
{
    /** @param string[] $empresas identificadores dos painéis (ex.: "quintoandar") */
    public function __construct(private readonly ClienteHttp $http, private readonly array $empresas)
    {
    }

    public function chave(): string
    {
        return 'greenhouse';
    }

    public function rotulo(): string
    {
        return 'Greenhouse';
    }

    public function site(): ?string
    {
        return null;
    }

    public function buscar(): iterable
    {
        foreach ($this->empresas as $empresa) {
            try {
                $resposta = $this->http->get("https://boards-api.greenhouse.io/v1/boards/{$empresa}/jobs?content=true", [], 60);
            } catch (RuntimeException) {
                continue;
            }

            foreach ($resposta['jobs'] ?? [] as $vaga) {
                $local = trim((string) ($vaga['location']['name'] ?? ''));
                $texto = html_entity_decode((string) ($vaga['content'] ?? ''), ENT_QUOTES | ENT_HTML5);
                yield [
                    'titulo' => (string) ($vaga['title'] ?? ''),
                    'empresa' => (string) ($vaga['company_name'] ?? '') ?: ucfirst($empresa),
                    'descricao' => $texto,
                    'link' => (string) ($vaga['absolute_url'] ?? ''),
                    'publicadoEm' => strtotime((string) ($vaga['first_published'] ?? $vaga['updated_at'] ?? '')) ?: time(),
                    'local' => $local !== '' ? $local : null,
                    'remoto' => (bool) preg_match('/remot/i', $local . ' ' . (string) ($vaga['title'] ?? '')),
                    'brasil' => (bool) preg_match('/brazil|brasil|são paulo|sao paulo|rio de janeiro|belo horizonte|curitiba|porto alegre|florian[oó]polis|recife/i', $local),
                    'moeda' => 'USD',
                    'salMin' => null,
                    'salMax' => null,
                    'nivel' => null,
                    'tags' => [],
                ];
            }
        }
    }
}
