<?php

require_once __DIR__ . '/../../config/db.php';

class ArticuloCongeModel
{
    public function obtenerPorEspecies(array $especies): array
    {
        $conn = conectarDB();

        if (count($especies) === 0) {
            return [];
        }

        $lista = implode(",", array_map(function($e){
            return "'" . trim($e) . "'";
        }, $especies));

        $sql = "
            SELECT
                pp.cod_parte_producc,
                pp.especie,
                te.cat_art,
                acs.cod_sec,
                ac.cod_art_cong,
                ac.descr
            FROM PARTE_PRODUCCION pp
            JOIN TG_ESPECIES te
                ON TRIM(pp.especie) = TRIM(te.especie)
            JOIN ART_CONGE_SECCION acs
                ON TRIM(te.especie) = TRIM(acs.especie)
            JOIN ARTICULO_CONGE ac
                ON TRIM(ac.cod_sec) = TRIM(acs.cod_sec)
            WHERE pp.flag_estado = '1'
            AND TRIM(pp.especie) IN($lista)
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $data = [];

        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return $data;
    }
}
