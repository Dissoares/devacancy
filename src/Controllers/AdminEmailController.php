<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Email;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Services\AutenticacaoService;

/** Envia o e-mail de teste da tela Configurações → E-mail e guarda o resultado para ela mostrar. */
final class AdminEmailController
{
    public function __construct(
        private readonly AutenticacaoService $autenticacao,
        private readonly Email $email,
    ) {
    }

    public function testar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticacao->usuarioAtual();
        $nome = (string) configuracao('app.nome');

        $enviado = $this->email->enviar(
            $usuario->email,
            "Teste de e-mail do {$nome}",
            "Se você leu isto, o envio de e-mails do {$nome} está funcionando.",
            '<p>Se você leu isto, o envio de e-mails do <strong>' . e($nome) . '</strong> está funcionando.</p>',
        );

        Sessao::definir('teste_email', [
            'ok' => $enviado,
            'para' => $usuario->email,
            'erro' => $this->email->ultimoErro,
        ]);
        return Resposta::redirecionar('/admin/configuracoes/email#email-teste');
    }
}
