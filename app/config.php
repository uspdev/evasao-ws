<?php

# Usuários com acesso ao sistema 
define('PWD_FILE', __DIR__.'/../local/users.txt');

# lista de controllers disponiveis, incluindo namespaces.
define('CONTROLLERS_FILE', __DIR__.'/../local/controllers.txt');
$controllers['evasao'] = 'Uspdev\Evasao\Evasao';

# Controlador de gerencia do webservice
$mgmt_route = 'Ws';
$mgmt_class = 'Uspdev\Evasao\Ws';