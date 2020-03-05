<?php
require_once '../app/bootstrap.php';

use Uspdev\Evasao\Ws;
use Uspdev\Ipcontrol\Ipcontrol;

// limitando acesso por IP, se estiver habilitado, antes de tudo
Ipcontrol::proteger();

// não temos nada aqui por enquanto
Flight::route('*', function () {
    return true;
});

// na raiz vamos colocar os controllers disponiveis, menos o de administração que fica oculto
Flight::route('/', function () use ($controllers) {

    foreach ($controllers as $key => $val) {
        $out[$key] = getenv('DOMINIO') . '/' . $key;
    }
    Flight::jsonf($out);

});

// vamos criar as rotas específicas de admininistação do webservice
Flight::route('GET /' . $mgmt_route . '(/@metodo:[a-z]+(/@param1))', function ($metodo, $param1) use ($mgmt_class) {

    // vamos verificar se o usuário é valido
    Flight::auth();

    $ctrl = new $mgmt_class();
    if (empty($metodo)) {
        // se nao foi passado metodo vamos mostrar a lista de metodos publicos
        $out = Ws::metodos($ctrl);
    } else {
        // se foi passado vamos chamá-lo
        $out = $ctrl->$metodo($param1);
    }
    Flight::jsonf($out);

});

// vamos mapear todas as rotas para o controller selecionado
Flight::route('GET /@controlador:[a-z]+(/@metodo:[a-z]+(/@param1))', function ($controlador, $metodo, $param1) use ($controllers) {

    // vamos verificar se o usuário é valido
    Flight::auth();

    // se o controlador passado nao existir
    if (empty($controllers[$controlador])) {
        Flight::notFound('Controlador inexistente');
    }

    // como o controlador existe, vamos instanciar
    $ctrl = new $controllers[$controlador];

    // se nao foi passado metodo vamos mostrar a lista de metodos publicos
    if (empty($metodo)) {
        $out = Ws::metodos($ctrl);
        Flight::jsonf($out);
        exit;
    }

    // se o método não existe vamos abortar
    if (!method_exists($ctrl, $metodo)) {
        Flight::notFound('Metodo inexistente');
    }

    // agora que está tudo certo vamos fazer a chamada usando cache
    $c = new Uspdev\Cache\Cache($ctrl);
    $out = $c->getCached($metodo, [$param1]);

    // vamos formatar a saída de acordo com format=?
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
