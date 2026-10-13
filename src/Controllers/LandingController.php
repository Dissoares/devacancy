<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Idioma;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;
use App\Services\LocalizacaoService;
use App\Services\VagaService;

final class LandingController
{
    public function __construct(
        private readonly VagaService $vagas,
        private readonly LocalizacaoService $localizacao,
    ) {
    }

    public function inicio(Requisicao $requisicao): Resposta
    {
        $noBrasil = $this->localizacao->noBrasil($requisicao);

        return Resposta::html(Visao::renderizar('landing/inicio', [
            'titulo' => t('landing.titulo_pagina'),
            'descricao' => t('landing.descricao_pagina'),
            'classeCorpo' => 'pagina-landing',
            'urlCanonica' => url('/'),
            'imagemAlt' => t('landing.og_alt'),
            'dadosEstruturados' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    ['@type' => 'Organization', 'name' => 'Devacancy', 'url' => url('/'), 'logo' => url('/assets/img/og-padrao.png')],
                    ['@type' => 'WebSite', 'name' => 'Devacancy', 'url' => url('/'), 'inLanguage' => Idioma::atual() === 'pt' ? 'pt-BR' : 'en'],
                ],
            ],
            'vagasAbertas' => $this->vagas->contarAtivas($noBrasil),
            'vagasNovas' => $this->vagas->contarNovas($noBrasil),
            'vagasRecentes' => $this->vagas->recentes($noBrasil, 8),
            'planos' => configuracao('planos'),
            'moeda' => $this->localizacao->pagaEmReais($requisicao) ? 'BRL' : 'USD',
        ], 'site'));
    }

    /** Vitrine "ao vivo" (HTMX): a própria lista se recarrega de tempos em tempos com as vagas mais recentes. */
    public function vitrine(Requisicao $requisicao): Resposta
    {
        $noBrasil = $this->localizacao->noBrasil($requisicao);

        return Resposta::html(Visao::parcial('partials/vitrine-vagas', [
            'vagas' => $this->vagas->recentes($noBrasil, 8),
            'novas' => $this->vagas->contarNovas($noBrasil),
        ]));
    }
}
