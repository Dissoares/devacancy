<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NaoEncontradoException;
use Throwable;

final class Aplicacao
{
    private readonly Container $container;
    private readonly Roteador $roteador;

    public function __construct()
    {
        Ambiente::carregar(RAIZ . '/.env');
        date_default_timezone_set('America/Sao_Paulo');

        $this->container = new Container();
        foreach (configuracao('vinculos') as $abstrato => $concreto) {
            $this->container->vincular($abstrato, $concreto);
        }

        $this->roteador = new Roteador($this->container);
        $registrarRotas = require RAIZ . '/routes/web.php';
        $registrarRotas($this->roteador);
    }

    public function executar(): void
    {
        Sessao::iniciar();
        $requisicao = Requisicao::capturar();
        Idioma::detectar($requisicao);
        Indicacao::capturar($requisicao);

        try {
            $resposta = $this->protegerContraCsrf($requisicao) ?? $this->roteador->despachar($requisicao);
        } catch (NaoEncontradoException) {
            $resposta = Resposta::html(Visao::renderizar('errors/404', ['titulo' => t('erro.404_titulo')], 'site'), 404);
        } catch (Throwable $erro) {
            $resposta = $this->tratarErro($erro);
        }

        $resposta->enviar();
    }

    private function protegerContraCsrf(Requisicao $requisicao): ?Resposta
    {
        // Webhooks vêm de servidores externos e se autenticam por token próprio.
        if ($requisicao->metodo() !== 'POST' || str_starts_with($requisicao->caminho(), '/webhooks/')) {
            return null;
        }

        $token = $requisicao->entrada('_token') ?? $requisicao->cabecalho('x-csrf-token');
        if (Csrf::validar($token)) {
            return null;
        }

        return Resposta::texto(t('erro.sessao_expirada'), 403);
    }

    private function tratarErro(Throwable $erro): Resposta
    {
        $registro = sprintf(
            "[%s] %s: %s em %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $erro::class,
            $erro->getMessage(),
            $erro->getFile(),
            $erro->getLine(),
            $erro->getTraceAsString(),
        );
        error_log($registro, 3, RAIZ . '/storage/logs/erros.log');

        $detalhe = configuracao('app.ambiente') === 'local' ? $erro->getMessage() : null;

        return Resposta::html(
            Visao::renderizar('errors/500', ['titulo' => t('erro.500_titulo'), 'detalhe' => $detalhe], 'site'),
            500,
        );
    }
}
