<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/** Vagas do Arbeitnow (quadro de vagas europeu, com muitas vagas remotas). Sem chave. */
final class FonteArbeitnow implements FonteDeVagas
{
    private const PAGINAS = 3;

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function chave(): string
    {
        return 'arbeitnow';
    }

    public function rotulo(): string
    {
        return 'Arbeitnow';
    }

    public function site(): ?string
    {
        return 'https://www.arbeitnow.com';
    }

    public function buscar(): iterable
    {
        for ($pagina = 1; $pagina <= self::PAGINAS; $pagina++) {
            try {
                $resposta = $this->http->get("https://www.arbeitnow.com/api/job-board-api?page={$pagina}", [], 40);
            } catch (RuntimeException) {
                return;
            }

            foreach ($resposta['data'] ?? [] as $vaga) {
                yield [
                    'titulo' => (string) ($vaga['title'] ?? ''),
                    'empresa' => (string) ($vaga['company_name'] ?? ''),
                    'descricao' => (string) ($vaga['description'] ?? ''),
                    'link' => (string) ($vaga['url'] ?? ''),
                    'publicadoEm' => (int) ($vaga['created_at'] ?? time()),
                    'local' => (string) ($vaga['location'] ?? '') ?: null,
                    'remoto' => (bool) ($vaga['remote'] ?? false),
                    'brasil' => false,
                    'moeda' => 'EUR',
                    'salMin' => null,
                    'salMax' => null,
                    'nivel' => null,
                    'tags' => array_map('strval', (array) ($vaga['tags'] ?? [])),
                ];
            }
        }
    }
}
