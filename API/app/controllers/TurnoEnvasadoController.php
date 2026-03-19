<?php

require_once __DIR__ . '/../models/TurnoEnvasadoModel.php';
require_once __DIR__ . '/../helper/CacheHelper.php';

class TurnoEnvasadoController
{
    private TurnoEnvasadoModel $model;

    public function __construct()
    {
        $this->model = new TurnoEnvasadoModel();
    }

    /* ======================================================
       EJECUTAR TURNO (ABRIR / CERRAR)
    ====================================================== */
    public function ejecutar()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codUsr = $input['cod_usr'] ?? null;
        $accion = $input['accion'] ?? null;
        $planes = $input['planes'] ?? null;

        if (!$codUsr || !$accion) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'PARÁMETROS INCOMPLETOS'
            ];
        }

        /* ================= ABRIR TURNO ================= */
        if ($accion === '2') {

            if (!is_array($planes) || count($planes) === 0) {
                http_response_code(400);
                return [
                    'ok' => false,
                    'message' => 'PLANES REQUERIDOS'
                ];
            }

            // Guarda plan en cache
            CacheHelper::guardarPlan($planes, $codUsr);

            $ok = $this->model->ejecutarTurno($codUsr, '2');

        /* ================= CERRAR TURNO ================= */
        } elseif ($accion === '3') {

            $ok = $this->model->ejecutarTurno($codUsr, '3');

            if ($ok) {
                // Limpia todos los caches relacionados
                CacheHelper::limpiarPlan();
                CacheHelper::limpiarLineas();
                CacheHelper::limpiarProductoLinea(); 
            }

        } else {

            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'ACCIÓN INVÁLIDA'
            ];
        }

        if (!$ok) {
            return [
                'ok' => false,
                'message' => 'ERROR AL EJECUTAR TURNO'
            ];
        }

        $estadoTurno = $this->model->obtenerEstadoTurno();

        return [
            'ok' => true,
            'data' => $estadoTurno
        ];
    }

    /* ======================================================
       ESTADO TURNO
    ====================================================== */
    public function estado()
    {
        $estadoTurno = $this->model->obtenerEstadoTurno();

        return [
            'ok' => true,
            'data' => $estadoTurno
        ];
    }

    /* ======================================================
       PLAN CACHE
    ====================================================== */
    public function obtenerPlan()
    {
        $data = CacheHelper::obtenerPlan();

        return [
            'ok' => true,
            'data' => $data
        ];
    }

    public function limpiarPlan()
    {
        CacheHelper::limpiarPlan();

        return [
            'ok' => true
        ];
    }

    /* ======================================================
       LINEAS CACHE
    ====================================================== */

    public function guardarLineas()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $lineas = $input['lineas'] ?? null;

        if (!is_array($lineas)) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'FORMATO INVÁLIDO'
            ];
        }

        // Si viene vacío, limpiamos cache
        if (count($lineas) === 0) {
            CacheHelper::limpiarLineas();
            return ['ok' => true];
        }

        CacheHelper::guardarLineas($lineas);

        return ['ok' => true];
    }

    public function obtenerLineas()
    {
        $data = CacheHelper::obtenerLineas();

        return [
            'ok' => true,
            'data' => $data
        ];
    }

    public function limpiarLineas()
    {
        CacheHelper::limpiarLineas();

        return ['ok' => true];
    }

    /* ======================================================
       PRODUCTO POR LINEA CACHE 
    ====================================================== */

    /**
     * Guarda los productos seleccionados por línea.
     * Si la línea ya existe en cache → se actualiza.
     * Si no existe → se agrega.
     */
    public function guardarProductoLinea()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codLinea = $input['cod_linea'] ?? null;
        $productos = $input['productos'] ?? null;

        if (!$codLinea || !is_array($productos)) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'DATOS INVÁLIDOS'
            ];
        }

        CacheHelper::guardarProductoLinea([
            'cod_linea' => $codLinea,
            'productos' => $productos
        ]);

        return [
            'ok' => true,
            'message' => 'PRODUCTO LINEA GUARDADO'
        ];
    }

    /**
     * Devuelve el JSON completo de producto_linea
     */
    public function obtenerProductoLinea()
    {
        $data = CacheHelper::obtenerProductoLinea();

        return [
            'ok' => true,
            'data' => $data
        ];
    }

    /**
     * Limpia manualmente el cache de producto_linea
     */
    public function limpiarProductoLinea()
    {
        CacheHelper::limpiarProductoLinea();

        return ['ok' => true];
    }
}
