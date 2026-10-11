<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/** Consultas agregadas para o painel de analytics do admin (funil, receita e segmentação). */
final class AnalyticsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function funil(): array
    {
        $linha = $this->pdo->query(
            "SELECT
                SUM(u.tipo = 'profissional') AS cadastros,
                SUM(u.tipo = 'profissional' AND u.cadastro_completo = 1) AS perfis_completos,
                SUM(u.tipo = 'profissional' AND a.usuario_id IS NOT NULL) AS assinantes
             FROM usuarios u
             LEFT JOIN assinaturas a ON a.usuario_id = u.id AND a.expira_em > NOW()"
        )->fetch();

        return array_map('intval', $linha ?: []);
    }

    public function receita(): array
    {
        $linha = $this->pdo->query(
            "SELECT
                SUM(IF(status = 'pago', valor, 0)) AS total,
                SUM(IF(status = 'pago' AND pago_em >= NOW() - INTERVAL 30 DAY, valor, 0)) AS ultimos_30_dias
             FROM pagamentos WHERE moeda = 'BRL'"
        )->fetch();

        $porMetodo = $this->pdo->query(
            "SELECT metodo, COUNT(*) AS quantidade, SUM(valor) AS total
             FROM pagamentos WHERE status = 'pago'
             GROUP BY metodo ORDER BY total DESC"
        )->fetchAll();

        return [
            'total' => (float) ($linha['total'] ?? 0),
            'ultimos_30_dias' => (float) ($linha['ultimos_30_dias'] ?? 0),
            'por_metodo' => $porMetodo,
        ];
    }

    /** Taxa de conversão (cadastro -> assinante) por valor de uma coluna de perfis_profissionais (senioridade, area, modelo_trabalho, regiao_vagas...). */
    public function conversaoPorColuna(string $coluna): array
    {
        $consulta = $this->pdo->query(sprintf(
            "SELECT pp.%1\$s AS valor,
                    COUNT(*) AS cadastros,
                    SUM(a.usuario_id IS NOT NULL) AS assinantes
             FROM perfis_profissionais pp
             JOIN usuarios u ON u.id = pp.usuario_id AND u.tipo = 'profissional'
             LEFT JOIN assinaturas a ON a.usuario_id = pp.usuario_id AND a.expira_em > NOW()
             WHERE pp.%1\$s IS NOT NULL AND pp.%1\$s <> ''
             GROUP BY pp.%1\$s
             ORDER BY cadastros DESC",
            $coluna,
        ))->fetchAll();

        foreach ($consulta as &$linha) {
            $linha['cadastros'] = (int) $linha['cadastros'];
            $linha['assinantes'] = (int) $linha['assinantes'];
            $linha['taxa'] = $linha['cadastros'] > 0 ? round($linha['assinantes'] / $linha['cadastros'] * 100, 1) : 0.0;
        }

        return $consulta;
    }

    /** Conversão por tecnologia da stack (coluna JSON, por isso agregada em PHP em vez de SQL). */
    public function conversaoPorStack(int $limite = 10): array
    {
        $linhas = $this->pdo->query(
            "SELECT pp.stack, (a.usuario_id IS NOT NULL) AS assinante
             FROM perfis_profissionais pp
             JOIN usuarios u ON u.id = pp.usuario_id AND u.tipo = 'profissional'
             LEFT JOIN assinaturas a ON a.usuario_id = pp.usuario_id AND a.expira_em > NOW()"
        )->fetchAll();

        $porTecnologia = [];
        foreach ($linhas as $linha) {
            $tecnologias = json_decode($linha['stack'] ?: '[]', true) ?: [];
            foreach ($tecnologias as $tecnologia) {
                $porTecnologia[$tecnologia] ??= ['cadastros' => 0, 'assinantes' => 0];
                $porTecnologia[$tecnologia]['cadastros']++;
                $porTecnologia[$tecnologia]['assinantes'] += (int) $linha['assinante'];
            }
        }

        uasort($porTecnologia, fn (array $a, array $b) => $b['cadastros'] <=> $a['cadastros']);
        $porTecnologia = array_slice($porTecnologia, 0, $limite, true);

        $resultado = [];
        foreach ($porTecnologia as $tecnologia => $dados) {
            $resultado[] = [
                'valor' => $tecnologia,
                'cadastros' => $dados['cadastros'],
                'assinantes' => $dados['assinantes'],
                'taxa' => $dados['cadastros'] > 0 ? round($dados['assinantes'] / $dados['cadastros'] * 100, 1) : 0.0,
            ];
        }

        return $resultado;
    }
}
