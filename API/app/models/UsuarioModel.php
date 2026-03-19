<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helper/encriptado.php';

class UsuarioModel
{
    public function login(string $usuario, string $clave)
    {
        $conn = conectarDB();

        $sql = " SELECT COD_USR, NOMBRE, CLAVE, PERFIL
            FROM USUARIO
            WHERE COD_USR = :usuario
              AND PERFIL = 'OPER_TI' ";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':usuario', $usuario);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        if (!$row || empty($row['CLAVE'])) {
            error_log("USUARIO NO ENCONTRADO: {$usuario}");
            return null;
        }

        $claveDesencriptada = encriptado::decrypt($row['CLAVE']);

        // ===== DEBUG CLAVES =====
        error_log("LOGIN DEBUG --------------------");
        error_log("USUARIO        : {$usuario}");
        error_log("CLAVE BD       : {$row['CLAVE']}");
        error_log("CLAVE DESENC   : [" . $claveDesencriptada . "]");
        error_log("CLAVE INGRESO  : [" . $clave . "]");
        error_log("--------------------------------");

        if (trim($claveDesencriptada) !== trim($clave)) {
            error_log("Clave incorrecta para {$usuario}");
            return null;
        }

        error_log("LOGIN OK → {$usuario}");

        return [
            'cod_usr' => $row['COD_USR'],
            'nombre'  => $row['NOMBRE'],
            'perfil'  => $row['PERFIL'],
        ];
    }
}
