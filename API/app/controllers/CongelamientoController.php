<?php

require_once __DIR__ . '/../models/CongelamientoModel.php';

class CongelamientoController
{
    private CongelamientoModel $model;

    public function __construct()
    {
        $this->model = new CongelamientoModel();
    }

    public function registrarDetalle()
    {
        try {

            $input = json_decode(file_get_contents("php://input"), true);

            if (
                empty($input["cod_maquina"]) ||
                empty($input["cod_tel"]) ||
                empty($input["ges_item"]) ||
                empty($input["kilos"])
            ) {
                return [
                    "ok" => false,
                    "message" => "Datos incompletos"
                ];
            }

            $this->model->registrarDetalle(
                $input["cod_maquina"],
                $input["cod_tel"],
                (int)$input["ges_item"],
                (float)$input["kilos"],
                (float)($input["cantidad"] ?? 0),
                $input["cod_usr"]
            );

            return [
                "ok" => true,
                "message" => "Congelamiento Registrado"
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => $e->getMessage()
            ];
        }
    }
}