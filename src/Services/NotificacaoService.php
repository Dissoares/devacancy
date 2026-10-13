<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Email;
use App\Core\Idioma;
use App\Core\Visao;
use App\Entities\Usuario;
use Throwable;

/**
 * E-mails do sistema para o cliente: boas-vindas, assinatura confirmada e recuperação de senha. Todos usam a mesma
 * moldura (views/layouts/emails.php) e saem no idioma da pessoa. Falha de envio nunca derruba a ação que gerou o e-mail
 * (cadastro, pagamento): só vai para o registro de erros.
 */
final class NotificacaoService
{
    public function __construct(private readonly Email $email)
    {
    }

    /** @param ?int $vagas quantas vagas já combinam com o perfil, quando for possível saber */
    public function boasVindas(Usuario $usuario, ?int $vagas = null): void
    {
        $this->enviar($usuario, function () use ($usuario, $vagas): array {
            $nome = $this->primeiroNome($usuario);
            return [
                t('email.boas_assunto', ['app' => (string) configuracao('email.nome')]),
                t('email.boas_previa'),
                Visao::parcial('emails/boas-vindas', ['nome' => $nome, 'vagas' => $vagas]),
                implode("\n\n", array_filter([
                    t('email.boas_titulo', ['nome' => $nome]),
                    t('email.boas_texto'),
                    $vagas ? numero($vagas) . ' ' . t($vagas === 1 ? 'email.boas_vagas_um' : 'email.boas_vagas_varios') . '. ' . t('email.boas_vagas_nota') : null,
                    t('email.boas_como') . "\n1. " . t('email.boas_passo_1') . "\n2. " . t('email.boas_passo_2') . "\n3. " . t('email.boas_passo_3'),
                    t('email.boas_botao') . ': ' . url('/painel'),
                    t('email.boas_nota'),
                ])),
            ];
        });
    }

    public function assinaturaConfirmada(Usuario $usuario, string $plano, float $valor, string $moeda, string $metodo, ?string $validoAte, bool $renovacao, ?int $pedido = null): void
    {
        $this->enviar($usuario, function () use ($usuario, $plano, $valor, $moeda, $metodo, $validoAte, $renovacao, $pedido): array {
            $dados = [
                'nome' => $this->primeiroNome($usuario),
                'renovacao' => $renovacao,
                'plano' => t('plano.' . $plano),
                'valor' => moeda($valor, $moeda),
                'forma' => t('email.forma_' . $metodo),
                'data' => data_br(date('Y-m-d H:i:s')),
                'validoAte' => $validoAte ? data_br($validoAte) : '—',
                'pedido' => $pedido ? '#' . $pedido : null,
            ];
            $linhas = [t('email.ass_plano') . ': ' . $dados['plano'], t('email.ass_valor') . ': ' . $dados['valor'], t('email.ass_forma') . ': ' . $dados['forma'], t('email.ass_data') . ': ' . $dados['data'], t('email.ass_valido_ate') . ': ' . $dados['validoAte']];

            return [
                t($renovacao ? 'email.ass_assunto_renovacao' : 'email.ass_assunto', ['app' => (string) configuracao('email.nome')]),
                t('email.ass_previa', ['plano' => $dados['plano'], 'data' => $dados['validoAte']]),
                Visao::parcial('emails/assinatura', $dados),
                implode("\n\n", [
                    t($renovacao ? 'email.ass_titulo_renovacao' : 'email.ass_titulo', ['nome' => $dados['nome']]),
                    t($renovacao ? 'email.ass_texto_renovacao' : 'email.ass_texto', ['plano' => $dados['plano']]),
                    t('email.ass_comprovante') . "\n" . implode("\n", $linhas),
                    t('email.ass_botao') . ': ' . url('/painel'),
                    t('email.ass_nota'),
                ]),
            ];
        });
    }

    /** Fica no idioma da requisição: quem pede a nova senha está vendo o site nele. */
    public function recuperacaoDeSenha(Usuario $usuario, string $link): void
    {
        $this->enviar($usuario, function () use ($usuario, $link): array {
            $app = (string) configuracao('email.nome');
            return [
                t('recuperar.email_assunto', ['app' => $app]),
                t('recuperar.email_aviso'),
                Visao::parcial('emails/senha', ['nome' => $this->primeiroNome($usuario), 'link' => $link]),
                t('recuperar.email_texto', ['link' => $link, 'app' => $app]),
            ];
        }, false);
    }

    /**
     * Monta o e-mail no idioma do cliente e envia.
     * @param callable(): array{0: string, 1: string, 2: string, 3: string} $montar assunto, prévia, conteúdo HTML e texto simples
     */
    private function enviar(Usuario $usuario, callable $montar, bool $noIdiomaDoUsuario = true): void
    {
        $idiomaAtual = Idioma::atual();
        try {
            if ($noIdiomaDoUsuario && in_array($usuario->idioma, ['pt', 'en'], true)) {
                Idioma::definir($usuario->idioma);
            }
            [$assunto, $previa, $conteudo, $texto] = $montar();
            $html = Visao::renderizar('layouts/emails', ['assunto' => $assunto, 'previa' => $previa, 'conteudo' => $conteudo]);
            $this->email->enviar($usuario->email, $assunto, $texto, $html);
        } catch (Throwable $erro) {
            error_log(sprintf("[%s] E-mail para %s não enviado: %s\n", date('Y-m-d H:i:s'), $usuario->email, $erro->getMessage()), 3, RAIZ . '/storage/logs/erros.log');
        } finally {
            Idioma::definir($idiomaAtual);
        }
    }

    private function primeiroNome(Usuario $usuario): string
    {
        return trim(explode(' ', trim($usuario->nome))[0]) ?: $usuario->nome;
    }
}
