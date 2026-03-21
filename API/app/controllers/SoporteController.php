<?php

require_once __DIR__ . '/../models/SoporteModel.php';
require_once __DIR__ . '/../models/TurnoEnvasadoModel.php';
require_once __DIR__ . '/../helper/CacheHelper.php';

class SoporteController
{

    /* 
       LISTAR MAESTRO
     */
    public function listar()
{
    try {

        /* OBTENER TURNO ACTIVO */
        $turnoModel = new TurnoEnvasadoModel();
        $estadoTurno = $turnoModel->obtenerTurnoActivo();

        if (
            empty($estadoTurno) ||
            empty($estadoTurno['aperturado']) ||
            empty($estadoTurno['cod_tur_env'])
        ) {
            http_response_code(400);

            return [
                "ok" => false,
                "message" => "NO HAY TURNO ACTIVO"
            ];
        }

        $codTurno = trim($estadoTurno['cod_tur_env']);

        /* LISTAR SOPORTES DEL TURNO */
        $model = new SoporteModel();
        $data  = $model->listar($codTurno);

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


    /* ASIGNAR SOPORTES*/
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

            // Validar turno activo
            $turnoModel = new TurnoEnvasadoModel();
            $estadoTurno = $turnoModel->obtenerTurnoActivo();

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

            // Obtener origen
    //         $codOrigen = $model->obtenerOrigenPorUsuario($codUsr);
    //         if (empty($codOrigen)) {
    //             http_response_code(400);
    //             return [
    //         "ok" => false,
    //         "message" => "EL USUARIO NO TIENE ORIGEN CONFIGURADO"
    //     ];
    // }

            // Limpiar anteriores
            // $model->eliminar($codLinea, null, $codTurno);

            if (count($soportes) === 0) {
                return [
                    "ok" => true,
                    "message" => "SOPORTES ELIMINADOS",
                    "data" => []
                ];
            }

            // INSERTAR NUEVOS SOPORTES / PARIHUELAS
            $model->insertarMultiplesSoportes(
                $soportes,
                $codLinea,
                $codTurno,
                $codUsr,
            );

            $dataActual = $model->obtenerSoportesPorLinea($codLinea, $codTurno);

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

    // LISTAR LOS TIPOS DE ENVASE (LUGAR O ENVASE DONDE VA EL PRODUCTO)
    public function obtenerTiposEnvase()
{
    try {

        $model = new SoporteModel();
        $data = $model->obtenerTiposEnvase();

        return [
            "ok" => true,
            "data" => $data
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL LISTAR TIPOS DE ENVASE",
            "error" => $e->getMessage()
        ];
    }
}


    /* FINALIZAR PARIHUELA*/
    public function finalizar()
{
    try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input["cod_enva_s"]) ||
            empty($input["cod_linea"])
        ) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "DATOS INVÁLIDOS"
            ];
        }

        $model = new SoporteModel();

        $codSiguiente = $model->finalizarParihuela(
            trim($input["cod_enva_s"]),
            trim($input["cod_linea"])
        );

        $dataActual = $model->obtenerSoportesPorLinea(
            trim($input["cod_linea"]),
            trim($_GET['cod_turno'] ?? '')
        );

        return [
            "ok" => true,
            "message" => "PARIHUELA FINALIZADA",
            "data" => [
                "finalizado" => trim($input["cod_enva_s"]),
                "siguiente"  => $codSiguiente,
                "lista"      => $dataActual
            ]
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL FINALIZAR PARIHUELA",
            "error" => $e->getMessage()
        ];
    }
}
    /* ACTUALIZAR ESTADO*/
        public function actualizarEstado()
{
    try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input["cod_genva_s"]) ||
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
            trim($input["cod_genva_s"]),
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
    /* ELIMINAR */
