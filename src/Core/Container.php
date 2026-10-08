<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/** Injeção de dependências com autowiring: resolve construtores pelos tipos dos parâmetros. */
final class Container
{
    private array $vinculos = [];
    private array $instancias = [];

    public function vincular(string $abstrato, string|callable $concreto): void
    {
        $this->vinculos[$abstrato] = $concreto;
    }

    public function obter(string $classe): object
    {
        if (isset($this->instancias[$classe])) {
            return $this->instancias[$classe];
        }

        $concreto = $this->vinculos[$classe] ?? $classe;
        $instancia = is_callable($concreto) ? $concreto($this) : $this->construir($concreto);

        return $this->instancias[$classe] = $instancia;
    }

    private function construir(string $classe): object
    {
        if (!class_exists($classe)) {
            throw new RuntimeException("Classe não encontrada no container: {$classe}");
        }

        $reflexao = new ReflectionClass($classe);
        if (!$reflexao->isInstantiable()) {
            throw new RuntimeException("Sem implementação vinculada para: {$classe}");
        }

        $construtor = $reflexao->getConstructor();
        if ($construtor === null) {
            return new $classe();
        }

        $dependencias = [];
        foreach ($construtor->getParameters() as $parametro) {
            $tipo = $parametro->getType();
            if ($tipo instanceof ReflectionNamedType && !$tipo->isBuiltin()) {
                $dependencias[] = $this->obter($tipo->getName());
            } elseif ($parametro->isDefaultValueAvailable()) {
                $dependencias[] = $parametro->getDefaultValue();
            } else {
                throw new RuntimeException("Não foi possível resolver \${$parametro->getName()} em {$classe}");
            }
        }

        return $reflexao->newInstanceArgs($dependencias);
    }
}
