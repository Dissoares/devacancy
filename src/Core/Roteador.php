<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NaoEncontradoException;

final class Roteador
{
    private array $rotas = [];

    public function __construct(private readonly Container $container)
    {
    }

    /** @param array{0: class-string, 1: string} $acao */
    public function get(string $caminho, array $acao, array $middlewares = []): void
    {
        $this->adicionar('GET', $caminho, $acao, $middlewares);
    }

    /** @param array{0: class-string, 1: string} $acao */
    public function post(string $caminho, array $acao, array $middlewares = []): void
    {
        $this->adicionar('POST', $caminho, $acao, $middlewares);
    }

    public function despachar(Requisicao $requisicao): Resposta
    {
        foreach ($this->rotas as $rota) {
            if ($rota['metodo'] !== $requisicao->metodo()) {
                continue;
            }
            if (!preg_match($rota['regex'], $requisicao->caminho(), $correspondencias)) {
                continue;
            }

            $requisicao->definirParametrosRota(array_filter($correspondencias, 'is_string', ARRAY_FILTER_USE_KEY));
            return $this->executar($rota, $requisicao);
        }

        throw new NaoEncontradoException();
    }

    private function adicionar(string $metodo, string $caminho, array $acao, array $middlewares): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $caminho);
        $this->rotas[] = [
            'metodo' => $metodo,
            'regex' => '#^' . $regex . '$#',
            'acao' => $acao,
            'middlewares' => $middlewares,
        ];
    }

    private function executar(array $rota, Requisicao $requisicao): Resposta
    {
        [$classe, $metodo] = $rota['acao'];

        $nucleo = fn (Requisicao $req): Resposta => $this->container->obter($classe)->{$metodo}($req);

        // Monta a cadeia de middlewares de fora para dentro.
        $cadeia = array_reduce(
            array_reverse($rota['middlewares']),
            fn (callable $proximo, string $middleware) => fn (Requisicao $req): Resposta
                => $this->container->obter($middleware)->processar($req, $proximo),
            $nucleo,
        );

        return $cadeia($requisicao);
    }
}
