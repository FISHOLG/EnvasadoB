<?php
require_once __DIR__ . '/../../config/db.php';

class MaquinaCongeModel
{
    // public function obtenerMaquinas(): array
    // {
    //     $conn = conectarDB();

    //     $sql = "
    //         SELECT  
    //             mc.COD_MAQ,
    //             m.DESC_MAQ,
    //             mc.FLAG_TIPO
    //         FROM MAQUINA_CONGE mc
    //         INNER JOIN MAQUINA m 
    //             ON m.COD_MAQUINA = mc.COD_MAQ
    //         ORDER BY m.DESC_MAQ
    //     ";

    //     $stmt = oci_parse($conn, $sql);
    //     oci_execute($stmt);

    //     $data = [];

    //     while ($row = oci_fetch_assoc($stmt)) {
    //         $data[] = [
    //             'cod_maquina' => $row['COD_MAQ'],
    //             'desc_maquina' => $row['DESC_MAQ'],
    //             'flag_tipo' => $row['FLAG_TIPO']
    //         ];
    //     }

    //     oci_free_statement($stmt);
    //     oci_close($conn);

    //     return $data;
    // }


    public function obtenerMaquinas(): array
{
    $conn = conectarDB();

    $sql = "
        SELECT  
            mc.COD_MAQ,
            m.DESC_MAQ,
            mc.FLAG_TIPO,
            cm.ESTADO,
            CASE cm.ESTADO
                WHEN 0 THEN 'CREADO'
                WHEN 1 THEN 'CARGA'
                WHEN 2 THEN 'CONGELANDO'
                WHEN 3 THEN 'DESCARGA'
                WHEN 4 THEN 'FINALIZADO'
                ELSE 'SIN ESTADO'
            END AS DESC_ESTADO
        FROM MAQUINA_CONGE mc

        INNER JOIN MAQUINA m 
            ON m.COD_MAQUINA = mc.COD_MAQ

        LEFT JOIN CONGELAMIENTO_MAQUINARIA cm
            ON cm.COD_MAQ = mc.COD_MAQ

        ORDER BY m.DESC_MAQ
    ";

    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);

    $data = [];

    while ($row = oci_fetch_assoc($stmt)) {

        $data[] = [
            'cod_maquina' => $row['COD_MAQ'],
            'desc_maquina' => $row['DESC_MAQ'],
            'flag_tipo' => $row['FLAG_TIPO'],
            'estado' => $row['ESTADO'] ?? 0,
            'estado_desc' => $row['DESC_ESTADO']
        ];
    }

    oci_free_statement($stmt);
    oci_close($conn);

    return $data;
}
}