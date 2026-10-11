<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ClienteHttp
{
    private const TEMPO_LIMITE = 15;

    public function get(string $url, array $cabecalhos = [], int $tempoLimite = self::TEMPO_LIMITE): array
    {
        return $this->enviar('GET', $url, null, $cabecalhos, $tempoLimite);
    }

    /** Envia o corpo como application/x-www-form-urlencoded (padrão do OAuth). */
    public function postFormulario(string $url, array $campos, array $cabecalhos = []): array
    {
        return $this->enviar('POST', $url, http_build_query($campos), [
            'Content-Type: application/x-www-form-urlencoded',
            ...$cabecalhos,
        ]);
    }

    public function postJson(string $url, array $corpo, array $cabecalhos = []): array
    {
        return $this->enviar('POST', $url, json_encode($corpo, JSON_UNESCAPED_UNICODE), [
            'Content-Type: application/json',
            ...$cabecalhos,
        ]);
    }

    private function enviar(string $metodo, string $url, ?string $corpo, array $cabecalhos, int $tempoLimite = self::TEMPO_LIMITE): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_TIMEOUT => $tempoLimite,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Devacancy', ...$cabecalhos],
        ]);
        if ($corpo !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $corpo);
        }

        $resposta = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($resposta === false) {
            throw new RuntimeException("Falha na requisição HTTP para {$url}: {$erro}");
        }

        $dados = json_decode((string) $resposta, true);
        if ($status >= 400 || !is_array($dados)) {
            throw new RuntimeException("Resposta inesperada ({$status}) de {$url}: " . mb_substr((string) $resposta, 0, 300));
        }

        return $dados;
    }
}
