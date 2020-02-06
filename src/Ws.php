<?php namespace Uspdev\Evasao;

class Ws
{
    public static function metodos($obj)
    {
        //$ws = new SELF;
        $metodos = get_class_methods($obj);

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
                $api[$m] = getenv('DOMINIO') . '/evasao/' . $m . $p;
            }
        }
        return $api;
    }
}