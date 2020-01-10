<?php

require_once '../app/bootstrap.php';

// Controller padrão
$ws = new Uspdev\Evasao\Ws;
# Instanciando biblioteca de cache
use Uspdev\Cache\Cache;
$c = new Cache($ws);

// na raiz vamos colocar a documentação
Flight::route('/', function () use ($ws) {
    $api['posgraduacao_url'] = getenv('DOMINIO') . '/posgraduacao';
    $api['metodos'] = get_class_methods($ws);
    Flight::jsonf($api);
});

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
