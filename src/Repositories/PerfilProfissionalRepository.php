<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\PerfilProfissionalDTO;
use App\Entities\PerfilProfissional;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use PDO;

final class PerfilProfissionalRepository implements PerfilProfissionalRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorUsuario(int $usuarioId): ?PerfilProfissional
    {
        $consulta = $this->pdo->prepare('SELECT * FROM perfis_profissionais WHERE usuario_id = ?');
        $consulta->execute([$usuarioId]);
        $linha = $consulta->fetch();
        return $linha ? PerfilProfissional::deLinha($linha) : null;
    }

    public function salvar(int $usuarioId, PerfilProfissionalDTO $dados, ?string $curriculoArquivo, ?string $curriculoNomeOriginal): void
    {
        $this->pdo->prepare(
            'INSERT INTO perfis_profissionais
                (usuario_id, area, senioridade, cargo_desejado, anos_experiencia, stack, modelo_trabalho, cidade,
                 pretensao_salarial, linkedin_url, github_url, portfolio_url, curriculo_arquivo, curriculo_nome_original, resumo,
                 regiao_vagas, moeda_pretensao, idiomas, ultima_remuneracao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS novo
             ON DUPLICATE KEY UPDATE
                area = novo.area, senioridade = novo.senioridade, cargo_desejado = novo.cargo_desejado,
                anos_experiencia = novo.anos_experiencia, stack = novo.stack, modelo_trabalho = novo.modelo_trabalho,
                cidade = novo.cidade, pretensao_salarial = novo.pretensao_salarial, linkedin_url = novo.linkedin_url,
                github_url = novo.github_url, portfolio_url = novo.portfolio_url, resumo = novo.resumo,
                regiao_vagas = novo.regiao_vagas, moeda_pretensao = novo.moeda_pretensao,
                idiomas = COALESCE(novo.idiomas, perfis_profissionais.idiomas),
                ultima_remuneracao = COALESCE(novo.ultima_remuneracao, perfis_profissionais.ultima_remuneracao),
                curriculo_arquivo = COALESCE(novo.curriculo_arquivo, perfis_profissionais.curriculo_arquivo),
                curriculo_nome_original = COALESCE(novo.curriculo_nome_original, perfis_profissionais.curriculo_nome_original)'
        )->execute([
            $usuarioId,
            $dados->area->value,
            $dados->senioridade->value,
            $dados->cargoDesejado,
            $dados->anosExperiencia,
            json_encode($dados->stack, JSON_UNESCAPED_UNICODE),
            $dados->modeloTrabalho->value,
            $dados->cidade,
            $dados->pretensaoSalarial,
            $dados->linkedinUrl,
            $dados->githubUrl,
            $dados->portfolioUrl,
            $curriculoArquivo,
            $curriculoNomeOriginal,
            $dados->resumo,
            $dados->regiaoVagas->value,
            $dados->moedaPretensao,
            $dados->idiomas !== [] ? json_encode($dados->idiomas) : null,
            $dados->ultimaRemuneracao,
        ]);
    }
}
