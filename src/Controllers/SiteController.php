<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Idioma;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;
use App\Services\VagaService;

/** Páginas institucionais (privacidade e termos) e arquivos para buscadores (robots.txt e sitemap.xml). */
final class SiteController
{
    /** Data da última revisão dos textos legais. */
    private const ATUALIZADO_EM = '2026-10-12';

    public function __construct(private readonly VagaService $vagas)
    {
    }

    public function privacidade(Requisicao $requisicao): Resposta
    {
        return $this->documento('privacidade', t('legal.privacidade_titulo'));
    }

    public function termos(Requisicao $requisicao): Resposta
    {
        return $this->documento('termos', t('legal.termos_titulo'));
    }

    public function robots(): Resposta
    {
        $linhas = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /painel',
            'Disallow: /perfil',
            'Disallow: /planos',
            'Disallow: /pagamento',
            'Disallow: /assinatura',
            'Disallow: /assinar',
            'Disallow: /entrar',
            'Disallow: /criar-conta',
            'Disallow: /completar-perfil',
            'Disallow: /redefinir-senha',
            'Disallow: /auth/',
            'Disallow: /webhooks/',
            'Disallow: /install',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];
        return Resposta::texto(implode("\n", $linhas) . "\n");
    }

    /** Páginas públicas e uma entrada por vaga aberta (cada vaga tem a sua página, pensada para ser compartilhada e encontrada). */
    public function sitemap(): Resposta
    {
        $entradas = [];
        foreach (['/', '/comecar', '/cadastro?tipo=empresa', '/privacidade', '/termos'] as $caminho) {
            $entradas[] = ['loc' => url($caminho), 'mod' => null, 'prioridade' => $caminho === '/' ? '1.0' : '0.6'];
        }
        foreach ($this->vagas->listar(true) as $vaga) {
            $entradas[] = ['loc' => url_vaga($vaga), 'mod' => substr($vaga->criadoEm, 0, 10), 'prioridade' => '0.7'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entradas as $entrada) {
            $xml .= '  <url><loc>' . htmlspecialchars($entrada['loc'], ENT_XML1) . '</loc>'
                . ($entrada['mod'] ? '<lastmod>' . $entrada['mod'] . '</lastmod>' : '')
                . '<priority>' . $entrada['prioridade'] . "</priority></url>\n";
        }
        $xml .= "</urlset>\n";

        return Resposta::texto($xml)->comCabecalho('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function documento(string $tipo, string $titulo): Resposta
    {
        return Resposta::html(Visao::renderizar('legal/documento', [
            'titulo' => $titulo . ' — Devacancy',
            'descricao' => t('legal.' . $tipo . '_descricao'),
            'tituloDocumento' => $titulo,
            'conteudo_legal' => "legal/{$tipo}-" . Idioma::atual(),
            'atualizadoEm' => data_br(self::ATUALIZADO_EM),
        ], 'site'));
    }
}
