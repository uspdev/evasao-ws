<?php

require_once '../app/bootstrap.php';

// Controller padrão
//$ws = new Uspdev\Evasao\Ws;

# Instanciando biblioteca de cache
//$c = new Uspdev\Cache\Cache($ws);

// na raiz vamos colocar os controllers disponiveis
Flight::route('/', function () use ($controllers) {
    foreach ($controllers as $key => $val) {
        $out[$key] = getenv('DOMINIO') . '/' . $key;
    }
    Flight::jsonf($out);
});

// vamos mapear todas as rotas para o controller selecionado
Flight::route('GET /@controlador:[a-z]+(/@metodo:[a-z]+(/@param1))',
    function ($controlador, $metodo, $param1) use ($controllers) {

        // se o controladro passado nao existe
        if (empty($controllers[$controlador])) {
            Flight::notFound('Controlador inexistente');
        }

        // se nao foi passado metodo vamos mostrar a lista de metodos
        if (empty($metodo)) {
            $metodo = 'documentacao';
        }

        $ctrl = new $controllers[$controlador];

        // se o método não existe
        if (!method_exists($ctrl, $metodo)) {
            Flight::notFound('Metodo inexistente');
        }

        // agora que está tudo certo vamos fazer a chamada usando cache
        $c = new Uspdev\Cache\Cache($ctrl);
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