public function eliminarSoporte()
{
    try {

        $input = json_decode(file_get_contents("php://input"), true);

        $codLinea  = $input["cod_linea"] ?? null;
        $codGenvaS = $input["cod_genva_s"] ?? null;
        $codEnvaS  = $input["cod_enva_s"] ?? null;

        if (!$codLinea || !$codGenvaS || !$codEnvaS) {
            return [
                "ok" => false,
                "message" => "Datos inválidos."
            ];
        }

        /* OBTENER TURNO ACTIVO */

        $turnoModel = new TurnoEnvasadoModel();
        $estadoTurno = $turnoModel->obtenerUltimoTurno();

        if (
            empty($estadoTurno) ||
            empty($estadoTurno['cod_tur_env']) ||
            empty($estadoTurno['cod_usr'])
        ) {
            return [
                "ok" => false,
                "message" => "No hay turno activo."
            ];
        }

        $codTurno = $estadoTurno["cod_tur_env"];
        $codUsr   = $estadoTurno["cod_usr"];

        $model = new SoporteModel();

        $model->eliminarSoporteDeLinea(
            $codGenvaS,
            $codLinea,
            $codTurno,
            $codUsr,
            $codEnvaS
        );

        return [
            "ok" => true,
            "message" => "Soporte eliminado correctamente"
        ];

    } catch (Throwable $e) {

        return [
            "ok" => false,
            "message" => $e->getMessage()
        ];
    }
}

    /* OBTENER POR SOPORTE POR LINEA */
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

        /* OBTENER TURNO ACTIVO */
        $turnoModel = new TurnoEnvasadoModel();
        $estadoTurno = $turnoModel->obtenerTurnoActivo();

        if (
            empty($estadoTurno) ||
            empty($estadoTurno['aperturado']) ||
            empty($estadoTurno['cod_tur_env'])
        ) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "NO HAY TURNO ACTIVO"
            ];
        }

        $codTurno = trim($estadoTurno['cod_tur_env']);

        $model = new SoporteModel();

        $data = $model->obtenerSoportesPorLinea(
            trim($codLinea),
            $codTurno
        );

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


    /* INSERTAR DETALLE */
    public function insertarDetalle()
{
    try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input["cod_genva_s"]) ||
            empty($input["cod_art_cong"]) ||
            !isset($input["kilos"]) ||
            empty($input["cod_tipo_enva"]) ||
            empty($input["cod_parte_producc"]) ||
            empty($input["cod_usr"])
        ) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "DATOS INVÁLIDOS"
            ];
        }

        $model = new SoporteModel();

        $model->insertarDetalle(
            trim($input["cod_genva_s"]),
            trim($input["cod_art_cong"]),
            floatval($input["kilos"]),
            intval($input["cantidad"]),
            trim($input["cod_tipo_enva"]),
            trim($input["cod_parte_producc"]),
            trim($input["cod_usr"])
        );

        return [
            "ok" => true,
            "message" => "REGISTRADO CORRECTAMENTE"
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL INSERTAR REGISTRO",
            "error" => $e->getMessage()
        ];
    }
}
    /* OBTENER DETALLE*/
    public function obtenerDetalleP()
{
    try {

        $codGenvaS = isset($_GET["cod_genva_s"]) 
            ? trim($_GET["cod_genva_s"]) 
            : null;

        if (!$codGenvaS) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "COD_GENVA_S REQUERIDO"
            ];
        }

        $model = new SoporteModel();

        $data = $model->obtenerDetalleP($codGenvaS);

        return [
            "ok" => true,
            "data" => $data ?? []
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL OBTENER DETALLE",
            "error" => $e->getMessage()
        ];
    }
}

    //OBTENER SOPORTES FINALIZADOS

public function obtenerSoportesFinalizados()
{
    try {

        $turnoModel = new TurnoEnvasadoModel();
        $estadoTurno = $turnoModel->obtenerTurnoActivo();

        if (
            empty($estadoTurno) ||
            empty($estadoTurno['aperturado']) ||
            empty($estadoTurno['cod_tur_env'])
        ) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "NO HAY TURNO ACTIVO"
            ];
        }

        $codTurno = trim($estadoTurno['cod_tur_env']);

        $model = new SoporteModel();
        $data  = $model->obtenerSoportesFinalizados($codTurno);

        $cache = CacheHelper::obtenerSoportesMaquina('*');

        $ocupados = [];

        if (is_array($cache)) {

            foreach ($cache as $maq => $soportes) {

                if (!is_array($soportes)) {
                    continue;
                }

                foreach ($soportes as $s) {

                    if (is_array($s) && isset($s["cod_genva_s"])) {
                        $ocupados[] = $s["cod_genva_s"];
                    } else {
                        $ocupados[] = $s;
                    }

                }

            }

        }

        if (!empty($ocupados)) {

            $data = array_filter($data, function ($item) use ($ocupados) {

                return !in_array($item["COD_GENVA_S"], $ocupados);

            });

            $data = array_values($data);

        }

        return [
            "ok" => true,
            "data" => $data
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL OBTENER SOPORTES FINALIZADOS",
            "error" => $e->getMessage()
        ];
    }
}

