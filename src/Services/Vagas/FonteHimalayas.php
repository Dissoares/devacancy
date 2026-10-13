<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/** Vagas remotas do Himalayas. Sem chave; 20 vagas por página; o site pede um link de volta. */
final class FonteHimalayas implements FonteDeVagas
{
    private const PAGINAS = 30;
    private const POR_PAGINA = 20;

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function chave(): string
    {
        return 'himalayas';
    }

    public function rotulo(): string
    {
        return 'Himalayas';
    }

    public function site(): ?string
    {
        return 'https://himalayas.app';
    }

    public function buscar(): iterable
    {
        for ($pagina = 0; $pagina < self::PAGINAS; $pagina++) {
            try {
                $resposta = $this->http->get('https://himalayas.app/jobs/api?limit=' . self::POR_PAGINA . '&offset=' . $pagina * self::POR_PAGINA, [], 30);
            } catch (RuntimeException) {
                return;
            }

            $vagas = $resposta['jobs'] ?? [];
            foreach ($vagas as $vaga) {
                $restricoes = array_map('strval', (array) ($vaga['locationRestrictions'] ?? []));
                // Vaga restrita a alguns países só serve se incluir o Brasil, a América Latina ou o mundo todo.
                if ($restricoes !== [] && !preg_match('/brazil|brasil|latin|latam|americas|worldwide|anywhere|global/i', implode(' ', $restricoes))) {
                    continue;
                }

                $fator = strtolower((string) ($vaga['salaryPeriod'] ?? 'year')) === 'month' ? 12 : 1;
                $nivel = $vaga['seniority'] ?? '';
                $publicado = $vaga['pubDate'] ?? null;
                yield [
                    'titulo' => (string) ($vaga['title'] ?? ''),
                    'empresa' => (string) ($vaga['companyName'] ?? ''),
                    'descricao' => (string) ($vaga['description'] ?? $vaga['excerpt'] ?? ''),
                    'link' => (string) ($vaga['applicationLink'] ?? $vaga['guid'] ?? ''),
                    'publicadoEm' => is_numeric($publicado) ? (int) $publicado : (strtotime((string) $publicado) ?: time()),
                    'local' => $restricoes !== [] ? implode(', ', array_slice($restricoes, 0, 3)) : null,
                    'remoto' => true,
                    'brasil' => false,
                    'moeda' => strtoupper((string) ($vaga['currency'] ?? 'USD')) === 'EUR' ? 'EUR' : 'USD',
                    'salMin' => ((int) ($vaga['minSalary'] ?? 0)) * $fator ?: null,
                    'salMax' => ((int) ($vaga['maxSalary'] ?? 0)) * $fator ?: null,
                    'nivel' => is_array($nivel) ? implode(' ', $nivel) : (string) $nivel,
                    'tags' => array_map('strval', array_merge((array) ($vaga['categories'] ?? []), (array) ($vaga['parentCategories'] ?? []))),
                ];
            }

            if (count($vagas) < self::POR_PAGINA) {
                return;
            }
            usleep(300_000);
        }
    }
}
