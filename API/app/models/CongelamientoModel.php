<?php

require_once __DIR__ . '/../../config/db.php';

class CongelamientoModel
{

    public function registrarDetalle(
        string $codMaquina,
        string $codTel,
        int $gesItem,
        float $kilos,
        float $cantidad,
        string $codUsr
    ): bool {

        $conn = conectarDB();

        $sql = "
            INSERT INTO CONGELAMIENTO_DET
            (
                COD_CONGE_MAQUI,
                COD_TEL,
                GES_ITEM,
                COD_USR,
                KILOS,
                CANTIDAD
            )
            VALUES
            (
                :maq,
                :tel,
                :gesItem,
                :usr,
                :kilos,
                :cantidad
            )
        ";

        $stmt = oci_parse($conn, $sql);

        oci_bind_by_name($stmt, ":maq", $codMaquina);
        oci_bind_by_name($stmt, ":tel", $codTel);
        oci_bind_by_name($stmt, ":gesItem", $gesItem);
        oci_bind_by_name($stmt, ":usr", $codUsr);
        oci_bind_by_name($stmt, ":kilos", $kilos);
        oci_bind_by_name($stmt, ":cantidad", $cantidad);

        $ok = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$ok) {
            $error = oci_error($stmt);
            throw new Exception($error["message"]);
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return true;
    }

}