<?php

require_once '../app/bootstrap.php';

// Controller padrão
$ws = new Uspdev\Evasao\Ws;

# Instanciando biblioteca de cache
$c = new Uspdev\Cache\Cache($ws);

// na raiz vamos colocar a documentação
Flight::route('/', function () use ($ws) {

    $metodos = get_class_methods($ws);

    foreach ($metodos as $m) {
        // para cada método vamos obter os parâmetros
        $r = new ReflectionMethod($ws, $m);
        $params = $r->getParameters();
        $p = '/';
        foreach ($params as $param) {
            $p .= '{' . $param->getName() . '}, ';
        }
        $p = substr($p,0,-2);
        
        // vamos apresentar na forma de url
        $api[$m] = getenv('DOMINIO') . '/' . $m . $p;
    }
    Flight::jsonf($api);
});

define('USPDEV_CACHE_DISABLE', true);
// vamos mapear todas as rotas para o controller padrão
Flight::route('GET /@metodo:[a-z]+(/@param1)', function ($metodo, $param1) use ($c) {

    $out = $c->getCached($metodo, [$param1]);

    $f = Flight::request()->query['format'];
    switch ($f) {
        case 'csv':
            Flight::csv($out);
            break;
        case 'json':
            Flight::jsonf($out);
            break;
        default:
            Flight::json($out);
    }
});

Flight::start();
