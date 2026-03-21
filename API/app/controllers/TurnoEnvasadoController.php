<?php

require_once __DIR__ . '/../models/TurnoEnvasadoModel.php';
require_once __DIR__ . '/../helper/CacheHelper.php';
require_once __DIR__ . '/../models/SoporteModel.php';

class TurnoEnvasadoController
{
    private TurnoEnvasadoModel $model;

    public function __construct()
    {
        $this->model = new TurnoEnvasadoModel();
    }

    /* 
       EJECUTAR TURNO (ABRIR / CERRAR)
     */
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

    try {

        /* ================= ABRIR TURNO ================= */
        if ($accion === '2') {

            if (!is_array($planes) || count($planes) === 0) {
                http_response_code(400);
                return [
                    'ok' => false,
                    'message' => 'PLANES REQUERIDOS'
                ];
            }

            /* guardar plan diario */
            CacheHelper::guardarPlan($planes, $codUsr);

            /* registrar turno */
            $this->model->ejecutarTurno(null, $codUsr, 1);

            /* obtener último turno */
            $turno = $this->model->obtenerTurnoActivo();

            if (!$turno) {
                return [
                    'ok' => false,
                    'message' => 'NO SE PUDO OBTENER EL TURNO REGISTRADO'
                ];
            }

            /* iniciar turno */
            $this->model->ejecutarTurno($turno['cod_tur_env'], $codUsr, 2);
        }

        /* ================= CERRAR TURNO ================= */
        elseif ($accion === '3') {

            $turno = $this->model->obtenerTurnoActivo();

            if (!$turno || $turno['flag_estado'] !== '1') {
                return [
                    'ok' => false,
                    'message' => 'NO EXISTE TURNO ACTIVO'
                ];
            }

            $this->model->ejecutarTurno($turno['cod_tur_env'], $codUsr, 3);

            /* limpiar cache solo si cerró correctamente */
            CacheHelper::limpiarPlan();
            CacheHelper::limpiarLineas();
            CacheHelper::limpiarProductoLinea();
        }

        else {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'ACCIÓN INVÁLIDA'
            ];
        }

        /* ================= RESPUESTA FINAL ================= */

        $estadoTurno = $this->model->obtenerTurnoActivo();

