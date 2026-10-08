<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\Pagamento;
use App\Entities\PerfilProfissional;
use App\Entities\Usuario;
use App\Enums\StatusPagamento;
use App\Exceptions\ValidacaoException;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;
use App\Services\UsuarioService;
use App\Services\VagaService;

/** Funil de conversão do profissional: vagas compatíveis → planos → PIX → assinatura ativa. */
final class AssinaturaController
{
    public function __construct(
        private readonly AssinaturaService $assinaturas,
        private readonly VagaService $vagas,
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    /** Tela logo depois do cadastro: "N vagas compatíveis com o seu perfil". */
    public function vagasCompativeis(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $perfil = $this->usuarios->perfilDe($usuario);

        if (!$perfil instanceof PerfilProfissional || $this->assinaturas->vigenteAte($usuario)) {
            return Resposta::redirecionar('/painel');
        }

        $compativeis = $this->vagas->compativeis($perfil);

        return Resposta::html(Visao::renderizar('assinatura/vagas-compativeis', [
            'titulo' => 'Vagas compatíveis — Devacancy',
            'usuario' => $usuario,
            'perfil' => $perfil,
            'compativeis' => $compativeis,
            'recentes' => $this->vagas->contarRecentes($compativeis),
        ], 'app'));
    }

    public function planos(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        if (!$usuario->ehProfissional()) {
            return Resposta::redirecionar('/painel');
        }

        return Resposta::html(Visao::renderizar('assinatura/planos', [
            'titulo' => 'Planos — Devacancy',
            ...$this->dadosDosPlanos($usuario),
            'valores' => ['plano' => (string) $requisicao->consulta('plano', 'trimestral')],
            'erros' => [],
        ], 'app'));
    }

    public function assinar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $dados = $requisicao->todos();

        try {
            $pagamento = $this->assinaturas->iniciarPagamento($usuario, $dados);
        } catch (ValidacaoException $erro) {
            return Resposta::html(Visao::parcial('assinatura/partials/formulario-plano', [
                ...$this->dadosDosPlanos($usuario),
                'valores' => $dados,
                'erros' => $erro->erros(),
            ]));
        }

        return Resposta::redirecionarHtmx('/pagamento/' . $pagamento->id);
    }

    public function pagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $pagamento = $this->assinaturas->sincronizar(
            $this->assinaturas->pagamentoDoUsuario($usuario, (int) $requisicao->parametro('id'))
        );

        return Resposta::html(Visao::renderizar('assinatura/pagamento', [
            'titulo' => 'Pagamento — Devacancy',
            'usuario' => $usuario,
            'pagamento' => $pagamento,
            'plano' => $this->assinaturas->planos()[$pagamento->plano] ?? null,
            'simulacao' => $this->assinaturas->modoSimulacao(),
        ], 'app'));
    }

    /** Consultado pelo HTMX a cada poucos segundos enquanto o PIX está pendente. */
    public function statusPagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $pagamento = $this->assinaturas->sincronizar(
            $this->assinaturas->pagamentoDoUsuario($usuario, (int) $requisicao->parametro('id'))
        );

        return $this->respostaDeStatus($pagamento);
    }

    public function simularPagamento(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        return $this->respostaDeStatus($this->assinaturas->simularPagamento($usuario, (int) $requisicao->parametro('id')));
    }

    public function webhook(Requisicao $requisicao): Resposta
    {
        $token = (string) configuracao('expay.token_webhook');
        if ($token === '' || !hash_equals($token, (string) $requisicao->consulta('token', ''))) {
            return Resposta::texto('Não autorizado.', 401);
        }

        $this->assinaturas->processarWebhook($requisicao->json());
        return Resposta::texto('ok');
    }

    private function respostaDeStatus(Pagamento $pagamento): Resposta
    {
        if ($pagamento->status === StatusPagamento::Pago) {
            Sessao::flash('sucesso', 'Pagamento confirmado! Sua assinatura está ativa e as candidaturas já entraram na fila.');
            return Resposta::redirecionarHtmx('/painel');
        }

        return Resposta::html(Visao::parcial('assinatura/partials/status-pagamento', [
            'pagamento' => $pagamento,
            'simulacao' => $this->assinaturas->modoSimulacao(),
        ]));
    }

    private function dadosDosPlanos(Usuario $usuario): array
    {
        $perfil = $this->usuarios->perfilDe($usuario);

        return [
            'usuario' => $usuario,
            'planos' => $this->assinaturas->planos(),
            'totalCompativeis' => $perfil instanceof PerfilProfissional ? count($this->vagas->compativeis($perfil)) : 0,
            'vigenteAte' => $this->assinaturas->vigenteAte($usuario),
        ];
    }
}
