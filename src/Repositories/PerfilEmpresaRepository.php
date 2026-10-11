<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\PerfilEmpresaDTO;
use App\Entities\PerfilEmpresa;
use App\Interfaces\PerfilEmpresaRepositoryInterface;
use PDO;

final class PerfilEmpresaRepository implements PerfilEmpresaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorUsuario(int $usuarioId): ?PerfilEmpresa
    {
        $consulta = $this->pdo->prepare('SELECT * FROM perfis_empresas WHERE usuario_id = ?');
        $consulta->execute([$usuarioId]);
        $linha = $consulta->fetch();
        return $linha ? PerfilEmpresa::deLinha($linha) : null;
    }

    public function salvar(int $usuarioId, PerfilEmpresaDTO $dados): void
    {
        $this->pdo->prepare(
            'INSERT INTO perfis_empresas
                (usuario_id, nome_empresa, site, tamanho, cargo_contato, stacks_contratacao, vagas_abertas, mensagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                nome_empresa = VALUES(nome_empresa), site = VALUES(site), tamanho = VALUES(tamanho),
                cargo_contato = VALUES(cargo_contato), stacks_contratacao = VALUES(stacks_contratacao),
                vagas_abertas = VALUES(vagas_abertas), mensagem = VALUES(mensagem)'
        )->execute([
            $usuarioId,
            $dados->nomeEmpresa,
            $dados->site,
            $dados->tamanho->value,
            $dados->cargoContato,
            json_encode($dados->stacksContratacao, JSON_UNESCAPED_UNICODE),
            $dados->vagasAbertas,
            $dados->mensagem,
        ]);
    }
}
