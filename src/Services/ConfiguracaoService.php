<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\CatalogoConfiguracoes;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\ConfiguracaoRepositoryInterface;

/**
 * Configurações do site editadas no painel. O valor do painel vence o do .env; apagar um campo
 * no painel faz o site voltar a usar o .env (se houver). Segredos nunca voltam para a tela.
 */
final class ConfiguracaoService
{
    public function __construct(private readonly ConfiguracaoRepositoryInterface $configuracoes)
    {
    }

    /**
     * Grupos do catálogo com o estado de cada campo, prontos para a tela.
     * origem: painel | env | vazio. Tokens vazios são gerados aqui, para já aparecerem prontos para copiar.
     */
    public function grupos(): array
    {
        $salvos = $this->configuracoes->todas();
        $base = rtrim((string) configuracao('app.url'), '/');
        $grupos = [];

        foreach (CatalogoConfiguracoes::GRUPOS as $chaveGrupo => $grupo) {
            $campos = [];
            foreach ($grupo['campos'] as $chave => $campo) {
                $doPainel = (string) ($salvos[$chave] ?? '');
                $doArquivo = Ambiente::doArquivo($chave);

                if ($campo['tipo'] === 'token' && $doPainel === '' && $doArquivo === null) {
                    $doPainel = $this->gerarToken();
                    $this->configuracoes->salvar($chave, $doPainel);
                }

                $atual = $doPainel !== '' ? $doPainel : (string) $doArquivo;
                $campos[$chave] = $campo + [
                    'origem' => $doPainel !== '' ? 'painel' : ($doArquivo !== null ? 'env' : 'vazio'),
                    // Segredos nunca vão para o HTML; mostra só o final, para a pessoa reconhecer qual é.
                    'valor' => $campo['tipo'] === 'segredo' ? '' : $atual,
                    'final' => $campo['tipo'] === 'segredo' && $atual !== '' ? '…' . mb_substr($atual, -4) : null,
                ];
            }

            $grupos[$chaveGrupo] = [
                'titulo' => $grupo['titulo'],
                'descricao' => $grupo['descricao'],
                'campos' => $campos,
                'avisos' => array_map(fn (string $aviso) => str_replace('{APP_URL}', $base, $aviso), $grupo['avisos'] ?? []),
            ];
        }

        Ambiente::esquecerPainel();
        return $grupos;
    }

    /**
     * Salva os campos de um grupo. Em branco: segredo mantém o atual; os demais voltam para o .env.
     * @param array<string, mixed> $dados campos do formulário (+ "limpar" e "regenerar" como listas de chaves)
     * @throws ValidacaoException
     */
    public function salvar(string $grupo, array $dados): void
    {
        $campos = CatalogoConfiguracoes::GRUPOS[$grupo]['campos'] ?? throw new NaoEncontradoException();
        $limpar = (array) ($dados['limpar'] ?? []);
        $regenerar = (array) ($dados['regenerar'] ?? []);

        $erros = [];
        $mudancas = [];
        foreach ($campos as $chave => $campo) {
            $valor = trim((string) ($dados[$chave] ?? ''));

            if (in_array($chave, $limpar, true)) {
                $mudancas[$chave] = null;
                continue;
            }
            if ($campo['tipo'] === 'token' && in_array($chave, $regenerar, true)) {
                $mudancas[$chave] = $this->gerarToken();
                continue;
            }
            // Segredo ou token em branco: mantém o atual (o campo de segredo nunca vem preenchido da tela).
            if (in_array($campo['tipo'], ['segredo', 'token'], true) && $valor === '') {
                continue;
            }
            if ($valor === '') {
                $mudancas[$chave] = null;
                continue;
            }

            $erro = $this->validar($campo, $valor);
            if ($erro !== null) {
                $erros[$chave] = $erro;
                continue;
            }
            $mudancas[$chave] = $campo['tipo'] === 'url' ? rtrim($valor, '/') : $valor;
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        foreach ($mudancas as $chave => $valor) {
            $valor === null ? $this->configuracoes->remover($chave) : $this->configuracoes->salvar($chave, $valor);
        }
        Ambiente::esquecerPainel();
    }

    private function validar(array $campo, string $valor): ?string
    {
        return match ($campo['tipo']) {
            'url' => filter_var($valor, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $valor) ? null : 'Informe um endereço completo, começando com https://',
            'email' => filter_var($valor, FILTER_VALIDATE_EMAIL) ? null : 'Informe um e-mail válido.',
            'opcao' => isset($campo['opcoes'][$valor]) ? null : 'Escolha uma das opções.',
            default => mb_strlen($valor) > 2000 ? 'Valor longo demais.' : null,
        };
    }

    private function gerarToken(): string
    {
        return bin2hex(random_bytes(20));
    }
}
