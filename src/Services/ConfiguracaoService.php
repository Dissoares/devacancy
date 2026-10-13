<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\CatalogoConfiguracoes;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Interfaces\ConfiguracaoRepositoryInterface;
use App\Interfaces\MetodoPagamentoRepositoryInterface;

/**
 * Configurações do site editadas no painel. O valor do painel vence o do .env; apagar um campo
 * no painel faz o site voltar a usar o .env (se houver). Segredos nunca voltam para a tela.
 */
final class ConfiguracaoService
{
    public function __construct(
        private readonly ConfiguracaoRepositoryInterface $configuracoes,
        private readonly MetodoPagamentoRepositoryInterface $metodosPagamento,
    ) {
    }

    public function existe(string $secao): bool
    {
        return isset(CatalogoConfiguracoes::SECOES[$secao]);
    }

    /**
     * Uma seção do menu com os seus cartões e o estado de cada campo, pronta para a tela.
     * origem do campo: painel | env | vazio. Tokens ainda vazios são gerados aqui, já prontos para copiar.
     * status do cartão: configurado | incompleto | vazio (null quando o cartão não tem campos obrigatórios).
     */
    public function secao(string $chave): array
    {
        $secao = CatalogoConfiguracoes::SECOES[$chave] ?? throw new NaoEncontradoException();
        $salvos = $this->configuracoes->todas();
        $ativos = $this->metodosPagamento->ativos();
        $base = rtrim(endereco_do_site(), '/');

        $cartoes = [];
        foreach ($secao['cartoes'] as $chaveCartao => $cartao) {
            $campos = [];
            $valores = [];
            foreach ($cartao['campos'] as $chaveCampo => $campo) {
                $doPainel = (string) ($salvos[$chaveCampo] ?? '');
                $doArquivo = Ambiente::doArquivo($chaveCampo);

                if ($campo['tipo'] === 'token' && $doPainel === '' && $doArquivo === null) {
                    $doPainel = $this->gerarToken();
                    $this->configuracoes->salvar($chaveCampo, $doPainel);
                }

                $atual = $doPainel !== '' ? $doPainel : (string) $doArquivo;
                $valores[$chaveCampo] = $atual;
                $campos[$chaveCampo] = $campo + [
                    'origem' => $doPainel !== '' ? 'painel' : ($doArquivo !== null ? 'env' : 'vazio'),
                    // Segredos nunca vão para o HTML; só o final, para a pessoa reconhecer qual é.
                    'valor' => $campo['tipo'] === 'segredo' ? '' : $atual,
                    'final' => $campo['tipo'] === 'segredo' && $atual !== '' ? '…' . mb_substr($atual, -4) : null,
                ];
            }

            $cartoes[$chaveCartao] = [
                'titulo' => $cartao['titulo'],
                'descricao' => $cartao['descricao'],
                'icone' => $cartao['icone'] ?? null,
                'metodo' => $cartao['metodo'] ?? null,
                'ativo' => isset($cartao['metodo']) ? ($ativos[$cartao['metodo']] ?? true) : null,
                'status' => $this->status($cartao['obrigatorios'] ?? null, $valores),
                'campos' => $campos,
                'avisos' => array_map(fn (string $aviso) => str_replace('{APP_URL}', $base, $aviso), $cartao['avisos'] ?? []),
            ];
        }

        Ambiente::esquecerPainel();
        return ['titulo' => $secao['titulo'], 'descricao' => $secao['descricao'], 'extra' => $secao['extra'] ?? null, 'cartoes' => $cartoes];
    }

    /**
     * Salva os campos de um cartão. Em branco: segredo e token mantêm o atual; os demais voltam para o .env.
     * @param array<string, mixed> $dados campos do formulário (+ "limpar", "regenerar" e "__ativo")
     * @throws ValidacaoException
     */
    public function salvar(string $secao, string $cartao, array $dados): void
    {
        $definicao = CatalogoConfiguracoes::SECOES[$secao]['cartoes'][$cartao] ?? throw new NaoEncontradoException();
        $limpar = (array) ($dados['limpar'] ?? []);
        $regenerar = (array) ($dados['regenerar'] ?? []);

        $erros = [];
        $mudancas = [];
        foreach ($definicao['campos'] as $chave => $campo) {
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
        if (isset($definicao['metodo']) && isset($dados['__ativo_enviado'])) {
            $this->metodosPagamento->definir($definicao['metodo'], isset($dados['__ativo']));
        }
        Ambiente::esquecerPainel();
    }

    /** O que o servidor leu das configurações de e-mail (nunca a senha), para a tela de diagnóstico. */
    public function leituraDoEmail(): array
    {
        $usuario = (string) configuracao('email.smtp_usuario');
        $senha = (string) configuracao('email.smtp_senha');

        return [
            'modo' => (string) configuracao('email.smtp_host') !== '' ? 'SMTP' : 'mail() da hospedagem',
            'de' => (string) configuracao('email.de'),
            'host' => (string) configuracao('email.smtp_host'),
            'porta' => (string) configuracao('email.smtp_porta'),
            'seguranca' => (string) configuracao('email.smtp_seguranca'),
            'usuario' => $usuario,
            'usuario_completo' => $usuario === '' || str_contains($usuario, '@'),
            'senha_tamanho' => mb_strlen($senha),
            'senha_suspeita' => $senha !== trim($senha) || preg_match('/["\'\s]/', $senha) === 1,
            'url' => endereco_do_site(),
        ];
    }

    /** @param array<int, string[]>|null $grupos @param array<string, string> $valores */
    private function status(?array $grupos, array $valores): ?string
    {
        if ($grupos === null) {
            return null;
        }

        $ok = 0;
        foreach ($grupos as $alternativas) {
            foreach ($alternativas as $chave) {
                if (($valores[$chave] ?? '') !== '') {
                    $ok++;
                    break;
                }
            }
        }

        return match (true) {
            $ok === count($grupos) => 'configurado',
            $ok === 0 => 'vazio',
            default => 'incompleto',
        };
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
