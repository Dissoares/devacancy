<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Configurações do site. Ordem de leitura: o que foi salvo no painel admin (tabela "configuracoes"),
 * depois o .env. A conexão com o banco (DB_*) e o token de emergência do /install (MIGRACOES_TOKEN)
 * vêm só do .env: são o que permite abrir o painel em primeiro lugar.
 */
final class Ambiente
{
    /** Chaves que nunca são lidas do painel. */
    private const SO_DO_ARQUIVO = ['DB_HOST', 'DB_PORTA', 'DB_NOME', 'DB_USUARIO', 'DB_SENHA', 'MIGRACOES_TOKEN'];

    private static array $valores = [];
    /** @var array<string, string>|null valores salvos no painel, lidos uma vez por requisição */
    private static ?array $painel = null;

    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
            self::$valores[$chave] = trim($valor, "\"'");
        }
    }

    public static function obter(string $chave, ?string $padrao = null): ?string
    {
        if (!in_array($chave, self::SO_DO_ARQUIVO, true)) {
            $doPainel = self::painel()[$chave] ?? '';
            if ($doPainel !== '') {
                return $doPainel;
            }
        }

        return self::doArquivo($chave) ?? $padrao;
    }

    /** Só o valor do .env (ou da variável de ambiente do servidor), ignorando o painel. */
    public static function doArquivo(string $chave): ?string
    {
        $valor = self::$valores[$chave] ?? getenv($chave);
        return ($valor === false || $valor === '') ? null : $valor;
    }

    /** Depois de salvar no painel, a próxima leitura busca de novo. */
    public static function esquecerPainel(): void
    {
        self::$painel = null;
    }

    /**
     * Lê a tabela de configurações com uma conexão própria (feita só com o .env), para não depender da
     * configuração do site enquanto ela ainda está sendo montada. Sem banco ou sem a tabela, segue sem painel.
     * @return array<string, string>
     */
    private static function painel(): array
    {
        if (self::$painel !== null) {
            return self::$painel;
        }
        self::$painel = [];

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    self::doArquivo('DB_HOST') ?? '127.0.0.1',
                    self::doArquivo('DB_PORTA') ?? '3306',
                    self::doArquivo('DB_NOME') ?? 'devacancy',
                ),
                self::doArquivo('DB_USUARIO') ?? 'root',
                self::doArquivo('DB_SENHA') ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
            );
            self::$painel = $pdo->query('SELECT chave, valor FROM configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Throwable) {
            // Banco fora do ar ou migração 014 ainda não aplicada: vale só o .env.
        }

        return self::$painel;
    }
}
