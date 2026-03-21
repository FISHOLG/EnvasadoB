<?php

require_once __DIR__ . '/../models/PlanDiarioModel.php';

class PlanDiarioController
{
    public function listar($request)
    {
        $model = new PlanDiarioModel();
        return $model->listarPlanes();
    }

//     public function obtenerPlanCache()
// {
//     $data = CacheHelper::obtenerPlan();

//     if (!$data) {
//         return [
//             "ok" => false,
//             "message" => "No existe plan en cache"
//         ];
//     }

//     return [
//         "ok" => true,
//         "data" => $data
//     ];
// }
}


