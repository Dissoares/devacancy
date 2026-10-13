<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;

final class Vaga
{
    /** @param string[] $stack */
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $empresa,
        public readonly string $fonte,
        public readonly AreaAtuacao $area,
        public readonly Senioridade $senioridade,
        public readonly array $stack,
        public readonly ModeloTrabalho $modeloTrabalho,
        public readonly ?string $cidade,
        public readonly ?int $salarioMin,
        public readonly ?int $salarioMax,
        public readonly ?string $link,
        public readonly ?string $descricao,
        public readonly bool $ativa,
        public readonly string $criadoEm,
        public readonly RegiaoVagas $regiao = RegiaoVagas::Brasil,
        public readonly string $moeda = 'BRL',
    ) {
    }

    public static function deLinha(array $linha): self
    {
        return new self(
            id: (int) $linha['id'],
            titulo: $linha['titulo'],
            empresa: $linha['empresa'],
            fonte: $linha['fonte'] ?? 'manual',
            area: AreaAtuacao::from($linha['area']),
            senioridade: Senioridade::from($linha['senioridade']),
            stack: json_decode($linha['stack'] ?: '[]', true) ?: [],
            modeloTrabalho: ModeloTrabalho::from($linha['modelo_trabalho']),
            cidade: $linha['cidade'],
            salarioMin: $linha['salario_min'] !== null ? (int) $linha['salario_min'] : null,
            salarioMax: $linha['salario_max'] !== null ? (int) $linha['salario_max'] : null,
            link: $linha['link'],
            descricao: $linha['descricao'],
            ativa: (bool) $linha['ativa'],
            criadoEm: $linha['criado_em'],
            regiao: RegiaoVagas::tryFrom($linha['regiao'] ?? '') ?? RegiaoVagas::Brasil,
            moeda: $linha['moeda'] ?? 'BRL',
        );
    }

    public function faixaSalarial(): ?string
    {
        $formatar = fn (int $valor): string => moeda($valor, $this->moeda, 0);

        return match (true) {
            $this->salarioMin && $this->salarioMax => $formatar($this->salarioMin) . ' – ' . $formatar($this->salarioMax),
            (bool) $this->salarioMin => t('vaga.a_partir_de', ['valor' => $formatar($this->salarioMin)]),
            (bool) $this->salarioMax => t('vaga.ate', ['valor' => $formatar($this->salarioMax)]),
            default => null,
        };
    }

    /** Quem publicou a vaga originalmente, para o crédito "Fonte" (a empresa continua oculta). @return array{rotulo: string, site: ?string}|null */
    public function origem(): ?array
    {
        return match ($this->fonte) {
            'gupy' => ['rotulo' => 'Gupy', 'site' => null],
            'arbeitnow' => ['rotulo' => 'Arbeitnow', 'site' => 'https://www.arbeitnow.com'],
            'jobicy' => ['rotulo' => 'Jobicy', 'site' => 'https://jobicy.com'],
            'remoteok' => ['rotulo' => 'Remote OK', 'site' => 'https://remoteok.com'],
            'himalayas' => ['rotulo' => 'Himalayas', 'site' => 'https://himalayas.app'],
            'greenhouse' => ['rotulo' => 'Greenhouse', 'site' => null],
            'lever' => ['rotulo' => 'Lever', 'site' => null],
            'adzuna' => ['rotulo' => 'Adzuna', 'site' => 'https://www.adzuna.com.br'],
            'github' => ['rotulo' => 'GitHub', 'site' => null],
            default => null,
        };
    }

    /** Texto da vaga para o público: a descrição (ou uma gerada) com o nome da empresa coberto. */
    public function descricaoPublica(): string
    {
        $descricao = $this->descricao ?: t('funil.descricao_gerada', [
            'nivel' => mb_strtolower($this->senioridade->rotulo()),
            'area' => $this->area->rotulo(),
            'stack' => implode(', ', array_slice($this->stack, 0, 3)) ?: $this->area->rotulo(),
        ]);
        if ($this->empresa !== 'Confidencial' && mb_strlen($this->empresa) >= 3) {
            $descricao = preg_replace('/' . preg_quote($this->empresa, '/') . '/iu', '▒▒▒▒▒', $descricao);
        }
        return $descricao;
    }

    public function local(): string
    {
        $local = $this->cidade ?? ($this->regiao === RegiaoVagas::Internacional ? t('regiao.internacional') : null);
        return $this->modeloTrabalho->rotulo() . ($local ? ' · ' . $local : '');
    }
}
