<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\ConfiguracaoService;

/** Configurações do site (pagamentos, login social, e-mail...) editadas no painel, sem mexer no .env. */
final class AdminConfiguracaoController
{
    public function __construct(
        private readonly ConfiguracaoService $configuracoes,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function inicio(): Resposta
    {
        return Resposta::redirecionar('/admin/configuracoes/site');
    }

    /** Endereço antigo da tela de e-mail (links salvos continuam funcionando). */
    public function emailAntigo(): Resposta
    {
        return Resposta::redirecionar('/admin/configuracoes/email');
    }

    public function secao(Requisicao $requisicao): Resposta
    {
        return $this->pagina((string) $requisicao->parametro('secao'), [], null, (string) Sessao::obterFlash('cartao_salvo'));
    }

    public function salvar(Requisicao $requisicao): Resposta
    {
        $secao = (string) $requisicao->parametro('secao');
        $cartao = (string) $requisicao->parametro('cartao');

        try {
            $this->configuracoes->salvar($secao, $cartao, $requisicao->todos());
        } catch (ValidacaoException $erro) {
            return $this->pagina($secao, $erro->erros(), $cartao, '', 422);
        }

        Sessao::flash('cartao_salvo', $cartao);
        return Resposta::redirecionar("/admin/configuracoes/{$secao}#{$secao}-{$cartao}");
    }

    private function pagina(string $chaveSecao, array $erros, ?string $cartaoComErro, string $cartaoSalvo, int $status = 200): Resposta
    {
        if (!$this->configuracoes->existe($chaveSecao)) {
            throw new NaoEncontradoException();
        }

        $secao = $this->configuracoes->secao($chaveSecao);

        return Resposta::html(Visao::renderizar('admin/configuracoes', [
            'titulo' => $secao['titulo'] . ' — Admin',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'chaveSecao' => $chaveSecao,
            'secao' => $secao,
            'erros' => $erros,
            'cartaoComErro' => $cartaoComErro,
            'cartaoSalvo' => $cartaoSalvo,
            'teste' => $secao['extra'] === 'teste_email' ? Sessao::retirar('teste_email') : null,
            'leitura' => $secao['extra'] === 'teste_email' ? $this->configuracoes->leituraDoEmail() : null,
        ], 'admin'), $status);
    }
}
