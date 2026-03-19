<?php

class Router
{
    private $request;
    private $routes = ['get' => [], 'post' => [], 'put' => []];

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function put($path, $callback)
    {
        $this->routes['put'][$path] = $callback;
    }

    public function resolve()
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        // $path = str_replace(APP_NAME, '', strtolower($path));
        $path = strtolower($path);

        if (strpos($path, APP_NAME) === 0) {
            $path = substr($path, strlen(APP_NAME));
        }


        $callback = $this->routes[$method][$path] ?? null;

        if (!$callback) {
            foreach ($this->routes[$method] as $route => $handler) {
                // Extraer los nombres de parámetros
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $paramNames);
                $paramNames = $paramNames[1]; // nos quedamos solo con los nombres

                // Crear patrón de regex
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route);

                // Comparar la URL real con el patrón
                if (preg_match("#^$pattern$#", $path, $matches)) {
                    array_shift($matches); // quitar coincidencia completa

                    //  Asignar a un objeto asociativo
                    $paramsAssoc = [];
                    foreach ($paramNames as $index => $name) {
                        $paramsAssoc[$name] = $matches[$index];
                    }

                    // Guardar en request
                    $this->request->addRouteParams($paramsAssoc);
                    $callback = $handler;
                    break;
                }
            }
        }

        if (!$callback) {

            http_response_code(404);
            echo json_encode(['error' => 'Ruta no encontrada']);
            return;
        }


        [$controllerName, $methodName] = explode('@', $callback);
        require_once __DIR__ . "/../controllers/$controllerName.php";
        $controller = new $controllerName;

        echo json_encode($controller->$methodName($this->request));
        // $controller->$methodName($this->request);


        
    }
}
