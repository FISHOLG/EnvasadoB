<?php

require_once __DIR__ . '/../../config/db.php';

class LineasTrabajoModel
{
 
    public function listar()
    {
        $conn = conectarDB();

        $sql = "
            SELECT
                COD_LINEA,
                DESCR,
                COD_USR
            FROM PRODUCC_LINEAS
            ORDER BY DESCR
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $data = [];

        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = [

                'cod_linea' => $row['COD_LINEA'], 
                'descr'     => $row['DESCR'],     
                'usuario'   => $row['COD_USR'],
                // 'estado'    => 'ACTIVA',         
            ];
        }

        oci_free_statement($stmt);

        return $data;
    }
}
