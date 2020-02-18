<?php namespace Uspdev\Evasao;

use Uspdev\Evasao\Auth;

class Ws
{
    public static function metodos($obj)
    {
        $metodos = get_class_methods($obj);

        $classe = get_class($obj);
        if ($pos = strrpos($classe, '\\')) {
            $classe = substr($classe, $pos + 1);
        }
        $classe = strtolower($classe);

        foreach ($metodos as $m) {
            // para cada método vamos obter os parâmetros
            $r = new \ReflectionMethod($obj, $m);
            $params = $r->getParameters();

            // vamos listar somente os métodos publicos
            if ($r->isPublic()) {
                $p = '/';
                foreach ($params as $param) {
                    $p .= '{' . $param->getName() . '}, ';
                }
                $p = substr($p, 0, -2);

                // vamos apresentar na forma de url
                $api[$m] = getenv('DOMINIO') . '/' . $classe . '/' . $m . $p;
            }
        }
        return $api;
    }

    public static function status()
    {
        $out['colegiados'] = getenv('CODCLG');
        $out['cache'] = getenv('USPDEV_CACHE_DISABLE') ? 'desabilitado' : 'habilitado';
        $out['ip_control'] = getenv('IP_CONTROL') ? 'habilitado' : 'desabilitado';

        return $out;
    }

    public static function login()
    {
        $auth = new Auth();
        if ($auth->login()) {
            return ['msg' => $auth->msg];
        } else {
            \Flight::unauthorized($auth->msg);
        }
    }

    public static function logout()
    {
        $auth = new Auth();
        $auth->logout();
        \Flight::unauthorized($auth->msg);
    }
}
