<?php

require_once '../app/bootstrap.php';

// Controller padrão
$ws = new Uspdev\Evasao\Ws;

// na raiz vamos colocar a documentação
Flight::route('/', function () use ($ws) {
    $api['posgraduacao_url'] = getenv('DOMINIO') . '/posgraduacao';
    $api['metodos'] = get_class_methods($ws);
    Flight::jsonf($api);
});

// vamos mapear todas as rotas para o controller padrão
Flight::route('GET /@metodo:[a-z]+(/@param1)', function ($metodo, $param1) use ($ws) {
    $ret = $ws->$metodo($param1);
    $f = Flight::request()->query['format'];
    switch ($f) {
        case 'csv':
            Flight::csv($ret);
            break;
        default:
            Flight::jsonf($ret);
    }
});

Flight::start();
