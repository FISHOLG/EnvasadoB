<?php

require_once __DIR__ . '/../models/TurnoCongeladoModel.php';

class TurnoCongeladoController
{
    private TurnoCongeladoModel $model;

    public function __construct()
    {
        $this->model = new TurnoCongeladoModel();
    }

    /* ================= EJECUTAR TURNO (ABRIR / CERRAR) ================= */
    public function ejecutarTurnoCongelado()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $codUsr = $input['cod_usr'] ?? null;
        $accion = $input['accion'] ?? null;

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

                $turno = $this->model->obtenerTurnoCongelado();

                /* VALIDAR SI YA EXISTE ACTIVO */
                if ($turno && $turno['flag_estado'] == '1') {
                    return [
                        'ok' => false,
                        'message' => 'YA EXISTE UN TURNO ACTIVO'
                    ];
                }

                /* ================= REGISTRAR ================= */
                $this->model->ejecutarTurnoCongelado(null, $codUsr, 1);

                /* ================= OBTENER RECIÉN CREADO ================= */
                $turno = $this->model->obtenerTurnoCongelado();

                if (!$turno || empty($turno['cod_tur_conge'])) {
                    return [
                        'ok' => false,
                        'message' => 'NO SE PUDO CREAR EL TURNO'
                    ];
                }

                /* ================= INICIAR ================= */
                $this->model->ejecutarTurnoCongelado(
                    $turno['cod_tur_conge'],
                    $codUsr,
                    2
                );
            }

            /* ================= CERRAR TURNO ================= */
            elseif ($accion === '3') {

                $turno = $this->model->obtenerTurnoCongelado();

                if (!$turno || $turno['flag_estado'] != '1') {
                    return [
                        'ok' => false,
                        'message' => 'NO EXISTE TURNO ACTIVO'
                    ];
                }

                $this->model->ejecutarTurnoCongelado(
                    $turno['cod_tur_conge'],
                    $codUsr,
                    3
                );
            }

            /* ================= ACCIÓN INVÁLIDA ================= */
            else {
                http_response_code(400);
                return [
                    'ok' => false,
                    'message' => 'ACCIÓN INVÁLIDA'
                ];
            }

            /* ================= RESPUESTA ================= */

            $estadoTurno = $this->model->obtenerTurnoCongelado();

            if (!$estadoTurno) {
                $estadoTurno = [
                    'cod_tur_conge' => '',
                    'cod_usr'       => '',
                    'fecha_ini'     => '',
                    'fecha_fin'     => '',
                    'flag_estado'   => '2',
                    'aperturado'    => false
                ];
            }

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

    /* ================= ESTADO TURNO ================= */
    public function estadoTurnoCongelado()
    {
        $estado = $this->model->obtenerTurnoCongelado();

        return [
            'ok' => true,
            'data' => $estado ?? [
                'cod_tur_conge' => '',
                'cod_usr'       => '',
                'fecha_ini'     => '',
                'fecha_fin'     => '',
                'flag_estado'   => '2',
                'aperturado'    => false
            ]
        ];
    }

    /* ================= ESTADO GLOBAL TURNO ================= */
    public function estadoGlobalTurnoCongelado()
    {
        $estado = $this->model->obtenerTurnoCongelado();

        return [
            'ok' => true,
            'turno_activo' => $estado && $estado['flag_estado'] == '1'
        ];
    }

    /* ================= ÚLTIMO TURNO ================= */
    public function ultimoTurnoCongelado()
    {
        $data = $this->model->obtenerUltimoTurnoCongelado();

        return [
            'ok' => true,
            'data' => $data
        ];
    }
}