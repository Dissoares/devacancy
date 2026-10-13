<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Conversões para o Pixel da Meta (Facebook/Instagram) e para o Google Analytics 4.
 * Os códigos ficam em Admin → Configurações → Site; sem eles nada é carregado nem enviado.
 * O navegador só carrega as ferramentas depois que a pessoa aceita os cookies (public/assets/js/componentes/rastreamento.js).
 *
 * Cada evento tem a forma: ['id' => string, 'meta' => [nome, dados], 'ga' => [nome, dados]].
 */
final class Rastreamento
{
    /** Eventos para a página que está sendo montada agora. */
    private static array $agora = [];
    /** Eventos que vão na próxima página (por exemplo, depois do cadastro, que termina num redirecionamento). */
    private static array $proxima = [];

    /** Código do Pixel: só dígitos, porque vai para dentro de um script. */
    public static function pixel(): string
    {
        $codigo = trim((string) configuracao('rastreamento.meta_pixel'));
        return preg_match('/^\d{8,20}$/', $codigo) ? $codigo : '';
    }

    /** ID de métricas do Google Analytics 4 (G-XXXXXXXXXX). */
    public static function analytics(): string
    {
        $codigo = strtoupper(trim((string) configuracao('rastreamento.ga')));
        return preg_match('/^G-[A-Z0-9]{6,14}$/', $codigo) ? $codigo : '';
    }

    public static function ativo(): bool
    {
        return self::pixel() !== '' || self::analytics() !== '';
    }

    public static function agora(array $evento): void
    {
        self::$agora[] = $evento;
    }

    public static function naProximaPagina(array $evento): void
    {
        self::$proxima[] = $evento;
        Sessao::flash('rastreio', self::$proxima);
    }

    /** Todos os eventos que a página atual deve avisar: os do pedido anterior, os de agora e as compras novas. */
    public static function eventosDaPagina(): array
    {
        if (!self::ativo()) {
            return [];
        }
        return [...(array) Sessao::obterFlash('rastreio', []), ...self::$agora, ...self::comprasNovas()];
    }

    public static function cadastro(string $metodo): array
    {
        return [
            'id' => 'cadastro-' . bin2hex(random_bytes(6)),
            'meta' => ['CompleteRegistration', ['status' => true, 'content_name' => $metodo]],
            'ga' => ['sign_up', ['method' => $metodo]],
        ];
    }

    /** Terminou o questionário e viu as vagas: o sinal de interesse mais forte antes de criar a conta. */
    public static function interesse(): array
    {
        return ['id' => 'interesse-' . bin2hex(random_bytes(6)), 'meta' => ['Lead', []], 'ga' => ['generate_lead', []]];
    }

    public static function inicioDeCheckout(float $valor, string $moeda): array
    {
        $dados = ['value' => $valor, 'currency' => $moeda];
        return ['id' => 'checkout-' . bin2hex(random_bytes(6)), 'meta' => ['InitiateCheckout', $dados], 'ga' => ['begin_checkout', $dados]];
    }

    /** @param array{id: int|string, plano: string, valor: string|float, moeda: string} $pagamento */
    public static function compra(array $pagamento): array
    {
        $valor = (float) $pagamento['valor'];
        $moeda = (string) $pagamento['moeda'];
        $id = 'pagamento-' . $pagamento['id'];

        return [
            'id' => $id,
            // O mesmo "id" nos dois lados evita contar a compra duas vezes se um dia houver envio pelo servidor.
            'meta' => ['Purchase', ['value' => $valor, 'currency' => $moeda, 'content_name' => (string) $pagamento['plano'], 'content_type' => 'product']],
            'ga' => ['purchase', ['transaction_id' => $id, 'value' => $valor, 'currency' => $moeda, 'items' => [['item_id' => (string) $pagamento['plano'], 'item_name' => (string) $pagamento['plano'], 'price' => $valor, 'quantity' => 1]]]],
        ];
    }

    /**
     * Pagamentos concluídos desta pessoa que ainda não foram avisados. A confirmação pode chegar quando ela não está no
     * site (webhook, PIX conferido à mão), então o aviso sai na primeira visita depois, uma única vez por pagamento.
     * @return array<int, array>
     */
    private static function comprasNovas(): array
    {
        $usuario = usuario_sessao();
        if (!is_array($usuario) || ($usuario['tipo'] ?? '') === 'admin' || empty($usuario['id'])) {
            return [];
        }

        try {
            $pdo = Conexao::obter();
            $consulta = $pdo->prepare("SELECT id, plano, valor, moeda FROM pagamentos WHERE usuario_id = ? AND status = 'pago' AND rastreado_em IS NULL ORDER BY id LIMIT 5");
            $consulta->execute([(int) $usuario['id']]);
            $linhas = $consulta->fetchAll();
            if ($linhas === []) {
                return [];
            }

            $pdo->prepare('UPDATE pagamentos SET rastreado_em = NOW() WHERE id IN (' . implode(',', array_map(fn (array $l) => (int) $l['id'], $linhas)) . ')')->execute();
            return array_map(fn (array $linha) => self::compra($linha), $linhas);
        } catch (Throwable) {
            // Rastreamento nunca pode derrubar uma página (ex.: a atualização do banco ainda não foi rodada).
            return [];
        }
    }
}
