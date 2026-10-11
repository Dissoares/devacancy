<?php

/**
 * Cria o banco, roda as migrações e cria (ou atualiza) o administrador.
 * Uso: php database/instalar.php admin@email.com "SenhaForte123"
 */

declare(strict_types=1);

use App\Core\Ambiente;

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';

Ambiente::carregar(RAIZ . '/.env');
$banco = configuracao('banco');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $banco['host'], $banco['porta']),
    $banco['usuario'],
    $banco['senha'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$nomeBanco = str_replace('`', '', $banco['nome']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$nomeBanco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$nomeBanco}`");
echo "Banco `{$nomeBanco}` pronto.\n";

$pdo->exec('CREATE TABLE IF NOT EXISTS migracoes (arquivo VARCHAR(190) PRIMARY KEY, executada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$executadas = $pdo->query('SELECT arquivo FROM migracoes')->fetchAll(PDO::FETCH_COLUMN);

$arquivos = glob(__DIR__ . '/migrations/*.sql');
sort($arquivos);
foreach ($arquivos as $arquivo) {
    $nome = basename($arquivo);
    if (in_array($nome, $executadas, true)) {
        continue;
    }
    $pdo->exec(file_get_contents($arquivo));
    $pdo->prepare('INSERT INTO migracoes (arquivo) VALUES (?)')->execute([$nome]);
    echo "Migração executada: {$nome}\n";
}

[$email, $senha] = [$argv[1] ?? null, $argv[2] ?? null];
if ($email && $senha) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8) {
        exit("Informe um e-mail válido e uma senha com pelo menos 8 caracteres.\n");
    }
    $pdo->prepare(
        "INSERT INTO usuarios (tipo, nome, email, senha_hash, cadastro_completo) VALUES ('admin', 'Administrador', ?, ?, 1)
         ON DUPLICATE KEY UPDATE tipo = 'admin', senha_hash = VALUES(senha_hash), cadastro_completo = 1"
    )->execute([mb_strtolower($email), password_hash($senha, PASSWORD_DEFAULT)]);
    echo "Administrador pronto: {$email}\n";
}

echo "Instalação concluída.\n";
