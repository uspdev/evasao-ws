<?php

namespace Uspdev\Evasao;

class Auth
{
    public $msg;

    // formato: ['username' => pwd_hash];
    private $users = [];

    public function __construct($pwdfile = '')
    {
        if (empty($pwdfile)) {
            $pwdfile = PWD_FILE;
        }

        // vamos ler o arquivo de senhas
        if (($handle = fopen($pwdfile, 'r')) !== false) {
            while (($user = fgetcsv($handle, 1000, ':')) !== false) {
                $this->users[$user[0]] = $user[1];
            }
            fclose($handle);
        }
    }

    public function auth()
    {

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        // header('Access-Control-Allow-Headers: authorization');

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        $user = $_SERVER['PHP_AUTH_USER'];
        $pwd = $_SERVER['PHP_AUTH_PW'];

        //echo $user,$pwd;exit;

        // se o usuario não existir ou se a senha não conferir vamos negar acesso
        if (!isset($this->users[$user]) or !password_verify($pwd, $this->users[$user])) {
            return false;
        }

        // tudo certo, acesso liberado
        return true;
    }

    public function login()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: authorization');

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $this->msg = 'OK';
            return true;
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="use this hash key to encode"');
            //header('HTTP/1.0 401 Unauthorized');
            $this->msg = 'Você deve digitar um login e senha válidos para acessar este recurso';
            return false;
        }

        if ($this->auth()) {
            $this->msg = 'Login com successo';
            return true;
        }

        $this->msg = 'Usuário ou senha inválidos';
        return false;
    }

    public static function logout()
    {
        header('WWW-Authenticate: Basic realm="use this hash key to encode"');
        //header('HTTP/1.0 401 Unauthorized');
        //exit;
        //die('logout');
    }

    // Controle de acesso por IP
    public static function ipControl($ipfile)
    {
        // vamos ler o arquivo de endereços autorizados
        if (($handle = fopen($ipfile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                
                // e ver se o ip está na lista
                // https://stackoverflow.com/questions/2869893/block-specific-ip-block-from-my-website-in-php
                $network = ip2long($row[0]);
                $prefix = (int) $row[1];
                $ip = ip2long($_SERVER['REMOTE_ADDR']);

                if ($network >> (32 - $prefix) == $ip >> (32 - $prefix)) {
                    // Se sim, vamos liberar o acesso
                    fclose($handle);
                    return true;
                }
            }
            fclose($handle);
            // aqui vamos negar o acesso
            return false;
        } else {
            echo 'Erro ao ler arquivo '.$ipfile;
            exit;
        }
    }
}
