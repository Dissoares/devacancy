<?php

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

require RAIZ . '/vendor/autoload.php';

(new App\Core\Aplicacao())->executar();
