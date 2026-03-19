<?php

require_once __DIR__ . '/../models/PlanDiarioModel.php';

class PlanDiarioController
{
    public function listar($request)
    {
        $model = new PlanDiarioModel();
        return $model->listarPlanes();
    }
}
