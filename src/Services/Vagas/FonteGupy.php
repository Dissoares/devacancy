<?php

declare(strict_types=1);

namespace App\Services\Vagas;

use App\Core\ClienteHttp;
use RuntimeException;

/** Vagas do portal público do Gupy (a plataforma de recrutamento mais usada no Brasil). Sem chave. */
final class FonteGupy implements FonteDeVagas
{
    private const URL = 'https://portal.gupy.io/api/job-search/jobs';
    private const POR_PAGINA = 100;
    private const PAGINAS_POR_TERMO = 4;

    private const TERMOS = [
        'desenvolvedor', 'programador', 'engenheiro de software', 'analista de sistemas', 'front end', 'back end',
        'full stack', 'devops', 'engenheiro de dados', 'cientista de dados', 'desenvolvedor mobile', 'qa testes',
        'desenvolvedor java', 'desenvolvedor python', 'desenvolvedor .net', 'desenvolvedor php', 'desenvolvedor react',
        'desenvolvedor node', 'desenvolvedor android', 'desenvolvedor ios', 'analista de dados', 'analista de qualidade',
        'arquiteto de software', 'tech lead', 'sre', 'analista de bi', 'dba', 'analista de suporte tecnologia',
    ];

    private const ESTADOS = [
        'Acre' => 'AC', 'Alagoas' => 'AL', 'Amapá' => 'AP', 'Amazonas' => 'AM', 'Bahia' => 'BA', 'Ceará' => 'CE',
        'Distrito Federal' => 'DF', 'Espírito Santo' => 'ES', 'Goiás' => 'GO', 'Maranhão' => 'MA', 'Mato Grosso' => 'MT',
        'Mato Grosso do Sul' => 'MS', 'Minas Gerais' => 'MG', 'Pará' => 'PA', 'Paraíba' => 'PB', 'Paraná' => 'PR',
        'Pernambuco' => 'PE', 'Piauí' => 'PI', 'Rio de Janeiro' => 'RJ', 'Rio Grande do Norte' => 'RN',
        'Rio Grande do Sul' => 'RS', 'Rondônia' => 'RO', 'Roraima' => 'RR', 'Santa Catarina' => 'SC',
        'São Paulo' => 'SP', 'Sergipe' => 'SE', 'Tocantins' => 'TO',
    ];

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    public function chave(): string
    {
        return 'gupy';
    }

    public function rotulo(): string
    {
        return 'Gupy';
    }

    public function site(): ?string
    {
        return null;
    }

    public function buscar(): iterable
    {
        $vistas = [];
        foreach (self::TERMOS as $termo) {
            for ($pagina = 0; $pagina < self::PAGINAS_POR_TERMO; $pagina++) {
                try {
                    $resposta = $this->http->get(self::URL . '?' . http_build_query([
                        'jobName' => $termo, 'limit' => self::POR_PAGINA, 'offset' => $pagina * self::POR_PAGINA,
                    ]), [], 30);
                } catch (RuntimeException) {
                    break;
                }

                $vagas = $resposta['data'] ?? [];
                foreach ($vagas as $vaga) {
                    $id = (string) ($vaga['id'] ?? '');
                    if ($id === '' || isset($vistas[$id])) {
                        continue;
                    }
                    $vistas[$id] = true;

                    $modelo = (string) ($vaga['workplaceType'] ?? '');
                    $estado = (string) ($vaga['state'] ?? '');
                    $uf = self::ESTADOS[$estado] ?? $estado;
                    $cidade = trim((string) ($vaga['city'] ?? ''));

                    yield [
                        'titulo' => (string) ($vaga['name'] ?? ''),
                        'empresa' => (string) ($vaga['careerPageName'] ?? ''),
                        'descricao' => (string) ($vaga['description'] ?? ''),
                        'link' => (string) ($vaga['jobUrl'] ?? ''),
                        'publicadoEm' => strtotime((string) ($vaga['publishedDate'] ?? '')) ?: time(),
                        'local' => $modelo === 'remote' ? null : ($cidade !== '' ? $cidade . ($uf !== '' ? ", {$uf}" : '') : null),
                        'remoto' => $modelo === 'remote',
                        'hibrido' => $modelo === 'hybrid',
                        'brasil' => true,
                        'moeda' => 'BRL',
                        'salMin' => null,
                        'salMax' => null,
                        'nivel' => null,
                        'tags' => [],
                    ];
                }

                if (count($vagas) < self::POR_PAGINA) {
                    break;
                }
                usleep(250_000);
            }
        }
    }
}
