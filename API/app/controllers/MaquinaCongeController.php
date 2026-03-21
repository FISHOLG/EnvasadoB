<?php
require_once __DIR__ . '/../models/MaquinaCongeModel.php';
class MaquinaCongeController
{
    private MaquinaCongeModel $model;

    public function __construct()
    {
        $this->model = new MaquinaCongeModel();
    }

    public function listarMaquinas()
    {
        $data = $this->model->obtenerMaquinas();

        return [
            'ok' => true,
            'data' => $data
        ];
    }
}