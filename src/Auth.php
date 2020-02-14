<?php

namespace Uspdev\Evasao;

class Auth
{
    public static function auth()
    {
        global $users;

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: authorization');

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            \Flight::requireAuth();
            exit;
        }

        if (!isset($users[$_SERVER['PHP_AUTH_USER']]) or $users[$_SERVER['PHP_AUTH_USER']] != md5($_SERVER['PHP_AUTH_PW'])) {
            \Flight::requireAuth();
            exit;
        }
    }

    public static function login()
    {
        global $users;

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: authorization');

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            echo 'OK';
            exit;
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('HTTP/1.0 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="use this hash key to encode"');
            echo 'Você deve digitar um login e senha válidos para acessar este recurso\n';
            exit;
        }

        if (!isset($users[$_SERVER['PHP_AUTH_USER']]) or $users[$_SERVER['PHP_AUTH_USER']] != md5($_SERVER['PHP_AUTH_PW'])) {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Credenciais inválidas';
            exit();
        }

        echo 'Login success';
        exit;
    }

    public static function logout()
    {
        header('HTTP/1.0 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="use this hash key to encode"');
        die('logout');
    }

    // Implementa controle de acesso por IP
    // Se definido IP_ACCESS_LIST, usará essa lista de ip/prefixo para
    // autorizar o acesso a todos os endpoints
    // Para desativar não defina a constante IP_ACCESS_LIST
    public static function ipControl()
    {
        define('IP_ACCESS_LIST', array(
            ['143.107.233.0', '24'], // SET
            ['143.107.181.0', '24'], // Campus 2: SAA, SEA
            ['143.107.182.0', '24'], // STI
            ['143.107.234.0', '24'], // SGS, STT
            ['143.107.235.0', '24'], // SEL
            ['143.107.238.0', '24'], // ALMOX, SEP
            ['143.107.239.0', '24'], // SEM
            ['10.233.0.0', '16'], // SET Intranet
        ));

        // se a lista for vazia ninguém tem acesso
        //define('IP_ACCESS_LIST', []);

        if (getenv('IP_CONTROL') === false) {
            return true;
        }

        // vamos verificar se o IP está na lista
        if (defined('IP_ACCESS_LIST')) {
            foreach (IP_ACCESS_LIST as $ip_list) {
                // https://stackoverflow.com/questions/2869893/block-specific-ip-block-from-my-website-in-php
                $network = ip2long($ip_list[0]);
                $prefix = $ip_list[1];
                $ip = ip2long($_SERVER['REMOTE_ADDR']);

                if ($network >> (32 - $prefix) == $ip >> (32 - $prefix)) {
                    return true;
                }
            }
        } 
        // não houve match na lista então vamos negar acesso
        return false;
    }
}
