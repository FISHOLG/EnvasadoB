<?php

require_once 'config/config.php';
require_once 'app/core/Request.php';
require_once 'app/core/Router.php';

$request = new Request();
$router = new Router($request);

require_once 'app/routes/web.php';

$router->resolve();
