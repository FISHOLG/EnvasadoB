<?php

require_once __DIR__ . '/../models/LineasTrabajoModel.php';

class LineasTrabajoController
{
    private $model;

    public function __construct()
    {
        $this->model = new LineasTrabajoModel();
    }

    public function listar()
    {
        return $this->model->listar();
    }
}