        return [
            'ok' => true,
            'data' => $estadoTurno
        ];

    } catch (Exception $e) {

        return [
            'ok' => false,
            'message' => $e->getMessage()
        ];
    }
}

    /* ESTADO TURNO*/

    public function estado()
    {
        return [
            'ok' => true,
            'data' => $this->model->obtenerTurnoActivo()
        ];
    }

    /* 
       PLAN CACHE
     */

    public function planCache()
    {
        $plan = CacheHelper::obtenerPlan();

        return [
            'ok' => true,
            'data' => $plan
        ];
    }

    public function limpiarPlan()
    {
        CacheHelper::limpiarPlan();

        return ['ok' => true];
    }

    /* 
       LINEAS CACHE
     */

    public function guardarLineas()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codUsr = $input['cod_usr'] ?? null;
        $lineas = $input['lineas'] ?? null;

        if (!$codUsr || !is_array($lineas)) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'FORMATO INVÁLIDO'
            ];
        }

        $resultado = CacheHelper::guardarLineas($lineas, $codUsr);

        if (!$resultado['ok']) {
            http_response_code(409);
            return [
                'ok' => false,
                'message' => "LA {$resultado['descr']} YA ESTÁ SELECCIONADA POR OTRO USUARIO"
            ];
        }

        return ['ok' => true];
    }

    public function obtenerLineas()
    {
        $codUsr = $_GET['cod_usr'] ?? null;

        if (!$codUsr) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'USUARIO REQUERIDO'
            ];
        }

        return [
            'ok' => true,
            'data' => CacheHelper::obtenerLineas($codUsr)
        ];
    }

    public function lineasOcupadas()
    {
        $codUsr = $_GET['cod_usr'] ?? null;

        if (!$codUsr) {
            http_response_code(400);
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'data' => CacheHelper::obtenerLineasOcupadas($codUsr)
        ];
    }

    /* 
       LIMPIAR LINEA
     */

    public function limpiarLineas()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codUsr   = $input['cod_usr']   ?? $_GET['cod_usr']   ?? null;
        $codLinea = $input['cod_linea'] ?? $_GET['cod_linea'] ?? null;

        if (!$codUsr || !$codLinea) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'DATOS INCOMPLETOS'
            ];
        }

        $productoCache = CacheHelper::obtenerProductoLinea($codUsr);

        if ($productoCache && isset($productoCache['producto_linea'])) {
            foreach ($productoCache['producto_linea'] as $p) {
                if (trim($p['cod_linea']) === trim($codLinea)) {
                    http_response_code(409);
                    return [
                        'ok' => false,
                        'message' => 'NO SE PUEDE ELIMINAR: TIENE PRODUCTO REGISTRADO'
                    ];
                }
            }
        }

        $soporteModel = new SoporteModel();

        $soportes = $soporteModel->obtenerSoportesPorLinea($codLinea, $_GET['cod_turno'] ?? '');

        if (!empty($soportes)) {
            http_response_code(409);
            return [
                'ok' => false,
                'message' => 'NO SE PUEDE ELIMINAR: TIENE SOPORTES REGISTRADOS'
            ];
        }

        $lineasActuales = CacheHelper::obtenerLineas($codUsr);

        if (!$lineasActuales || !isset($lineasActuales['lineas'])) {
            return ['ok' => true];
        }

        $nuevas = array_filter(
            $lineasActuales['lineas'],
            fn($l) => trim($l['cod_linea']) !== trim($codLinea)
        );

        CacheHelper::guardarLineas(array_values($nuevas), $codUsr);

        return ['ok' => true];
    }

    /* 
       PRODUCTO POR LINEA
     */

    public function guardarProductoLinea()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codUsr = $input['cod_usr'] ?? null;
        $codLinea = $input['cod_linea'] ?? null;
        $productos = $input['productos'] ?? null;

        if (!$codUsr || !$codLinea || !is_array($productos)) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'DATOS INVÁLIDOS'
            ];
        }

        CacheHelper::guardarProductoLinea([
            'cod_linea' => $codLinea,
            'productos' => $productos
        ], $codUsr);

        return [
            'ok' => true,
            'message' => 'PRODUCTO LINEA GUARDADO'
        ];
    }

    public function obtenerProductoLinea()
    {
        $codUsr = $_GET['cod_usr'] ?? null;

        if (!$codUsr) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'USUARIO REQUERIDO'
            ];
        }

        return [
            'ok' => true,
            'data' => CacheHelper::obtenerProductoLinea($codUsr)
        ];
    }

    public function limpiarProductoLinea()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $codUsr = $input['cod_usr'] ?? null;

        if (!$codUsr) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'USUARIO REQUERIDO'
            ];
        }

        CacheHelper::limpiarProductoLinea($codUsr);

        return ['ok' => true];
    }

    /* 
       ESTADO GLOBAL TURNO
     */

    public function estadoGlobal()
    {
        $estado = $this->model->obtenerTurnoActivo();

        return [
            'ok' => true,
            'turno_activo' => $estado !== null
        ];
    }

    /* 
       ULTIMO TURNO
     */

    public function ultimoTurno()
    {
        $data = $this->model->obtenerUltimoTurno();

        return [
            'ok' => true,
            'data' => $data
        ];
    }

    /* 
       AGREGAR PLAN CACHE
     */

    public function agregarPlanCache()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $planesNuevos = $input['planes'] ?? [];

        if (!is_array($planesNuevos) || count($planesNuevos) === 0) {
            http_response_code(400);
            return [
                'ok' => false,
                'message' => 'PLANES REQUERIDOS'
            ];
        }

        $cache = CacheHelper::obtenerPlan();
        $planesActuales = $cache['planes'] ?? [];

        $map = [];

        foreach ($planesActuales as $p) {
            $map[$p['codParte']] = $p;
        }

        foreach ($planesNuevos as $p) {
            $map[$p['codParte']] = $p;
        }

        $planesFinal = array_values($map);

        $usuario = $cache['usuario'] ?? 'system';

        CacheHelper::guardarPlan($planesFinal, $usuario);

        return [
            'ok' => true,
            'data' => $planesFinal
        ];
    }
}