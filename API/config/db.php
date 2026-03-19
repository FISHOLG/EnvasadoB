<?php

require_once __DIR__ . '/config.php';

function conectarDB() {
    if (basename($_SERVER['PHP_SELF'] == 'db.php')) exit;
    $conn = oci_pconnect(DB_DATA[BD_USE][0], DB_DATA[BD_USE][1], DB_DATA[BD_USE][2], 'UTF8');

    if (!$conn) {
        $e = oci_error();
        http_response_code(500);
        die(json_encode([
            'result' => 'error',
            'message' => 'Error de conexión a Oracle',
            'error' => $e['message']
        ]));
    }

    return $conn;
}

