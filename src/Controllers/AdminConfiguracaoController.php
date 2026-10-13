<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\ConfiguracaoService;

/** Configurações do site (pagamentos, e-mail, login social...) editadas no painel, sem mexer no .env. */
final class AdminConfiguracaoController
{
    public function __construct(
        private readonly ConfiguracaoService $configuracoes,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function painel(): Resposta
    {
        return $this->pagina([], null, (string) Sessao::obterFlash('grupo_salvo'));
    }

    public function salvar(Requisicao $requisicao): Resposta
    {
        $grupo = (string) $requisicao->parametro('grupo');

        try {
            $this->configuracoes->salvar($grupo, $requisicao->todos());
        } catch (ValidacaoException $erro) {
            return $this->pagina($erro->erros(), $grupo, '', 422);
        }

        Sessao::flash('grupo_salvo', $grupo);
        return Resposta::redirecionar('/admin/configuracoes#' . $grupo);
    }

    private function pagina(array $erros, ?string $grupoComErro, string $grupoSalvo, int $status = 200): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/configuracoes', [
            'titulo' => 'Configurações — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'grupos' => $this->configuracoes->grupos(),
            'erros' => $erros,
            'grupoComErro' => $grupoComErro,
            'grupoSalvo' => $grupoSalvo,
        ], 'app'), $status);
    }
}
