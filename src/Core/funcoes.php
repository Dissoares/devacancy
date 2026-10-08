<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Sessao;
use App\Core\Visao;

/** Lê config/app.php com notação de ponto: configuracao('banco.host'). */
function configuracao(string $chave, mixed $padrao = null): mixed
{
    static $config = null;
    $config ??= require RAIZ . '/config/app.php';

    $valor = $config;
    foreach (explode('.', $chave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $padrao;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $caminho = '/'): string
{
    if (preg_match('#^https?://#', $caminho)) {
        return $caminho;
    }
    return rtrim((string) configuracao('app.url'), '/') . '/' . ltrim($caminho, '/');
}

/** URL de arquivo estático com versão pela data de modificação (cache seguro). */
function asset(string $caminho): string
{
    $arquivo = RAIZ . '/public/assets/' . ltrim($caminho, '/');
    $versao = is_file($arquivo) ? filemtime($arquivo) : 0;
    return '/assets/' . ltrim($caminho, '/') . '?v=' . $versao;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/** Resumo do usuário logado guardado na sessão (id, nome, email, tipo, avatar). */
function usuario_sessao(): ?array
{
    return Sessao::obter('usuario');
}

function parcial(string $visao, array $dados = []): string
{
    return Visao::parcial($visao, $dados);
}

function icone(string $nome, string $classe = 'icone'): string
{
    return '<svg class="' . e($classe) . '" aria-hidden="true"><use href="#i-' . e($nome) . '"/></svg>';
}

function ativo(string $caminho): string
{
    $atual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return $atual === $caminho ? 'ativo' : '';
}

function data_br(?string $data, bool $comHora = false): string
{
    if (!$data) {
        return '—';
    }
    return date($comHora ? 'd/m/Y \à\s H:i' : 'd/m/Y', strtotime($data));
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome)) ?: [];
    $primeira = mb_substr($partes[0] ?? '', 0, 1);
    $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
}

/** Classe CSS do campo, marcando erro de validação quando houver. */
function classe_campo(array $erros, string $campo, string $extra = ''): string
{
    return trim('campo ' . $extra . (isset($erros[$campo]) ? ' campo-com-erro' : ''));
}

function erro_campo(array $erros, string $campo): string
{
    return isset($erros[$campo])
        ? '<span class="mensagem-erro">' . icone('x', 'icone icone-sm') . e($erros[$campo]) . '</span>'
        : '';
}
