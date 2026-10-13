<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/** Vagas remotas do Jobicy. Sem chave; o site pede no máximo uma rodada por hora. */
final class FonteJobicy implements FonteDeVagas
{
    private const MARCADORES = ['developer', 'engineer', 'devops', 'data', 'python', 'javascript'];

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function chave(): string
    {
        return 'jobicy';
    }

    public function rotulo(): string
    {
        return 'Jobicy';
    }

    public function site(): ?string
    {
        return 'https://jobicy.com';
    }

    public function buscar(): iterable
    {
        $vistas = [];
        foreach (self::MARCADORES as $marcador) {
            try {
                $resposta = $this->http->get('https://jobicy.com/api/v2/remote-jobs?count=100&tag=' . $marcador, [], 40);
            } catch (RuntimeException) {
                continue;
            }

            foreach ($resposta['jobs'] ?? [] as $vaga) {
                $id = (string) ($vaga['id'] ?? '');
                if ($id === '' || isset($vistas[$id])) {
                    continue;
                }
                $vistas[$id] = true;

                $local = $vaga['jobGeo'] ?? null;
                $nivel = $vaga['jobLevel'] ?? '';
                yield [
                    'titulo' => html_entity_decode((string) ($vaga['jobTitle'] ?? ''), ENT_QUOTES | ENT_HTML5),
                    'empresa' => html_entity_decode((string) ($vaga['companyName'] ?? ''), ENT_QUOTES | ENT_HTML5),
                    'descricao' => (string) ($vaga['jobDescription'] ?? $vaga['jobExcerpt'] ?? ''),
                    'link' => (string) ($vaga['url'] ?? ''),
                    'publicadoEm' => strtotime((string) ($vaga['pubDate'] ?? '')) ?: time(),
                    'local' => (is_array($local) ? implode(', ', $local) : (string) $local) ?: null,
                    'remoto' => true,
                    'brasil' => false,
                    'moeda' => 'USD',
                    'salMin' => null,
                    'salMax' => null,
                    'nivel' => is_array($nivel) ? implode(' ', $nivel) : (string) $nivel,
                    'tags' => [],
                ];
            }
        }
    }
}
