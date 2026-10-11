<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Idioma;
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

/** Texto traduzido para o idioma atual (arquivos em lang/). */
function t(string $chave, array $parametros = []): string
{
    return Idioma::texto($chave, $parametros);
}

/** Valor em dinheiro no formato do idioma atual: R$ 49,90 ou $9.99. */
function moeda(float $valor, string $codigo = 'BRL', int $casas = 2): string
{
    $simbolo = ['BRL' => 'R$ ', 'USD' => '$', 'EUR' => '€'][$codigo] ?? $codigo . ' ';
    return Idioma::atual() === 'pt'
        ? $simbolo . number_format($valor, $casas, ',', '.')
        : $simbolo . number_format($valor, $casas, '.', ',');
}

function numero(int|float $valor): string
{
    return Idioma::atual() === 'pt' ? number_format($valor, 0, ',', '.') : number_format($valor, 0, '.', ',');
}

/** Textos usados pelos scripts (avisos, rótulos de botões), no idioma atual. */
function textos_js(): array
{
    $chaves = ['sessao_expirada', 'erro_generico', 'sem_conexao', 'mostrar_senha', 'ocultar_senha', 'remover', 'adicionar_mais', 'limite_arquivo', 'pronto_para_enviar'];
    return array_combine($chaves, array_map(fn (string $chave) => t('js.' . $chave), $chaves));
}

/** Link para a página atual em outro idioma. */
function url_idioma(string $idioma): string
{
    $consulta = $_GET;
    $consulta['lang'] = $idioma;
    return (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') . '?' . http_build_query($consulta);
}

/** "São Paulo" vira "Sao Paulo" (o iconv com TRANSLIT gera "S~ao Paulo" no Windows). */
function sem_acentos(string $texto): string
{
    if (class_exists(Normalizer::class)) {
        $decomposto = Normalizer::normalize($texto, Normalizer::FORM_D);
        if ($decomposto !== false) {
            return preg_replace('/\p{Mn}+/u', '', $decomposto);
        }
    }
    return strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'í' => 'i', 'ì' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
        'Ú' => 'U', 'Ç' => 'C', 'Ñ' => 'N',
    ]);
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

/** Ícone oficial de uma tecnologia (Devicon), ou string vazia se não houver. */
function icone_stack(string $tecnologia, string $classe = 'icone-stack'): string
{
    $arquivo = \App\Core\IconesStack::arquivo($tecnologia);
    return $arquivo === null ? '' : icone_stack_arquivo($arquivo, $classe);
}

function icone_stack_arquivo(string $arquivo, string $classe = 'icone-stack'): string
{
    return '<img class="' . e($classe) . '" src="' . e(asset('img/stacks/' . $arquivo . '.svg')) . '" alt="'
        . e(\App\Core\IconesStack::nome($arquivo)) . '" title="' . e(\App\Core\IconesStack::nome($arquivo)) . '" width="16" height="16" loading="lazy" decoding="async">';
}

/** Fileira de ícones de uma stack (com a linguagem implícita: Spring → Java, .NET → C#). */
function icones_stack(array $stack, int $limite = 4): string
{
    $arquivos = \App\Core\IconesStack::daStack($stack, $limite);
    if ($arquivos === []) {
        return '';
    }
    return '<span class="icones-stack">' . implode('', array_map(fn (string $arquivo) => icone_stack_arquivo($arquivo), $arquivos)) . '</span>';
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
    $formato = Idioma::atual() === 'pt' ? ($comHora ? 'd/m/Y \à\s H:i' : 'd/m/Y') : ($comHora ? 'M j, Y \a	 g:i A' : 'M j, Y');
    return date($formato, strtotime($data));
}

/** "há 3 h", "há 2 dias" (ou "3 h ago"). */
function tempo_relativo(string $data): string
{
    $segundos = max(0, time() - strtotime($data));
    return match (true) {
        $segundos < 3600 => t('tempo.minutos', ['n' => max(1, intdiv($segundos, 60))]),
        $segundos < 86400 => t('tempo.horas', ['n' => intdiv($segundos, 3600)]),
        default => t(intdiv($segundos, 86400) === 1 ? 'tempo.dia' : 'tempo.dias', ['n' => intdiv($segundos, 86400)]),
    };
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
