<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Aplica as migrações de database/migrations. Usado pelo terminal (database/instalar.php)
 * e pelo navegador (painel admin e /install com token).
 */
final class MigracaoService
{
    private const TRAVA = 'devacancy_migracoes';

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $diretorio,
    ) {
    }

    /** @return array<int, array{arquivo: string, executada_em: ?string}> todas as migrações, na ordem em que rodam */
    public function estado(): array
    {
        $this->garantirTabela();
        $feitas = $this->pdo->query('SELECT arquivo, executada_em FROM migracoes')->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_map(
            fn (string $arquivo) => ['arquivo' => $arquivo, 'executada_em' => $feitas[$arquivo] ?? null],
            $this->arquivos(),
        );
    }

    /** @return string[] */
    public function pendentes(): array
    {
        return array_values(array_map(
            fn (array $migracao) => $migracao['arquivo'],
            array_filter($this->estado(), fn (array $migracao) => $migracao['executada_em'] === null),
        ));
    }

    /**
     * Roda as pendentes, uma a uma, parando na primeira que falhar (a que falhou não é marcada como feita).
     * @return array{executadas: string[], erro: ?array{arquivo: string, mensagem: string}}
     */
    public function executarPendentes(): array
    {
        $this->garantirTabela();

        // Impede duas execuções ao mesmo tempo (dois cliques, duas abas).
        if ((int) $this->pdo->query("SELECT GET_LOCK('" . self::TRAVA . "', 0)")->fetchColumn() !== 1) {
            throw new RuntimeException('Já existe uma execução de migrações em andamento. Aguarde alguns segundos e tente de novo.');
        }

        $executadas = [];
        try {
            foreach ($this->pendentes() as $arquivo) {
                try {
                    // Um comando por vez: o PDO só avisa erro do primeiro comando quando vários vão juntos.
                    foreach ($this->comandos((string) file_get_contents($this->diretorio . '/' . $arquivo)) as $comando) {
                        $this->pdo->exec($comando);
                    }
                    $this->pdo->prepare('INSERT INTO migracoes (arquivo) VALUES (?)')->execute([$arquivo]);
                    $executadas[] = $arquivo;
                } catch (Throwable $erro) {
                    return ['executadas' => $executadas, 'erro' => ['arquivo' => $arquivo, 'mensagem' => $erro->getMessage()]];
                }
            }
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('" . self::TRAVA . "')");
        }

        return ['executadas' => $executadas, 'erro' => null];
    }

    private function garantirTabela(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS migracoes (arquivo VARCHAR(190) PRIMARY KEY, executada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    }

    /** @return string[] */
    private function arquivos(): array
    {
        $arquivos = array_map('basename', glob($this->diretorio . '/*.sql') ?: []);
        sort($arquivos);

        return $arquivos;
    }

    /**
     * Separa o arquivo em comandos. Linhas de comentário ("-- ...") saem antes de dividir por ";",
     * porque os comentários podem ter ponto e vírgula no texto.
     * @return string[]
     */
    private function comandos(string $sql): array
    {
        $linhas = array_filter(explode("\n", $sql), fn (string $linha) => !str_starts_with(ltrim($linha), '--'));

        return array_values(array_filter(array_map('trim', explode(';', implode("\n", $linhas)))));
    }
}
