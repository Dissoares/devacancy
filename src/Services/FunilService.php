<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Sessao;
use App\Core\Validador;
use App\DTOs\PerfilProfissionalDTO;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\AreaAtuacao;
use App\Enums\IdiomaFalado;
use App\Enums\ModeloTrabalho;
use App\Enums\RegiaoVagas;
use App\Enums\Senioridade;
use App\Exceptions\ValidacaoException;

/**
 * Questionário anônimo antes do cadastro. As respostas ficam na sessão, alimentam o match
 * da tela de vagas e viram o perfil profissional quando a pessoa cria a conta.
 */
final class FunilService
{
    private const CHAVE_SESSAO = 'questionario';

    /** Faixas de anos de experiência: valor salvo => chave do rótulo. */
    public const EXPERIENCIA = ['0' => 'menos_1', '1' => '1_2', '3' => '3_5', '6' => '6_9', '10' => '10_mais'];

    /** Faixas salariais ("a partir de"): mensal em reais no Brasil, anual em dólar fora. */
    public const FAIXAS_SALARIAIS = [
        'BRL' => [2000, 4000, 6000, 9000, 12000, 16000, 20000, 25000],
        'USD' => [30000, 50000, 70000, 90000, 110000, 140000, 170000, 200000],
    ];

    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    /**
     * Valida e guarda as respostas. Fora do Brasil, as vagas são sempre internacionais e os valores em dólar.
     * @throws ValidacaoException
     */
    public function salvarRespostas(array $dados, bool $noBrasil): array
    {
        $dados['modelos'] = array_values((array) ($dados['modelos'] ?? []));
        $dados['idiomas'] = array_values((array) ($dados['idiomas'] ?? []));
        $dados['regiao_vagas'] = $noBrasil ? ($dados['regiao_vagas'] ?? '') : RegiaoVagas::Internacional->value;

        Validador::validar($dados, [
            'area' => 'obrigatorio|enum:' . AreaAtuacao::class,
            'senioridade' => 'obrigatorio|enum:' . Senioridade::class,
            'anos_experiencia' => 'obrigatorio|entre:0,50',
            'stack' => 'obrigatorio|max:600',
            'idiomas' => 'obrigatorio|enum:' . IdiomaFalado::class,
            'modelos' => 'obrigatorio|enum:' . ModeloTrabalho::class,
            'regiao_vagas' => 'obrigatorio|enum:' . RegiaoVagas::class,
            'cidade' => 'max:120',
            'pretensao_salarial' => 'obrigatorio|entre:0,2000000',
            'ultima_remuneracao' => 'entre:0,2000000',
        ]);

        $area = AreaAtuacao::from($dados['area']);
        $senioridade = Senioridade::from($dados['senioridade']);

        $respostas = [
            'area' => $area->value,
            'senioridade' => $senioridade->value,
            'cargo_desejado' => t('funil.cargo_padrao', ['area' => $area->rotulo(), 'nivel' => $senioridade->rotulo()]),
            'anos_experiencia' => (string) (int) $dados['anos_experiencia'],
            'stack' => (string) $dados['stack'],
            'idiomas' => $dados['idiomas'],
            'modelo_trabalho' => $this->modeloDasEscolhas($dados['modelos'])->value,
            'modelos' => $dados['modelos'],
            'regiao_vagas' => $dados['regiao_vagas'],
            'cidade' => (string) ($dados['cidade'] ?? ''),
            'pretensao_salarial' => (string) (int) $dados['pretensao_salarial'],
            'ultima_remuneracao' => ($dados['ultima_remuneracao'] ?? '') !== '' ? (string) (int) $dados['ultima_remuneracao'] : '',
            'moeda_pretensao' => $noBrasil ? 'BRL' : 'USD',
        ];

        // Mesma validação do perfil completo (stack vazia, tamanhos), antes de guardar.
        Validador::validar($respostas, PerfilProfissionalDTO::regras());
        if (PerfilProfissionalDTO::deArray($respostas)->stack === []) {
            throw new ValidacaoException(['stack' => t('perfil.stack_vazia')]);
        }

        Sessao::definir(self::CHAVE_SESSAO, $respostas);
        return $respostas;
    }

    public function respostas(): ?array
    {
        return Sessao::obter(self::CHAVE_SESSAO);
    }

    /** Perfil montado só com as respostas, para calcular o match de quem ainda não tem conta. */
    public function perfilProvisorio(): ?PerfilProfissional
    {
        $respostas = $this->respostas();
        if ($respostas === null) {
            return null;
        }

        $dto = PerfilProfissionalDTO::deArray($respostas);
        return new PerfilProfissional(
            usuarioId: 0,
            area: $dto->area,
            senioridade: $dto->senioridade,
            cargoDesejado: $dto->cargoDesejado,
            anosExperiencia: $dto->anosExperiencia,
            stack: $dto->stack,
            modeloTrabalho: $dto->modeloTrabalho,
            cidade: $dto->cidade,
            pretensaoSalarial: $dto->pretensaoSalarial,
            linkedinUrl: null,
            githubUrl: null,
            portfolioUrl: null,
            curriculoArquivo: null,
            curriculoNomeOriginal: null,
            resumo: null,
            regiaoVagas: $dto->regiaoVagas,
            moedaPretensao: $dto->moedaPretensao,
            idiomas: $dto->idiomas,
            ultimaRemuneracao: $dto->ultimaRemuneracao,
        );
    }

    /**
     * Depois do cadastro ou login: transforma as respostas guardadas no perfil do profissional.
     * Retorna true se criou o perfil agora.
     */
    public function concluirCadastro(Usuario $usuario): bool
    {
        $respostas = $this->respostas();
        if ($respostas === null || !$usuario->ehProfissional() || $usuario->cadastroCompleto) {
            return false;
        }

        $this->usuarios->salvarPerfilProfissional($usuario, $respostas, null);
        Sessao::remover(self::CHAVE_SESSAO);
        return true;
    }

    /** Várias escolhas viram um só modelo: remoto + híbrido = híbrido (aceita os dois); com presencial = tanto faz. */
    private function modeloDasEscolhas(array $escolhas): ModeloTrabalho
    {
        $modelos = array_map(fn (string $valor) => ModeloTrabalho::from($valor), $escolhas);

        return match (true) {
            in_array(ModeloTrabalho::Indiferente, $modelos, true), count($modelos) === 3 => ModeloTrabalho::Indiferente,
            count($modelos) === 1 => $modelos[0],
            !in_array(ModeloTrabalho::Presencial, $modelos, true) => ModeloTrabalho::Hibrido,
            default => ModeloTrabalho::Indiferente,
        };
    }
}
