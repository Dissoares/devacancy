<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Avisa Bing, Yandex e outros buscadores compatíveis (protocolo IndexNow) assim que há páginas novas, em vez de
 * esperar o robô passar. O Google não participa: ele descobre pelo sitemap e pelo Search Console.
 * A chave é gerada sozinha e o arquivo dela fica em /indexnow.txt.
 */
final class IndexNowService
{
    private const ENDERECO = 'https://api.indexnow.org/IndexNow';
    private const LOTE = 5000;

    /** Chave estável do site (32 caracteres). Pode ser trocada em Configurações com INDEXNOW_CHAVE. */
    public function chave(): string
    {
        $configurada = preg_replace('/[^a-zA-Z0-9]/', '', (string) configuracao('seo.indexnow_chave'));
        return $configurada !== '' ? substr($configurada, 0, 64) : substr(hash('sha256', 'indexnow|' . configuracao('banco.nome') . '|' . configuracao('app.nome')), 0, 32);
    }

    /** @param string[] $urls endereços completos das páginas novas ou atualizadas */
    public function enviar(array $urls): bool
    {
        $host = parse_url(endereco_do_site(), PHP_URL_HOST);
        // Em máquina local ou sem endereço público não há o que avisar.
        if (ambiente_local() || !$host || $urls === []) {
            return false;
        }

        $enviou = true;
        foreach (array_chunk(array_values(array_unique($urls)), self::LOTE) as $lote) {
            $curl = curl_init(self::ENDERECO);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
                CURLOPT_POSTFIELDS => json_encode([
                    'host' => $host,
                    'key' => $this->chave(),
                    'keyLocation' => url('/indexnow.txt'),
                    'urlList' => $lote,
                ], JSON_UNESCAPED_SLASHES),
            ]);
            curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            // 200 e 202 são aceitos; qualquer outro código só entra no registro de erros.
            if ($status !== 200 && $status !== 202) {
                $enviou = false;
                error_log(sprintf("[%s] IndexNow respondeu %d\n", date('Y-m-d H:i:s'), $status), 3, RAIZ . '/storage/logs/erros.log');
            }
        }
        return $enviou;
    }
}
