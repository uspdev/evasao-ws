<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Uspdev\Evasao\Auth;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if (getenv('AMBIENTE') == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    //Flight::set('flight.handle_errors', false);
}

# Variáveis obrigatórias
$dotenv->required('REPLICADO_HOST')->notEmpty();
$dotenv->required('CODCLG')->notEmpty();

// no .env o caminho do log é baseado na reaiz do sistema. Vamos ajustar para refletir essa ideia
putenv('REPLICADO_PATHLOG=' . __DIR__ . '/../local/' . getenv('REPLICADO_PATHLOG'));

// vamos ajustar os caminhos baseados no arquivo de configuracao
// dessa forma não precisamos recorrer ao RewriteBase do apache
// e podemos usar um .htaccess que não depende do deploy

//print_r(Flight::request());

//Flight::request()->base = parse_url(getenv('DOMINIO'), PHP_URL_PATH);
//Flight::request()->url = str_replace(Flight::request()->base, '', Flight::request()->url);

//print_r(Flight::request());exit;

// vamos verificar se o usuário está autenticado
Flight::map('auth', function () {
    $auth = new Auth();
    if (!$auth->auth()) {
        $auth->logout();
        if (!$auth->login()) {
            Flight::unauthorized($auth->msg);
        }
    }
});

// vamos imprimir a saida em csv
Flight::map('csv', function ($data) {

    header("Content-type: text/csv");
    header("Content-Disposition: inline; filename=file.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    if (!empty($data[0])) {
        // aqui se espera um array de arrays onde as chaves são a primeira linha da planilha
        $keys = array_keys($data[0]);
        fputcsv($out, $keys, ';');

        // e os dados vêm nas linhas subsequentes
        foreach ($data as $row) {
            fputcsv($out, $row, ';');
        }
    } else {
        // se for um array simples vamos exportar linha a linha sem cabecalho
        foreach ($data as $key => $val) {
            fputcsv($out, [$key, $val], ';');
        }
    }
    fclose($out);
});

// vamos imprimir o json formatado para humanos lerem
Flight::map('jsonf', function ($data) {
    Flight::json($data, 200, true, 'utf-8', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    Flight::stop();
});

// vamos sobrescrever a mensagem de not found para ficar mais compatível com a API
// retorna 404 mas com mensagem personalizada opcional ou mensagem padrão
Flight::map('notFound', function ($msg = null) {
    $data['message'] = empty($msg) ? 'Not Found' : $msg;
    $data['documentation_url'] = getenv('DOMINIO') . '/';
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    Flight::halt(404, $json);
});

// Interrompe a execução com 403 - Forbidden
// usado quando negado acesso por IP
Flight::map('forbidden', function ($msg = null) {
    $data['message'] = empty($msg) ? 'Forbidden' : $msg;
    $data['documentation_url'] = getenv('DOMINIO') . '/';
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    Flight::halt(403, $json);
});

Flight::map('unauthorized', function ($msg = null) {
    $data['message'] = empty($msg) ? 'unauthorized' : $msg;
    $data['documentation_url'] = getenv('DOMINIO') . '/';
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    Flight::halt(401, $json);
});
