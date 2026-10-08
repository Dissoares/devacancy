<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validador;
use App\DTOs\VagaDTO;
use App\Entities\PerfilProfissional;
use App\Entities\Vaga;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\Senioridade;
use App\Exceptions\ValidacaoException;
use App\Interfaces\VagaRepositoryInterface;

/** Cadastro de vagas pela equipe e o match entre vagas e perfis. */
final class VagaService
{
    /** Só entram no match vagas cadastradas nos últimos N dias. */
    private const DIAS_VALIDADE = 30;

    public function __construct(private readonly VagaRepositoryInterface $vagas)
    {
    }

    /** @throws ValidacaoException */
    public function cadastrar(array $dados): int
    {
        Validador::validar($dados, VagaDTO::regras());
        $vaga = VagaDTO::deArray($dados);

        if ($vaga->salarioMin && $vaga->salarioMax && $vaga->salarioMin > $vaga->salarioMax) {
            throw new ValidacaoException(['salario_max' => 'O máximo precisa ser maior que o mínimo.']);
        }

        return $this->vagas->criar($vaga);
    }

    /** @return Vaga[] */
    public function listar(): array
    {
        return $this->vagas->listar();
    }

    public function alternarAtiva(int $id): void
    {
        $this->vagas->alternarAtiva($id);
    }

    /**
     * Vagas compatíveis com o perfil, da mais para a menos compatível.
     * @return array<int, array{vaga: Vaga, compatibilidade: int}>
     */
    public function compativeis(PerfilProfissional $perfil): array
    {
        $candidatas = $this->vagas->buscarCandidatas(
            array_map(fn (AreaAtuacao $area) => $area->value, $this->areasAceitas($perfil->area)),
            array_map(fn (Senioridade $nivel) => $nivel->value, $this->senioridadesAceitas($perfil->senioridade)),
            array_map(fn (ModeloTrabalho $modelo) => $modelo->value, $this->modelosAceitos($perfil->modeloTrabalho)),
            self::DIAS_VALIDADE,
        );

        $stackPerfil = $this->normalizarStack($perfil->stack);
        $resultado = [];

        foreach ($candidatas as $vaga) {
            if (!$this->localCompativel($perfil, $vaga)) {
                continue;
            }

            $stackVaga = $this->normalizarStack($vaga->stack);
            $emComum = count(array_intersect($stackVaga, $stackPerfil));
            if ($stackVaga !== [] && $stackPerfil !== [] && $emComum === 0) {
                continue;
            }

            $resultado[] = [
                'vaga' => $vaga,
                'compatibilidade' => $this->pontuar($perfil, $vaga, $stackVaga === [] ? 1.0 : $emComum / count($stackVaga)),
            ];
        }

        usort($resultado, fn (array $a, array $b) => [$b['compatibilidade'], $b['vaga']->criadoEm] <=> [$a['compatibilidade'], $a['vaga']->criadoEm]);

        return $resultado;
    }

    /** Quantas das vagas compatíveis entraram nas últimas 24 horas. */
    public function contarRecentes(array $compativeis): int
    {
        $limite = time() - 86400;
        return count(array_filter($compativeis, fn (array $item) => strtotime($item['vaga']->criadoEm) >= $limite));
    }

    /** @return AreaAtuacao[] */
    private function areasAceitas(AreaAtuacao $area): array
    {
        return match ($area) {
            AreaAtuacao::Fullstack => [AreaAtuacao::Fullstack, AreaAtuacao::Frontend, AreaAtuacao::Backend],
            AreaAtuacao::Frontend, AreaAtuacao::Backend => [$area, AreaAtuacao::Fullstack],
            default => [$area],
        };
    }

    /** Mesmo nível ou um acima/abaixo. @return Senioridade[] */
    private function senioridadesAceitas(Senioridade $senioridade): array
    {
        $niveis = Senioridade::cases();
        $posicao = array_search($senioridade, $niveis, true);
        return array_slice($niveis, max(0, $posicao - 1), $posicao === 0 ? 2 : 3);
    }

    /** @return ModeloTrabalho[] */
    private function modelosAceitos(ModeloTrabalho $preferencia): array
    {
        return match ($preferencia) {
            ModeloTrabalho::Remoto => [ModeloTrabalho::Remoto],
            ModeloTrabalho::Hibrido => [ModeloTrabalho::Hibrido, ModeloTrabalho::Remoto],
            ModeloTrabalho::Presencial, ModeloTrabalho::Indiferente => [ModeloTrabalho::Presencial, ModeloTrabalho::Hibrido, ModeloTrabalho::Remoto],
        };
    }

    /** Vaga presencial/híbrida só serve se for na cidade da pessoa (quando as duas cidades são conhecidas). */
    private function localCompativel(PerfilProfissional $perfil, Vaga $vaga): bool
    {
        if ($vaga->modeloTrabalho === ModeloTrabalho::Remoto || !$vaga->cidade || !$perfil->cidade) {
            return true;
        }
        return $this->normalizarTexto($vaga->cidade) === $this->normalizarTexto($perfil->cidade);
    }

    private function pontuar(PerfilProfissional $perfil, Vaga $vaga, float $coberturaStack): int
    {
        $pontos = $vaga->area === $perfil->area ? 25 : 15;
        $pontos += $vaga->senioridade === $perfil->senioridade ? 25 : 15;
        $pontos += $perfil->modeloTrabalho === ModeloTrabalho::Indiferente || $vaga->modeloTrabalho === $perfil->modeloTrabalho ? 15 : 10;
        $pontos += (int) round(35 * $coberturaStack);

        return min(99, $pontos);
    }

    /** "Node.js", "NodeJS" e "node" viram a mesma chave. @return string[] */
    private function normalizarStack(array $stack): array
    {
        $chaves = [];
        foreach ($stack as $tecnologia) {
            $chave = preg_replace('/[^a-z0-9+#]/', '', $this->normalizarTexto($tecnologia));
            if (strlen($chave) > 3 && str_ends_with($chave, 'js')) {
                $chave = substr($chave, 0, -2);
            }
            if ($chave !== '') {
                $chaves[$chave] = true;
            }
        }
        return array_keys($chaves);
    }

    private function normalizarTexto(string $texto): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        return mb_strtolower(trim($semAcento !== false ? $semAcento : $texto));
    }
}
