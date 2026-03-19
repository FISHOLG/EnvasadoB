<?php

class Request {

    private $routeParams = [];

    public function addRouteParams($params) {
        $this->routeParams = array_merge($this->routeParams, $params);
    }

    public function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($path, PHP_URL_PATH);
    }

    public function getMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getBody() {
        return json_decode(file_get_contents('php://input'), true);
    }

    public function getParams() {

        return array_merge($this->routeParams, $_GET ?? []);
    }
}
