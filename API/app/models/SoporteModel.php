<?php

require_once __DIR__ . '/../../config/db.php';

class SoporteModel
{
    /**
     * Obtiene todos los soportes del maestro
     */
    public function listar(): array
    {
        $conn = conectarDB();

        $sql = "SELECT 
                e.TIPO,
                e.COD_PRODUCCION,
                e.COD_ENVA_S,
                e.COD_USR,
                NVL(g.ESTADO, 0) AS ESTADO,
                g.COD_LINEA
            FROM ENVA_SOPORTE e
            LEFT JOIN GES_ENVA_SOPORTE g 
                ON g.COD_ENVA_S = e.COD_ENVA_S
                AND g.FECHA_REG = (
                    SELECT MAX(g2.FECHA_REG)
                    FROM GES_ENVA_SOPORTE g2
                    WHERE g2.COD_ENVA_S = e.COD_ENVA_S
                )
            ORDER BY e.COD_ENVA_S ASC";

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

  
    /* 
       INSERTAR MÚLTIPLES 
     */
    public function insertarMultiplesSoportes(
        array $soportes,
        string $codLinea,
        string $codTurno,
        string $codUsr,
        string $codOrigen
    ): bool {

        if (count($soportes) === 0) {
            return false;
        }

        $conn = conectarDB();

        $sql = "INSERT INTO GES_ENVA_SOPORTE (
                    COD_GENVA_S,
                    COD_ENVA_S,
                    FECHA_REG,
                    ESTADO,
                    COD_ORIGEN,
                    COD_USR,
                    COD_TUR_ENV,
                    COD_LINEA
                ) VALUES (
                    USF_NUM_TABS_CONGELA('GES'),
                    :codEnvaS,
                    SYSDATE,
                    :estado,
                    :codOrigen,
                    :codUsr,
                    :codTurno,
                    :codLinea
                )";

        $stmt = oci_parse($conn, $sql);

        $index = 0;

        foreach ($soportes as $codEnvaS) {

            $codEnvaS = trim($codEnvaS);
            $estado = ($index < 2) ? 1 : 2;

            oci_bind_by_name($stmt, ":codEnvaS", $codEnvaS);
            oci_bind_by_name($stmt, ":estado", $estado);
            oci_bind_by_name($stmt, ":codLinea", $codLinea);
            oci_bind_by_name($stmt, ":codTurno", $codTurno);
            oci_bind_by_name($stmt, ":codUsr", $codUsr);
            oci_bind_by_name($stmt, ":codOrigen", $codOrigen);

            $ok = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

            if (!$ok) {
                $error = oci_error($stmt);
                oci_rollback($conn);
                throw new Exception($error['message']);
            }

            $index++;
        }

        oci_commit($conn);
        oci_free_statement($stmt);
        oci_close($conn);

        return true;
    }

    /* 
       ACTUALIZAR ESTADO DE SOPORTE
     */
    public function actualizarEstado(
        string $codEnvaS,
        string $codLinea,
        int $estado
    ): bool {

        $conn = conectarDB();

        $sql = "UPDATE GES_ENVA_SOPORTE
                SET ESTADO = :estado
                WHERE COD_ENVA_S = :codEnvaS
                AND COD_LINEA = :codLinea";

        $stmt = oci_parse($conn, $sql);

        oci_bind_by_name($stmt, ":estado", $estado);
        oci_bind_by_name($stmt, ":codEnvaS", $codEnvaS);
        oci_bind_by_name($stmt, ":codLinea", $codLinea);

        $ok = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$ok) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return true;
    }

  /* 
   ELIMINAR SOPORTES (UNIFICADO)
   - Si se envía codEnvaS → elimina uno
   - Si es null → elimina todos de la línea
*/
public function eliminar(
    string $codLinea,
    ?string $codEnvaS = null,
    ?string $codTurno = null
): bool {

    $conn = conectarDB();

    $sql = "DELETE FROM GES_ENVA_SOPORTE
            WHERE COD_LINEA = :codLinea";

    if ($codEnvaS !== null) {
        $sql .= " AND COD_ENVA_S = :codEnvaS";
    }

    if ($codTurno !== null) {
        $sql .= " AND COD_TUR_ENV = :codTurno";
    }

    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ":codLinea", $codLinea);

    if ($codEnvaS !== null) {
        oci_bind_by_name($stmt, ":codEnvaS", $codEnvaS);
    }

    if ($codTurno !== null) {
        oci_bind_by_name($stmt, ":codTurno", $codTurno);
    }

    $ok = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if (!$ok) {
        $error = oci_error($stmt);
        throw new Exception($error['message']);
    }

    oci_free_statement($stmt);
    oci_close($conn);

    return true;
}

    /* 
       OBTENER SOPORTES POR LÍNEA
     */
    public function obtenerSoportesPorLinea(string $codLinea): array
    {
        $conn = conectarDB();

        $sql = "SELECT 
                    g.COD_ENVA_S,
                    g.ESTADO,
                    e.TIPO,
                    e.COD_PRODUCCION
                FROM GES_ENVA_SOPORTE g
                JOIN ENVA_SOPORTE e ON e.COD_ENVA_S = g.COD_ENVA_S
                WHERE g.COD_LINEA = :codLinea
                ORDER BY g.FECHA_REG ASC";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":codLinea", $codLinea);
        oci_execute($stmt);

        $data = [];

        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return $data;
    }

    /* 
       OBTENER COD_ORIGEN
     */
    public function obtenerCodOrigen(): ?string
    {
        $conn = conectarDB();

        $sql = "SELECT COD_ORIGEN 
                FROM ORIGEN
                WHERE ROWNUM = 1";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        return $row['COD_ORIGEN'] ?? null;
    }
}