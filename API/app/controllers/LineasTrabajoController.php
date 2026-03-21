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
    $usuarioActual = $_GET['cod_usr'] ?? null;

    $lineasBase = $this->model->listar();

    // Obtener mapa real de ocupación
    $ocupadasMap = CacheHelper::obtenerLineasConUsuario();

    $resultado = [];

    foreach ($lineasBase as $row) {

        $codLinea = $row['cod_linea'];
        $usuarioLinea = $ocupadasMap[$codLinea] ?? null;

        $resultado[] = [
            'cod_linea' => $codLinea,
            'descr'     => $row['descr'],
            'usuario'   => $usuarioLinea
        ];
    }

    return [
        'ok' => true,
        'data' => $resultado
    ];
}
}
