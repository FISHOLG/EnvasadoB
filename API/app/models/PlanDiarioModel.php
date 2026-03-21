<?php

require_once __DIR__ . '/../../config/db.php';

class PlanDiarioModel
{
    /**
     * Listar planes diarios desde PARTE_PRODUCCION
     * ESPECIE / FECHA
     */
    public function listarPlanes()
    {
        $conn = conectarDB();

        $sql = "
            SELECT
                COD_PARTE_PRODUCC,
                TO_CHAR(FECHA_PART, 'DD/MM/YYYY') AS FECHA_PART,
                TRIM(ESPECIE) AS ESPECIE,
                FLAG_ESTADO
            FROM PARTE_PRODUCCION
            WHERE FLAG_ESTADO = 1
            ORDER BY COD_PARTE_PRODUCC DESC
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $data = [];

        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = [
                'cod_parte' => $row['COD_PARTE_PRODUCC'],
                'fecha'     => $row['FECHA_PART'],
                'especie'   => $row['ESPECIE'],
                'estado'    => (int)$row['FLAG_ESTADO'],
            ];
        }

        oci_free_statement($stmt);

        return [
            'ok'   => true,
            'data' => $data
        ];
    }
}
