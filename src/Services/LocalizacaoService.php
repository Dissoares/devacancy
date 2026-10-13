<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ClienteHttp;
use App\Core\Idioma;
use App\Core\Requisicao;
use App\Core\Sessao;
use App\Entities\PerfilProfissional;
use RuntimeException;

/**
 * Descobre onde a pessoa está pela geolocalização do IP (ipwho.is), guardando o resultado na sessão.
 * O país decide moeda e meios de pagamento; a cidade aparece no questionário e na tela de vagas.
 * Em ambiente local (IP 127.0.0.1) dá para simular outro país com ?pais=US.
 */
final class LocalizacaoService
{
    private const CHAVE_SESSAO = 'localizacao';

    public function __construct(private readonly ClienteHttp $http)
    {
    }

    /** @return array{cidade: ?string, estado: ?string, pais: ?string} */
    public function detectar(Requisicao $requisicao): array
    {
        $simulado = strtoupper((string) $requisicao->consulta('pais', ''));
        if (ambiente_local() && preg_match('/^[A-Z]{2}$/', $simulado)) {
            Sessao::definir(self::CHAVE_SESSAO, ['ip' => $requisicao->ip(), 'cidade' => null, 'estado' => null, 'pais' => $simulado]);
        }

        $salvo = Sessao::obter(self::CHAVE_SESSAO);
        if (is_array($salvo) && $salvo['ip'] === $requisicao->ip()) {
            return ['cidade' => $salvo['cidade'], 'estado' => $salvo['estado'], 'pais' => $salvo['pais']];
        }

        $local = $this->consultarIp($requisicao->ip());
        // O país que a Cloudflare já conferiu vale mais que a consulta externa (e funciona se ela estiver fora do ar).
        $paisConfiavel = $requisicao->cabecalho('x-pais-confiavel');
        if ($paisConfiavel !== null) {
            $local['pais'] = $paisConfiavel;
        }
        Sessao::definir(self::CHAVE_SESSAO, ['ip' => $requisicao->ip(), ...$local]);
        return $local;
    }

    /** Código do país (BR, US…). Sem geolocalização, deduz pelo idioma: português → Brasil. */
    public function pais(Requisicao $requisicao): string
    {
        return $this->detectar($requisicao)['pais'] ?? (Idioma::atual() === 'pt' ? 'BR' : 'US');
    }

    /**
     * Se a pessoa paga em reais: está no Brasil E usa o site em português. Só nesse caso aparecem os preços em R$ e as
     * formas que só aceitam real (PIX manual, PIX automático e Asaas). Quem usa o site em inglês vê dólar, com Stripe e PayPal.
     * As vagas mostradas continuam seguindo só o país (noBrasil).
     */
    public function pagaEmReais(Requisicao $requisicao): bool
    {
        return $this->noBrasil($requisicao) && Idioma::atual() === 'pt';
    }

    public function noBrasil(Requisicao $requisicao): bool
    {
        return $this->pais($requisicao) === 'BR';
    }

    /** Ex.: "São Paulo, SP". Prefere a cidade informada pela pessoa; null quando não dá para saber. */
    public function localizar(Requisicao $requisicao, ?PerfilProfissional $perfil): ?string
    {
        if ($perfil?->cidade) {
            return $perfil->cidade;
        }

        $local = $this->detectar($requisicao);
        return $local['cidade'] ? $local['cidade'] . ($local['estado'] ? ', ' . $local['estado'] : '') : null;
    }

    /** Onde a pessoa está, para mostrar na tela: "Blumenau, SC" ou, sem a cidade, o nome do país ("Brasil"). */
    public function descricao(Requisicao $requisicao): ?string
    {
        $local = $this->detectar($requisicao);
        if ($local['cidade']) {
            return $local['cidade'] . ($local['estado'] ? ', ' . $local['estado'] : '');
        }

        $pais = $this->pais($requisicao);
        $nome = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-' . $pais, Idioma::atual()) : '';
        return $nome !== '' && $nome !== $pais ? $nome : $pais;
    }

    /** @return array{cidade: ?string, estado: ?string, pais: ?string} */
    private function consultarIp(string $ip): array
    {
        $vazio = ['cidade' => null, 'estado' => null, 'pais' => null];
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $vazio;
        }

        try {
            $dados = $this->http->get('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,city,region_code,country,country_code', [], 3);
        } catch (RuntimeException) {
            return $vazio;
        }

        if (empty($dados['success'])) {
            return $vazio;
        }

        $pais = strtoupper((string) ($dados['country_code'] ?? '')) ?: null;
        return [
            'cidade' => $dados['city'] ?? null,
            'estado' => $pais === 'BR' ? ($dados['region_code'] ?? null) : ($dados['country'] ?? null),
            'pais' => $pais,
        ];
    }
}