// PASAR DE ESTADO 2 A 1
public function regresarSoporteLinea()
{
    try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input["cod_genva_s"]) ||
            empty($input["cod_linea"])
        ) {
            http_response_code(400);
            return [
                "ok" => false,
                "message" => "DATOS INCOMPLETOS"
            ];
        }

        $codGenvaS       = trim($input["cod_genva_s"]);
        $codLineaDestino = trim($input["cod_linea"]);

        /* ================= TURNO ================= */
        $turnoModel = new TurnoEnvasadoModel();
        $turno = $turnoModel->obtenerTurnoActivo();

        if (!$turno || !$turno['aperturado']) {
            return [
                "ok" => false,
                "message" => "NO HAY TURNO ACTIVO"
            ];
        }

        $codTurno = $turno['cod_tur_env'];

        $model = new SoporteModel();

        /* ================= VALIDAR ESTADO ================= */
        $conn = conectarDB();

        $sql = "SELECT ESTADO 
                FROM GES_ENVA_SOPORTE 
                WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS)";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":codGenvaS", $codGenvaS);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        oci_close($conn);

        if (!$row) {
            throw new Exception("El soporte no existe.");
        }

        if (intval($row["ESTADO"]) !== 2) {
            throw new Exception("El soporte no está en estado FINALIZADO.");
        }

        /* ================= MOVER ================= */
        $model->moverSoporteLinea(
            $codGenvaS,
            $codLineaDestino,
            $codTurno
        );

        /* ================= ACTIVAR ================= */
        $model->actualizarEstado($codGenvaS, 1);

        return [
            "ok" => true,
            "message" => "SOPORTE MOVIDO CORRECTAMENTE"
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "ERROR AL REGRESAR SOPORTE",
            "error" => $e->getMessage()
        ];
    }
}

// 

        /* PASAR SOPORTE A CONGELADO*/
public function pasarACongelado()
        {
            try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input["cod_genva_s"]) ||
            empty($input["cod_maq"])
        ) {
            return [
                "ok" => false,
                "message" => "Datos incompletos"
            ];
        }

        $codGenvaS      = trim($input["cod_genva_s"]);
        $codMaq         = trim($input["cod_maq"]);
        $tipo           = trim($input["tipo"] ?? "");
        $codProduccion  = trim($input["cod_produccion"] ?? "");

        /* ACTUALIZAR ESTADO EN BD */

        $model = new SoporteModel();

        $model->pasarACongelado($codGenvaS);

        /*  GUARDAR EN CACHE */

        CacheHelper::guardarSoporteMaquina(
            $codMaq,
            $codGenvaS,
            $tipo,
            $codProduccion
        );

        return [
            "ok" => true,
            "message" => "Soporte asignado a máquina"
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "Error",
            "error" => $e->getMessage()
        ];
    }
}

// PASAR DE CONGELADO A FINALIZAADO  

        public function devolverAFinalizado()
        {
            try {

        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input["cod_genva_s"])) {
            return [
                "ok" => false,
                "message" => "Código requerido"
            ];
        }

        $codGenvaS = trim($input["cod_genva_s"]);

        /* ACTUALIZAR ESTADO EN BD */

        $model = new SoporteModel();
        $model->devolverAFinalizado($codGenvaS);

        /* ELIMINAR DEL CACHE */

        CacheHelper::eliminarSoporteMaquina($codGenvaS);

        return [
            "ok" => true,
            "message" => "Soporte devuelto a finalizado"
        ];

    } catch (Throwable $e) {

        http_response_code(500);

        return [
            "ok" => false,
            "message" => "Error al devolver soporte",
            "error" => $e->getMessage()
        ];
    }
}

//SOPORTES DE ESTADO 3 = CONGELADO
//  LEER CACHE DE MAQUINAS
public function obtenerSoportesMaquina()
{
    $codMaq = $_GET["cod_maq"] ?? null;

    if(!$codMaq){
        return [
            "ok"=>false,
            "message"=>"COD_MAQ requerido"
        ];
    }

    $data = CacheHelper::obtenerSoportesMaquina($codMaq);

    return [
        "ok"=>true,
        "data"=>$data
    ];
}
}