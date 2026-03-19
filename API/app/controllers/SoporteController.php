<?php

require_once __DIR__ . '/../models/SoporteModel.php';
require_once __DIR__ . '/../models/TurnoEnvasadoModel.php';

class SoporteController
{

    /* 
       LISTAR MAESTRO
     */
    public function listar()
    {
        try {

            $model = new SoporteModel();
            $data  = $model->listar();

            return [
                "ok" => true,
                "data" => $data
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => "ERROR AL LISTAR SOPORTE",
                "error" => $e->getMessage()
            ];
        }
    }

    /* 
       ASIGNAR SOPORTES 
     */
    public function asignar()
    {
        try {

            $input = json_decode(file_get_contents("php://input"), true);

            if (
                empty($input["cod_linea"]) ||
                !isset($input["soportes"]) ||
                !is_array($input["soportes"])
            ) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "DATOS INVÁLIDOS"
                ];
            }

            $codLinea = trim($input["cod_linea"]);
            $soportes = $input["soportes"];

            /* 
               VALIDAR TURNO ACTIVO
             */

            $turnoModel = new TurnoEnvasadoModel();
            $estadoTurno = $turnoModel->obtenerEstadoTurno();

            if (!$estadoTurno['aperturado']) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "NO HAY TURNO ACTIVO"
                ];
            }

            $codTurno = $estadoTurno['cod_tur_env'];
            $codUsr   = $estadoTurno['cod_usr'];

            if (empty($codUsr)) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "USUARIO NO ENCONTRADO EN TURNO"
                ];
            }

            $model = new SoporteModel();

            /* 
               OBTENER ORIGEN
             */

            $codOrigen = $model->obtenerCodOrigen();

            if (empty($codOrigen)) {
                http_response_code(500);
                return [
                    "ok" => false,
                    "message" => "NO SE PUDO OBTENER COD_ORIGEN"
                ];
            }

            /* 
               LIMPIAR TODO LO ANTERIOR 
             */

            $model->eliminar($codLinea, null, $codTurno);

            /* 
               SI NO HAY SOPORTES → SOLO LIMPIÓ
             */

            if (count($soportes) === 0) {

                return [
                    "ok" => true,
                    "message" => "SOPORTES ELIMINADOS",
                    "data" => []
                ];
            }

            /* 
               INSERTAR NUEVOS
             */

            $model->insertarMultiplesSoportes(
                $soportes,
                $codLinea,
                $codTurno,
                $codUsr,
                $codOrigen
            );

            /* 
               DEVOLVER ESTADO ACTUAL
             */

            $dataActual = $model->obtenerSoportesPorLinea($codLinea);

            return [
                "ok" => true,
                "message" => "SOPORTES ACTUALIZADOS",
                "data" => $dataActual
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => "ERROR AL ASIGNAR SOPORTES",
                "error" => $e->getMessage()
            ];
        }
    }

    /* 
       ACTUALIZAR ESTADO
     */
    public function actualizarEstado()
    {
        try {

            $input = json_decode(file_get_contents("php://input"), true);

            if (
                empty($input["cod_enva_s"]) ||
                empty($input["cod_linea"]) ||
                !isset($input["estado"])
            ) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "DATOS INVÁLIDOS"
                ];
            }

            $model = new SoporteModel();

            $model->actualizarEstado(
                trim($input["cod_enva_s"]),
                trim($input["cod_linea"]),
                intval($input["estado"])
            );

            return [
                "ok" => true,
                "message" => "ESTADO ACTUALIZADO"
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => "ERROR AL ACTUALIZAR ESTADO",
                "error" => $e->getMessage()
            ];
        }
    }

    /* 
       ELIMINAR

     */
    public function eliminar()
    {
        try {

            $input = json_decode(file_get_contents("php://input"), true);

            if (empty($input["cod_linea"])) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "COD_LINEA REQUERIDO"
                ];
            }

            $codLinea = trim($input["cod_linea"]);
            $codEnvaS = isset($input["cod_enva_s"])
                ? trim($input["cod_enva_s"])
                : null;

            $model = new SoporteModel();

            $model->eliminar($codLinea, $codEnvaS);

            return [
                "ok" => true,
                "message" => "ELIMINACIÓN CORRECTA"
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => "ERROR AL ELIMINAR",
                "error" => $e->getMessage()
            ];
        }
    }

    /* 
       OBTENER POR LINEA
     */
    public function porLinea()
    {
        try {

            $codLinea = $_GET['cod_linea'] ?? null;

            if (empty($codLinea)) {
                http_response_code(400);
                return [
                    "ok" => false,
                    "message" => "COD_LINEA REQUERIDO"
                ];
            }

            $model = new SoporteModel();
            $data = $model->obtenerSoportesPorLinea(trim($codLinea));

            return [
                "ok" => true,
                "data" => $data
            ];

        } catch (Throwable $e) {

            http_response_code(500);

            return [
                "ok" => false,
                "message" => "ERROR AL OBTENER SOPORTES",
                "error" => $e->getMessage()
            ];
        }
    }
}
