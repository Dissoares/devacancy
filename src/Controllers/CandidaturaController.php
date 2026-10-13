<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Entities\PerfilProfissional;
use App\Enums\StatusCandidatura;
use App\Services\AssinaturaService;
use App\Services\AutenticacaoService;
use App\Services\CandidaturaService;
use App\Services\UsuarioService;
use App\Services\VagaService;

/** "Candidatar-me" no painel do profissional e a fila de envio da equipe. */
final class CandidaturaController
{
    public function __construct(
        private readonly CandidaturaService $candidaturas,
        private readonly AssinaturaService $assinaturas,
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
        private readonly VagaService $vagas,
    ) {
    }

    /** Uma vaga (vaga_id) ou todas as compatíveis (vaga_ids[]). Sem assinatura, reserva e leva para os planos. */
    public function candidatar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $perfil = $this->usuarios->perfilDe($usuario);
        if (!$perfil instanceof PerfilProfissional) {
            return Resposta::redirecionarHtmx('/comecar');
        }

        $ids = array_filter([(int) $requisicao->entrada('vaga_id', 0), ...array_map('intval', (array) $requisicao->entrada('vaga_ids', []))]);
        $resultado = $this->candidaturas->candidatar($usuario, $perfil, $ids);

        if ($this->assinaturas->vigenteAte($usuario) === null) {
            return Resposta::redirecionarHtmx('/planos');
        }

        // Passou do limite do dia: avisa no painel, sem mexer nas vagas que não entraram.
        $limite = $this->candidaturas->limiteDiario();
        if ($resultado['limitou']) {
            Sessao::flash('aviso', t($resultado['criadas'] > 0 ? 'candidatura.limite_parcial' : 'candidatura.limite_atingido', ['n' => $resultado['criadas'], 'limite' => $limite]));
            return Resposta::redirecionarHtmx('/painel');
        }

        // Uma vaga só, pelo HTMX: devolve o próprio cartão já com o status, sem recarregar o painel.
        $vagaId = (int) $requisicao->entrada('vaga_id', 0);
        if ($vagaId > 0 && $requisicao->ehHtmx()) {
            foreach ($this->vagas->compativeis($perfil) as $item) {
                if ($item['vaga']->id === $vagaId) {
                    return Resposta::html(Visao::parcial('partials/cartao-vaga', [
                        'vaga' => $item['vaga'],
                        'compatibilidade' => $item['compatibilidade'],
                        'proxima' => $item['proxima'] ?? false,
                        'bloqueada' => false,
                        'comAcao' => true,
                        'status' => $this->candidaturas->statusPorVaga($usuario)[$vagaId] ?? null,
                        'recemEnviada' => true,
                    ]));
                }
            }
        }

        Sessao::flash('sucesso', t('candidatura.adicionadas'));
        return Resposta::redirecionarHtmx('/painel');
    }

    public function fila(Requisicao $requisicao): Resposta
    {
        $status = StatusCandidatura::tryFrom((string) $requisicao->consulta('status', StatusCandidatura::NaFila->value));
        $todas = $requisicao->consulta('todas') === '1';

        return Resposta::html(Visao::renderizar('admin/candidaturas', [
            'titulo' => 'Candidaturas — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'itens' => $this->candidaturas->listarParaEquipe($status, !$todas),
            'contagem' => $this->candidaturas->contarPorStatus(),
            'statusAtual' => $status,
            'todas' => $todas,
        ], 'admin'));
    }

    public function atualizar(Requisicao $requisicao): Resposta
    {
        $status = StatusCandidatura::tryFrom((string) $requisicao->entrada('status', ''));
        if ($status !== null) {
            $this->candidaturas->atualizarStatus((int) $requisicao->parametro('id'), $status);
        }

        return Resposta::redirecionar('/admin/candidaturas?status=' . rawurlencode((string) $requisicao->entrada('voltar', 'na_fila')));
    }
}
