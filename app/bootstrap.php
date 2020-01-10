<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

# Variáveis obrigatórias
$dotenv->required('REPLICADO_HOST')->notEmpty();

# Instanciando biblioteca de cache
use Uspdev\Cache\Cache;
$c = new Cache();

// vamos ajustar os caminhos baseados no arquivo de configuracao
// dessa forma não precisamos recorrer ao RewriteBase do apache
// e podemos usar um .htaccess que não depende do deploy
Flight::request()->base = parse_url(getenv('DOMINIO'), PHP_URL_PATH);
Flight::request()->url = str_replace(Flight::request()->base, '', Flight::request()->url);


// vamos imprimir o json formatado para humanos lerem
Flight::map('jsonf', function ($data) {
    Flight::json($data, 200, true, 'utf-8', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// vamos imprimir a saida em csv
Flight::map('csv', function ($data) {
    header("Content-type: text/csv");
    header("Content-Disposition: inline; filename=file.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    $out = fopen('php://output', 'w');
    fwrite($out,"\xEF\xBB\xBF");
    $keys = array_keys($data[0]);
    fputcsv($out, $keys, ';');
    
    foreach ($data as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
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
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    Flight::halt(403, $json);
});