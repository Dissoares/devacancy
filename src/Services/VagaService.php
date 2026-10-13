<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ListaDeTags;
use App\Core\Validador;
use App\DTOs\VagaDTO;
use App\Entities\PerfilProfissional;
use App\Entities\Vaga;
use App\Enums\AreaAtuacao;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;
use App\Exceptions\NaoEncontradoException;
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
            throw new ValidacaoException(['salario_max' => t('vaga.salario_invertido')]);
        }
        if ($vaga->regiao === RegiaoVagas::Ambas) {
            throw new ValidacaoException(['regiao' => t('validacao.enum')]);
        }

        return $this->vagas->criar($vaga);
    }

    /** Vaga que pode ser aberta por qualquer pessoa (link compartilhado): só as ativas existem para o público. @throws NaoEncontradoException */
    public function publica(int $id): Vaga
    {
        $vaga = $this->vagas->buscarPorId($id);
        if ($vaga === null || !$vaga->ativa) {
            throw new NaoEncontradoException();
        }
        return $vaga;
    }

    /** @return Vaga[] */
    public function listar(?bool $ativa = null): array
    {
        return $this->vagas->listar($ativa);
    }

    public function alternarAtiva(int $id): void
    {
        $this->vagas->alternarAtiva($id);
    }

    public function excluir(int $id): void
    {
        $this->vagas->excluir($id);
    }

    /** @return array<string, array{total: int, ativas: int}> */
    public function contarPorFonte(): array
    {
        return $this->vagas->contarPorFonte();
    }

    /** Mínimo de vagas que a busca tenta mostrar, completando com as vagas reais mais próximas do perfil. */
    private const MINIMO_RESULTADOS = 5;

    /**
     * Vagas para o perfil, da mais para a menos compatível. Se as compatíveis forem menos que o mínimo,
     * completa com vagas reais próximas (mesma área com nível vizinho, ou remotas da área/stack).
     * Nunca inclui vaga presencial/híbrida de outra cidade/estado. "proxima" marca as da reserva.
     * @return array<int, array{vaga: Vaga, compatibilidade: int, proxima: bool}>
     */
    public function compativeis(PerfilProfissional $perfil): array
    {
        $estritas = array_map(fn (array $item) => [...$item, 'proxima' => false], $this->estritas($perfil));
        if (count($estritas) >= self::MINIMO_RESULTADOS) {
            return $estritas;
        }

        $jaIncluidas = array_flip(array_map(fn (array $item) => $item['vaga']->id, $estritas));
        $regioes = $perfil->regiaoVagas->aceitas();
        $areas = $this->areasAceitas($perfil->area);
        $stackPerfil = $this->normalizarStack($perfil->stack);
        $niveis = Senioridade::cases();
        $nivelPerfil = array_search($perfil->senioridade, $niveis, true);

        // Camadas, da mais próxima para a mais distante, com desconto na nota de cada uma.
        // Em todas, no máximo dois níveis de distância: estagiário não recebe vaga de tech lead.
        $nivelPerto = fn (Vaga $vaga) => abs(array_search($vaga->senioridade, $niveis, true) - $nivelPerfil) <= 2;
        $camadas = [
            // Mesma área, mas com alguma tecnologia em comum: sem isso, um dev Java recebia vaga de Cobol/Mainframe.
            [5, fn (Vaga $vaga, int $emComum) => $nivelPerto($vaga) && in_array($vaga->area, $areas, true) && ($emComum > 0 || $vaga->stack === [])],
            [10, fn (Vaga $vaga, int $emComum) => $nivelPerto($vaga) && $vaga->modeloTrabalho === ModeloTrabalho::Remoto && ($emComum > 0 || in_array($vaga->area, $areas, true))],
            [15, fn (Vaga $vaga, int $emComum) => $nivelPerto($vaga) && $vaga->modeloTrabalho === ModeloTrabalho::Remoto],
        ];

        $reserva = [];
        $ativas = array_filter($this->vagas->listarAtivas(self::DIAS_VALIDADE), fn (Vaga $vaga) => in_array($vaga->regiao, $regioes, true));
        foreach ($camadas as [$desconto, $aceita]) {
            foreach ($ativas as $vaga) {
                if (isset($jaIncluidas[$vaga->id]) || !$this->localCompativel($perfil, $vaga)) {
                    continue;
                }
                $stackVaga = $this->normalizarStack($vaga->stack);
                $emComum = count(array_intersect($stackVaga, $stackPerfil));
                if (!$aceita($vaga, $emComum)) {
                    continue;
                }
                $cobertura = $stackVaga === [] ? 0.3 : $emComum / count($stackVaga);
                $reserva[] = ['vaga' => $vaga, 'compatibilidade' => max(35, $this->pontuar($perfil, $vaga, $cobertura) - $desconto), 'proxima' => true];
                $jaIncluidas[$vaga->id] = true;
            }
            if (count($estritas) + count($reserva) >= self::MINIMO_RESULTADOS) {
                break;
            }
        }

        usort($reserva, fn (array $a, array $b) => [$b['compatibilidade'], $b['vaga']->criadoEm] <=> [$a['compatibilidade'], $a['vaga']->criadoEm]);
        return [...$estritas, ...array_slice($reserva, 0, max(0, self::MINIMO_RESULTADOS - count($estritas)))];
    }

    /**
     * Só as vagas que batem área, nível (±1), modelo, local e stack.
     * @return array<int, array{vaga: Vaga, compatibilidade: int}>
     */
    private function estritas(PerfilProfissional $perfil): array
    {
        $candidatas = $this->vagas->buscarCandidatas(
            array_map(fn (AreaAtuacao $area) => $area->value, $this->areasAceitas($perfil->area)),
            array_map(fn (Senioridade $nivel) => $nivel->value, $this->senioridadesAceitas($perfil->senioridade)),
            array_map(fn (ModeloTrabalho $modelo) => $modelo->value, $this->modelosAceitos($perfil->modeloTrabalho)),
            array_map(fn (RegiaoVagas $regiao) => $regiao->value, $perfil->regiaoVagas->aceitas()),
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
            // Sem stack identificada, a vaga só serve se for exatamente da área da pessoa.
            if ($stackVaga === [] && $vaga->area !== $perfil->area) {
                continue;
            }

            $resultado[] = [
                'vaga' => $vaga,
                'compatibilidade' => $this->pontuar($perfil, $vaga, $stackVaga === [] ? 0.3 : $emComum / count($stackVaga)),
            ];
        }

        usort($resultado, fn (array $a, array $b) => [$b['compatibilidade'], $b['vaga']->criadoEm] <=> [$a['compatibilidade'], $a['vaga']->criadoEm]);

        return $resultado;
    }

    /**
     * Vagas mais recentes (fora do Brasil, só internacionais), para a vitrine "ao vivo" da landing.
     * @return Vaga[]
     */
    public function recentes(bool $noBrasil, int $limite = 5): array
    {
        $ativas = array_filter(
            $this->vagas->listarAtivas(self::DIAS_VALIDADE),
            fn (Vaga $vaga) => $noBrasil || $vaga->regiao === RegiaoVagas::Internacional,
        );
        return array_slice(array_values($ativas), 0, $limite);
    }

    /** Vagas que entraram nas últimas 24 horas. */
    public function contarNovas(bool $noBrasil): int
    {
        $limite = time() - 86400;
        return count(array_filter($this->recentes($noBrasil, 2000), fn (Vaga $vaga) => strtotime($vaga->criadoEm) >= $limite));
    }

    /** Total de vagas ativas (fora do Brasil, só as internacionais), para o selo da landing. */
    public function contarAtivas(bool $noBrasil): int
    {
        return array_sum($this->contarPorRegiao($noBrasil));
    }

    /** @return array<string, int> */
    private function contarPorRegiao(bool $noBrasil): array
    {
        $contagem = [];
        foreach ($this->vagas->listarAtivas(self::DIAS_VALIDADE) as $vaga) {
            if ($noBrasil || $vaga->regiao === RegiaoVagas::Internacional) {
                $contagem[$vaga->regiao->value] = ($contagem[$vaga->regiao->value] ?? 0) + 1;
            }
        }
        return $contagem;
    }

    /**
     * Radar do questionário: quantas vagas ativas batem com o que a pessoa respondeu até agora.
     * Cada resposta dada vira um filtro (área, nível, modelo, região, stack); as não respondidas não filtram.
     */
    public function contarParaRespostas(array $dados, bool $noBrasil): int
    {
        $area = AreaAtuacao::tryFrom((string) ($dados['area'] ?? ''));
        $areas = $area !== null ? $this->areasAceitas($area) : null;
        $nivel = Senioridade::tryFrom((string) ($dados['senioridade'] ?? ''));
        $niveis = $nivel !== null ? $this->senioridadesAceitas($nivel) : null;
        $modelos = array_filter(array_map(fn ($modelo) => ModeloTrabalho::tryFrom((string) $modelo), (array) ($dados['modelos'] ?? [])));
        $regiao = $noBrasil ? RegiaoVagas::tryFrom((string) ($dados['regiao_vagas'] ?? '')) : RegiaoVagas::Internacional;
        $regioes = $regiao?->aceitas() ?? RegiaoVagas::deVagas();
        $stack = $this->normalizarStack(ListaDeTags::normalizar((string) ($dados['stack'] ?? '')));

        return count(array_filter($this->vagas->listarAtivas(self::DIAS_VALIDADE), function (Vaga $vaga) use ($areas, $niveis, $modelos, $regioes, $stack): bool {
            if ($areas !== null && !in_array($vaga->area, $areas, true) || !in_array($vaga->regiao, $regioes, true)) {
                return false;
            }
            if ($niveis !== null && !in_array($vaga->senioridade, $niveis, true)) {
                return false;
            }
            if ($modelos !== [] && $vaga->modeloTrabalho !== ModeloTrabalho::Remoto && !in_array($vaga->modeloTrabalho, $modelos, true)) {
                return false;
            }
            $stackVaga = $this->normalizarStack($vaga->stack);
            return $stack === [] || $stackVaga === [] || array_intersect($stackVaga, $stack) !== [];
        }));
    }

    /** Tecnologias da vaga que a pessoa tem (com o nome como está na vaga); sem nenhuma em comum, as primeiras da vaga. @return string[] */
    public function stackEmComum(PerfilProfissional $perfil, Vaga $vaga, int $limite = 3): array
    {
        $doPerfil = $this->normalizarStack($perfil->stack);
        $emComum = array_filter($vaga->stack, fn (string $tecnologia) => array_intersect($this->normalizarStack([$tecnologia]), $doPerfil) !== []);
        return array_slice(array_values($emComum ?: $vaga->stack), 0, $limite);
    }

    /** Quantas das vagas compatíveis entraram nas últimas 24 horas. */
    public function contarRecentes(array $compativeis): int
    {
        $limite = time() - 86400;
        return count(array_filter($compativeis, fn (array $item) => strtotime($item['vaga']->criadoEm) >= $limite));
    }

    /**
     * Números reais do mercado para a tela de análise: vagas analisadas, demanda por tecnologia da pessoa,
     * vagas na área dela e médias das vagas compatíveis.
     * O salário médio usa a moeda mais comum entre as vagas compatíveis.
     * @return array{analisadas: int, porTecnologia: array<string, int>, naArea: int, salarioMedio: ?int, salarioMaximo: ?int, moeda: string, percentualRemoto: int}
     */
    public function panorama(PerfilProfissional $perfil, array $compativeis): array
    {
        $regioes = $perfil->regiaoVagas->aceitas();
        $ativas = array_values(array_filter(
            $this->vagas->listarAtivas(self::DIAS_VALIDADE),
            fn (Vaga $vaga) => in_array($vaga->regiao, $regioes, true),
        ));
        $areas = $this->areasAceitas($perfil->area);
        $stacksDoMercado = array_map(fn (Vaga $vaga) => $this->normalizarStack($vaga->stack), $ativas);

        $porTecnologia = [];
        foreach (array_slice($perfil->stack, 0, 6) as $tecnologia) {
            $chave = $this->normalizarStack([$tecnologia])[0] ?? null;
            $porTecnologia[$tecnologia] = $chave === null ? 0 : count(array_filter($stacksDoMercado, fn (array $stack) => in_array($chave, $stack, true)));
        }

        $salarios = [];
        $maximos = [];
        $remotas = 0;
        foreach ($compativeis as ['vaga' => $vaga]) {
            $faixa = array_filter([$vaga->salarioMin, $vaga->salarioMax]);
            if ($faixa !== []) {
                $salarios[$vaga->moeda][] = array_sum($faixa) / count($faixa);
                $maximos[$vaga->moeda][] = max($faixa);
            }
            $remotas += $vaga->modeloTrabalho === ModeloTrabalho::Remoto ? 1 : 0;
        }

        uasort($salarios, fn (array $a, array $b) => count($b) <=> count($a));
        // Quem quer vagas no Brasil vê o salário em reais, se houver vaga em reais com salário.
        if ($perfil->regiaoVagas !== RegiaoVagas::Internacional && isset($salarios['BRL'])) {
            $salarios = ['BRL' => $salarios['BRL']] + $salarios;
        }
        $moeda = (string) (array_key_first($salarios) ?? ($perfil->regiaoVagas === RegiaoVagas::Internacional ? 'USD' : 'BRL'));
        $valores = $salarios[$moeda] ?? [];

        return [
            'analisadas' => count($ativas),
            'porTecnologia' => $porTecnologia,
            'naArea' => count(array_filter($ativas, fn (Vaga $vaga) => in_array($vaga->area, $areas, true))),
            'salarioMedio' => $valores !== [] ? (int) (round(array_sum($valores) / count($valores) / 100) * 100) : null,
            'salarioMaximo' => isset($maximos[$moeda]) ? max($maximos[$moeda]) : null,
            'moeda' => $moeda,
            'percentualRemoto' => $compativeis !== [] ? (int) round(100 * $remotas / count($compativeis)) : 0,
        ];
    }

    /**
     * As tecnologias mais pedidas nas vagas abertas da área do profissional que ainda não estão no perfil dele.
     * @return array<string, int> tecnologia => quantidade de vagas
     */
    public function tecnologiasEmAlta(PerfilProfissional $perfil, int $limite = 6): array
    {
        $areas = $this->areasAceitas($perfil->area);
        $regioes = $perfil->regiaoVagas->aceitas();
        $tem = array_flip($this->normalizarStack($perfil->stack));

        $contagem = [];
        $rotulos = [];
        foreach ($this->vagas->listarAtivas(self::DIAS_VALIDADE) as $vaga) {
            if ($areas !== null && !in_array($vaga->area, $areas, true) || !in_array($vaga->regiao, $regioes, true)) {
                continue;
            }
            // Só vagas que já pedem algo do que a pessoa sabe: as outras tecnologias dela não são o próximo passo.
            if ($tem !== [] && array_intersect($this->normalizarStack($vaga->stack), array_keys($tem)) === []) {
                continue;
            }
            foreach ($vaga->stack as $tecnologia) {
                $chave = $this->normalizarStack([$tecnologia])[0] ?? null;
                if ($chave === null || isset($tem[$chave])) {
                    continue;
                }
                $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
                $rotulos[$chave] ??= $tecnologia;
            }
        }

        arsort($contagem);
        $resultado = [];
        foreach (array_slice($contagem, 0, $limite, true) as $chave => $total) {
            $resultado[$rotulos[$chave]] = $total;
        }
        return $resultado;
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
        return $this->distancia($perfil, $vaga) !== 'longe';
    }

    /**
     * Vaga presencial/híbrida vale para a mesma cidade ou o mesmo estado ("Blumenau, SC" e "Florianópolis, SC").
     * Remota, ou sem cidade conhecida, vale para todo mundo.
     */
    private function distancia(PerfilProfissional $perfil, Vaga $vaga): string
    {
        if ($vaga->modeloTrabalho === ModeloTrabalho::Remoto || !$vaga->cidade || !$perfil->cidade) {
            return 'qualquer';
        }

        [$cidadeVaga, $estadoVaga] = $this->separarLocal($vaga->cidade);
        [$cidadePerfil, $estadoPerfil] = $this->separarLocal($perfil->cidade);

        return match (true) {
            $cidadeVaga === $cidadePerfil => 'mesma_cidade',
            $estadoVaga !== null && $estadoVaga === $estadoPerfil => 'mesmo_estado',
            default => 'longe',
        };
    }

    /** "São Paulo, SP" vira ["sao paulo", "sp"]; sem estado, ["sao paulo", null]. @return array{0: string, 1: ?string} */
    private function separarLocal(string $local): array
    {
        $partes = array_map(fn (string $parte) => $this->normalizarTexto($parte), explode(',', $local, 2));
        return [$partes[0], ($partes[1] ?? '') !== '' ? $partes[1] : null];
    }

    private function pontuar(PerfilProfissional $perfil, Vaga $vaga, float $coberturaStack): int
    {
        $pontos = $vaga->area === $perfil->area ? 25 : 15;
        $pontos += $vaga->senioridade === $perfil->senioridade ? 25 : 15;
        $pontos += $perfil->modeloTrabalho === ModeloTrabalho::Indiferente || $vaga->modeloTrabalho === $perfil->modeloTrabalho ? 15 : 10;
        $pontos += (int) round(35 * $coberturaStack);
        $pontos -= $this->distancia($perfil, $vaga) === 'mesmo_estado' ? 8 : 0;

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
        return mb_strtolower(trim(sem_acentos($texto)));
    }
}
