<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\VagaDTO;
use App\Entities\Vaga;
use App\Interfaces\VagaRepositoryInterface;
use PDO;

final class VagaRepository implements VagaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function criar(VagaDTO $dados, ?string $criadoEm = null): int
    {
        $this->pdo->prepare(
            'INSERT INTO vagas
                (titulo, empresa, area, senioridade, stack, modelo_trabalho, regiao, cidade, salario_min, salario_max, moeda, link, descricao, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))'
        )->execute([
            $dados->titulo,
            $dados->empresa,
            $dados->area->value,
            $dados->senioridade->value,
            json_encode($dados->stack, JSON_UNESCAPED_UNICODE),
            $dados->modeloTrabalho->value,
            $dados->regiao->value,
            $dados->cidade,
            $dados->salarioMin,
            $dados->salarioMax,
            $dados->moeda->value,
            $dados->link,
            $dados->descricao,
            $criadoEm,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function existeSemelhante(string $titulo, string $empresa, int $dias): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM vagas WHERE titulo = ? AND empresa = ? AND criado_em >= NOW() - INTERVAL ? DAY LIMIT 1');
        $consulta->execute([$titulo, $empresa, $dias]);
        return (bool) $consulta->fetchColumn();
    }

    public function existeLink(string $link): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM vagas WHERE link = ? LIMIT 1');
        $consulta->execute([$link]);
        return (bool) $consulta->fetchColumn();
    }

    public function alternarAtiva(int $id): void
    {
        $this->pdo->prepare('UPDATE vagas SET ativa = 1 - ativa WHERE id = ?')->execute([$id]);
    }

    public function listar(int $limite = 200): array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM vagas ORDER BY criado_em DESC, id DESC LIMIT ?');
        $consulta->bindValue(1, $limite, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(Vaga::deLinha(...), $consulta->fetchAll());
    }

    public function listarAtivas(int $diasRecentes, int $limite = 2000): array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM vagas WHERE ativa = 1 AND criado_em >= NOW() - INTERVAL ? DAY ORDER BY criado_em DESC LIMIT ?');
        $consulta->bindValue(1, $diasRecentes, PDO::PARAM_INT);
        $consulta->bindValue(2, $limite, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(Vaga::deLinha(...), $consulta->fetchAll());
    }

    public function buscarCandidatas(array $areas, array $senioridades, array $modelos, array $regioes, int $diasRecentes): array
    {
        if ($areas === [] || $senioridades === [] || $modelos === [] || $regioes === []) {
            return [];
        }

        $marcadores = fn (array $lista): string => implode(',', array_fill(0, count($lista), '?'));

        $consulta = $this->pdo->prepare(sprintf(
            'SELECT * FROM vagas
             WHERE ativa = 1 AND area IN (%s) AND senioridade IN (%s) AND modelo_trabalho IN (%s) AND regiao IN (%s)
               AND criado_em >= NOW() - INTERVAL ? DAY
             ORDER BY criado_em DESC
             LIMIT 500',
            $marcadores($areas),
            $marcadores($senioridades),
            $marcadores($modelos),
            $marcadores($regioes),
        ));
        $consulta->execute([...$areas, ...$senioridades, ...$modelos, ...$regioes, $diasRecentes]);

        return array_map(Vaga::deLinha(...), $consulta->fetchAll());
    }
}
