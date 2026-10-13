<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Sessao;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\AreaAtuacao;
use App\Enums\IdiomaFalado;
use App\Enums\Senioridade;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\PerfilProfissionalRepositoryInterface;
use App\Interfaces\UsuarioRepositoryInterface;

/**
 * Importação do currículo (PDF ou PDF do LinkedIn): lê o texto, guarda a PROPOSTA na sessão e só altera o perfil
 * com o que a pessoa marcar na revisão. Os valores aplicados vêm sempre da proposta guardada aqui, nunca do formulário.
 */
final class ImportacaoPerfilService
{
    private const CHAVE = 'importacao_curriculo';
    private const TEXTO_MAXIMO = 60000;
    private const TEXTO_MINIMO = 120;

    public function __construct(
        private readonly LeitorCurriculoService $leitor,
        private readonly UsuarioService $usuarios,
        private readonly PerfilProfissionalRepositoryInterface $perfis,
        private readonly UsuarioRepositoryInterface $repositorioUsuarios,
        private readonly CurriculoService $curriculos,
    ) {
    }

    /**
     * Lê o texto e guarda a proposta (e o PDF, se veio). Substitui qualquer proposta anterior.
     * @param array<string, mixed>|null $upload arquivo enviado (o PDF original, para virar o currículo do perfil)
     * @throws ValidacaoException
     */
    public function analisar(Usuario $usuario, string $texto, string $origem, ?array $upload): void
    {
        $texto = mb_substr(trim($texto), 0, self::TEXTO_MAXIMO);
        if (mb_strlen($texto) < self::TEXTO_MINIMO) {
            throw new ValidacaoException(['texto' => t('importar.sem_texto')]);
        }

        $lido = $this->leitor->ler($texto);
        $achou = ($lido['cargo'] ?? null) !== null || $lido['stack'] !== [] || $lido['experiencias'] !== [];
        if (!$achou) {
            throw new ValidacaoException(['texto' => t('importar.nao_reconheceu')]);
        }

        $this->descartar();

        $pdf = null;
        if ($upload !== null && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $pdf = $this->curriculos->armazenar($upload);
            } catch (ValidacaoException) {
                // O PDF original é opcional: se não puder ser guardado, a leitura do texto continua valendo.
            }
        }

