<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/** Vagas do Remote OK (remotas, com faixa salarial em dólar). Sem chave; o site pede para citar a origem. */
final class FonteRemoteOk implements FonteDeVagas
{
    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function chave(): string
    {
        return 'remoteok';
    }

    public function rotulo(): string
    {
        return 'Remote OK';
    }

    public function site(): ?string
    {
        return 'https://remoteok.com';
    }

    public function buscar(): iterable
    {
        try {
            $lista = $this->http->get('https://remoteok.com/api', [], 40);
        } catch (RuntimeException) {
            return;
        }

        foreach ($lista as $vaga) {
            // O primeiro item do feed é um aviso legal, sem vaga.
            if (!is_array($vaga) || empty($vaga['position'])) {
                continue;
            }

            yield [
                'titulo' => (string) $vaga['position'],
                'empresa' => (string) ($vaga['company'] ?? ''),
                'descricao' => (string) ($vaga['description'] ?? ''),
                'link' => (string) ($vaga['url'] ?? ''),
                'publicadoEm' => (int) ($vaga['epoch'] ?? time()),
                'local' => (string) ($vaga['location'] ?? '') ?: null,
                'remoto' => true,
                'brasil' => false,
                'moeda' => 'USD',
                'salMin' => ((int) ($vaga['salary_min'] ?? 0)) ?: null,
                'salMax' => ((int) ($vaga['salary_max'] ?? 0)) ?: null,
                'nivel' => null,
                'tags' => array_map('strval', (array) ($vaga['tags'] ?? [])),
            ];
        }
    }
}
