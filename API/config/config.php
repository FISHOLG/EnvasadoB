<?php
ini_set('display_errors', '0');          // NO mostrar en respuesta
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');               // SÍ guardar en log
ini_set('error_reporting', E_ALL);

ini_set(
    'error_log',
    __DIR__ . '/../logs/backend.log'
);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const BD_USE = "produccion";
const DB_DATA = [
    'prueba' => ["rf_prueba", "rf_prueba", "ORCL_FISHOLG_WEB"],
    'produccion' => ["ref_fisholg", "ref_fisholg", "ORCL_FISHOLG_WEB"]
];

const APP_NAME = '/envasado/api';
