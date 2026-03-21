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

        $lista = implode(",", array_map(function ($e) {
            return "'" . trim($e) . "'";
        }, $especies));

        /* ================= CONSULTA PRINCIPAL ================= */
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
            AND TRIM(pp.especie) IN ($lista)
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        /* ================= CARGAR SUBCATEGORÍAS ================= */
        $subcategorias = [];

        $sqlSub = "
            SELECT 
                cat_art, 
                desc_sub_cat 
            FROM ARTICULO_SUB_CATEG
        ";

        $stmtSub = oci_parse($conn, $sqlSub);
        oci_execute($stmtSub);

        while ($rowSub = oci_fetch_assoc($stmtSub)) {
            $cat = trim($rowSub['CAT_ART']);
            $sub = trim($rowSub['DESC_SUB_CAT']);

            if (!isset($subcategorias[$cat])) {
                $subcategorias[$cat] = [];
            }

            $subcategorias[$cat][] = $sub;
        }

        oci_free_statement($stmtSub);

        /* ================= PROCESAR DATA ================= */
        $data = [];

        while ($row = oci_fetch_assoc($stmt)) {

            $catArt = trim($row['CAT_ART']);
            $descripcion = strtolower(trim($row['DESCR']));

            $tipo = 'SIN_TIPO';

            if (isset($subcategorias[$catArt])) {
                foreach ($subcategorias[$catArt] as $sub) {

                    if (strpos($descripcion, strtolower($sub)) !== false) {
                        $tipo = $sub;
                        break;
                    }
                }
            }

        // TIPO RESULTADO
            $row['TIPO'] = $tipo;

            $data[] = $row;
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return $data;
    }
}