        Sessao::definir(self::CHAVE, [
            'lido' => $lido,
            'origem' => $origem === 'linkedin' ? 'linkedin' : 'pdf',
            'pdf' => $pdf,
        ]);
    }

    public function temProposta(): bool
    {
        return Sessao::obter(self::CHAVE) !== null;
    }

    /**
     * A proposta lado a lado com o perfil atual, pronta para a tela de revisão. Só entram os campos em que a
     * importação acrescenta ou muda algo.
     * @return array{origem: string, linhas: array<int, array<string, mixed>>, pdf: ?string}|null
     */
    public function revisao(PerfilProfissional $atual, Usuario $usuario): ?array
    {
        $proposta = Sessao::obter(self::CHAVE);
        if ($proposta === null) {
            return null;
        }
        $lido = $proposta['lido'];
        $linhas = [];

        $adicionar = function (string $chave, string $rotulo, ?string $atualTexto, ?string $novoTexto, array $extra = []) use (&$linhas): void {
            if ($novoTexto === null || $novoTexto === '' || $novoTexto === $atualTexto) {
                return;
            }
            $linhas[] = ['chave' => $chave, 'rotulo' => $rotulo, 'atual' => $atualTexto, 'novo' => $novoTexto, ...$extra];
        };

        $nomeAtualEhEmail = (bool) preg_match('/^[a-z]+\s?[a-z]*$/iu', $usuario->nome) && mb_strlen($usuario->nome) < 18;
        $adicionar('nome', t('importar.campo_nome'), $usuario->nome, $lido['nome'], ['dica' => $nomeAtualEhEmail ? t('importar.dica_nome') : null]);
        $adicionar('cargo', t('importar.campo_cargo'), $atual->cargoDesejado, $lido['cargo']);
        $adicionar('senioridade', t('importar.campo_senioridade'), $atual->senioridade->rotulo(), $lido['senioridade'] ? Senioridade::from($lido['senioridade'])->rotulo() : null);
        $adicionar('area', t('importar.campo_area'), $atual->area->rotulo(), $lido['area'] ? AreaAtuacao::from($lido['area'])->rotulo() : null);
        $adicionar('anos', t('importar.campo_anos'), (string) $atual->anosExperiencia, $lido['anos'] !== null ? (string) $lido['anos'] : null);

        $novasTecnologias = $this->tecnologiasNovas($atual->stack, $lido['stack']);
        if ($novasTecnologias !== []) {
            $linhas[] = [
                'chave' => 'stack', 'rotulo' => t('importar.campo_stack'), 'atual' => $atual->stack !== [] ? implode(', ', $atual->stack) : null,
                'novo' => implode(', ', $novasTecnologias), 'chips' => $novasTecnologias, 'soma' => true,
            ];
        }

        $idiomasNovos = array_values(array_diff($lido['idiomas'], $atual->idiomas));
        if ($idiomasNovos !== []) {
            $linhas[] = [
                'chave' => 'idiomas', 'rotulo' => t('importar.campo_idiomas'),
                'atual' => $atual->idiomas !== [] ? implode(', ', array_map(fn (string $c) => IdiomaFalado::from($c)->rotulo(), $atual->idiomas)) : null,
                'novo' => implode(', ', array_map(fn (string $c) => IdiomaFalado::from($c)->rotulo(), $idiomasNovos)), 'soma' => true,
            ];
        }

        $adicionar('cidade', t('importar.campo_cidade'), $atual->cidade, $lido['cidade']);
        $adicionar('telefone', t('importar.campo_telefone'), $usuario->telefone, $lido['telefone']);
        $adicionar('linkedin_url', 'LinkedIn', $atual->linkedinUrl, $lido['linkedin_url']);
        $adicionar('github_url', 'GitHub', $atual->githubUrl, $lido['github_url']);
        $adicionar('portfolio_url', t('importar.campo_portfolio'), $atual->portfolioUrl, $lido['portfolio_url']);
        $adicionar('resumo', t('importar.campo_resumo'), $atual->resumo, $lido['resumo'], ['longo' => true]);

        if ($lido['experiencias'] !== [] && $lido['experiencias'] != $atual->experiencias) {
            $linhas[] = [
                'chave' => 'experiencias', 'rotulo' => t('importar.campo_experiencias'),
                'atual' => $atual->experiencias !== [] ? $this->itens(count($atual->experiencias)) : null,
                'novo' => $this->itens(count($lido['experiencias'])), 'lista' => $lido['experiencias'], 'tipo_lista' => 'experiencias',
            ];
        }
        if ($lido['formacao'] !== [] && $lido['formacao'] != $atual->formacao) {
            $linhas[] = [
                'chave' => 'formacao', 'rotulo' => t('importar.campo_formacao'),
                'atual' => $atual->formacao !== [] ? $this->itens(count($atual->formacao)) : null,
                'novo' => $this->itens(count($lido['formacao'])), 'lista' => $lido['formacao'], 'tipo_lista' => 'formacao',
            ];
        }
        if ($proposta['pdf'] !== null && $proposta['pdf']['nomeOriginal'] !== $atual->curriculoNomeOriginal) {
            $linhas[] = [
                'chave' => 'curriculo', 'rotulo' => t('importar.campo_curriculo'),
                'atual' => $atual->possuiCurriculo() ? $atual->curriculoNomeOriginal : null,
                'novo' => $proposta['pdf']['nomeOriginal'],
            ];
        }

        return ['origem' => $proposta['origem'], 'linhas' => $linhas, 'pdf' => $proposta['pdf']['nomeOriginal'] ?? null];
    }

    /**
     * Aplica ao perfil só o que foi marcado. Tecnologias e idiomas são somados ao que já existe.
     * @param string[] $marcados chaves de campo da tela de revisão
     * @return int quantos campos foram atualizados
     * @throws NaoEncontradoException sem proposta guardada
     * @throws ValidacaoException
     */
    public function aplicar(Usuario $usuario, PerfilProfissional $atual, array $marcados): int
    {
        $proposta = Sessao::obter(self::CHAVE) ?? throw new NaoEncontradoException();
        $lido = $proposta['lido'];
        $marcados = array_values(array_unique($marcados));
        $usar = fn (string $chave): bool => in_array($chave, $marcados, true);

        $dados = $atual->comoFormulario();
        $dados['telefone'] = $usuario->telefone;

        if ($usar('cargo') && $lido['cargo']) {
            $dados['cargo_desejado'] = $lido['cargo'];
        }
        if ($usar('senioridade') && $lido['senioridade']) {
            $dados['senioridade'] = $lido['senioridade'];
        }
        if ($usar('area') && $lido['area']) {
            $dados['area'] = $lido['area'];
        }
        if ($usar('anos') && $lido['anos'] !== null) {
            $dados['anos_experiencia'] = $lido['anos'];
        }
        if ($usar('stack')) {
            $dados['stack'] = implode(', ', [...$atual->stack, ...$this->tecnologiasNovas($atual->stack, $lido['stack'])]);
        }
        if ($usar('idiomas')) {
            $dados['idiomas'] = array_values(array_unique([...$atual->idiomas, ...$lido['idiomas']]));
            if (in_array('en', $dados['idiomas'], true) && $lido['nivel_ingles'] !== null && $atual->nivelIngles === null) {
                $dados['nivel_ingles'] = $lido['nivel_ingles'];
            }
        }
        foreach (['cidade', 'telefone', 'linkedin_url', 'github_url', 'portfolio_url', 'resumo'] as $campo) {
            if ($usar($campo) && !empty($lido[$campo])) {
                $dados[$campo] = $lido[$campo];
            }
        }

        $this->usuarios->salvarPerfilProfissional($usuario, $dados, null);

        if ($usar('nome') && !empty($lido['nome'])) {
            $this->repositorioUsuarios->atualizarNome($usuario->id, mb_substr($lido['nome'], 0, 120));
        }
        $this->perfis->salvarImportacao(
            $usuario->id,
            $usar('experiencias') ? $lido['experiencias'] : null,
            $usar('formacao') ? $lido['formacao'] : null,
        );

        if ($usar('curriculo') && $proposta['pdf'] !== null) {
            $this->perfis->definirCurriculo($usuario->id, $proposta['pdf']['arquivo'], $proposta['pdf']['nomeOriginal']);
            $this->curriculos->remover($atual->curriculoArquivo);
            $proposta['pdf'] = null;
        }

        Sessao::definir(self::CHAVE, $proposta);
        $this->descartar();
        return count($marcados);
    }

    /** Esquece a proposta e apaga o PDF que ainda não virou currículo. */
    public function descartar(): void
    {
        $proposta = Sessao::obter(self::CHAVE);
        if ($proposta !== null) {
            $this->curriculos->remover($proposta['pdf']['arquivo'] ?? null);
        }
        Sessao::remover(self::CHAVE);
    }

    private function itens(int $n): string
    {
        return t($n === 1 ? 'importar.n_item' : 'importar.n_itens', ['n' => $n]);
    }

    /**
     * @param string[] $atuais @param string[] $lidas
     * @return string[] as lidas que ainda não estão no perfil (sem diferenciar maiúsculas)
     */
    private function tecnologiasNovas(array $atuais, array $lidas): array
    {
        $tem = array_map('mb_strtolower', $atuais);
        return array_values(array_filter($lidas, fn (string $t) => !in_array(mb_strtolower($t), $tem, true)));
    }
}
