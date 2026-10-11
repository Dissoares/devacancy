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
                 regiao_vagas, moeda_pretensao, idiomas, ultima_remuneracao, nivel_ingles)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                area = VALUES(area), senioridade = VALUES(senioridade), cargo_desejado = VALUES(cargo_desejado),
                anos_experiencia = VALUES(anos_experiencia), stack = VALUES(stack), modelo_trabalho = VALUES(modelo_trabalho),
                cidade = VALUES(cidade), pretensao_salarial = VALUES(pretensao_salarial), linkedin_url = VALUES(linkedin_url),
                github_url = VALUES(github_url), portfolio_url = VALUES(portfolio_url), resumo = VALUES(resumo),
                regiao_vagas = VALUES(regiao_vagas), moeda_pretensao = VALUES(moeda_pretensao),
                idiomas = COALESCE(VALUES(idiomas), perfis_profissionais.idiomas),
                ultima_remuneracao = COALESCE(VALUES(ultima_remuneracao), perfis_profissionais.ultima_remuneracao),
                nivel_ingles = COALESCE(VALUES(nivel_ingles), perfis_profissionais.nivel_ingles),
                curriculo_arquivo = COALESCE(VALUES(curriculo_arquivo), perfis_profissionais.curriculo_arquivo),
                curriculo_nome_original = COALESCE(VALUES(curriculo_nome_original), perfis_profissionais.curriculo_nome_original)'
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
            $dados->nivelIngles?->value,
        ]);
    }
}
