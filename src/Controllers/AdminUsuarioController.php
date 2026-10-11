<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Exceptions\ValidacaoException;
use App\Services\AutenticacaoService;
use App\Services\UsuarioService;

/** Quem é administrador, e exclusão de cadastros. */
final class AdminUsuarioController
{
    public function __construct(
        private readonly UsuarioService $usuarios,
        private readonly AutenticacaoService $autenticacao,
    ) {
    }

    public function administradores(Requisicao $requisicao): Resposta
    {
        return $this->pagina();
    }

    public function promover(Requisicao $requisicao): Resposta
    {
        $email = trim((string) $requisicao->entrada('email', ''));

        try {
            $promovido = $this->usuarios->promoverAAdmin($email);
        } catch (ValidacaoException $erro) {
            return $this->pagina($erro->erros()['email'] ?? null, null, $email, 422);
        }

        Sessao::flash('sucesso', "{$promovido->email} agora é administrador.");
        return Resposta::redirecionar('/admin/administradores');
    }

    public function excluir(Requisicao $requisicao): Resposta
    {
        $id = (int) $requisicao->parametro('id');
        $atual = $this->autenticacao->usuarioAtual();

        try {
            $this->usuarios->excluir($id, $atual->id);
        } catch (ValidacaoException $erro) {
            Sessao::flash('erro', $erro->erros()['usuario'] ?? 'Não foi possível excluir.');
            return Resposta::redirecionar($requisicao->entrada('voltar') === 'admins' ? '/admin/administradores' : '/admin');
        }

        Sessao::flash('sucesso', 'Cadastro excluído.');
        return Resposta::redirecionar($requisicao->entrada('voltar') === 'admins' ? '/admin/administradores' : '/admin');
    }

    private function pagina(?string $erro = null, ?string $sucesso = null, string $email = '', int $status = 200): Resposta
    {
        return Resposta::html(Visao::renderizar('admin/administradores', [
            'titulo' => 'Administradores — Devacancy',
            'usuario' => $this->autenticacao->usuarioAtual(),
            'admins' => $this->usuarios->administradores(),
            'erro' => $erro ?? Sessao::obterFlash('erro'),
            'sucesso' => $sucesso ?? Sessao::obterFlash('sucesso'),
            'email' => $email,
        ], 'app'), $status);
    }
}
