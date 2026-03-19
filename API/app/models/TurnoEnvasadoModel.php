<?php

require_once __DIR__ . '/../../config/db.php';

class TurnoEnvasadoModel
{
    public function ejecutarTurno(string $codUsr, string $accion): bool
    {
        $conn = conectarDB();

        $sql = "BEGIN usp_INSERTA_actualiza_TURNO_ENVASADO(:usr, :accion); END;";
        $stmt = oci_parse($conn, $sql);

        oci_bind_by_name($stmt, ':usr', $codUsr);
        oci_bind_by_name($stmt, ':accion', $accion);

        $ok = oci_execute($stmt);

        if (!$ok) {
            $e = oci_error($stmt);
            error_log("Error ejecutarTurno: " . $e['message']);
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return $ok ? true : false;
    }

    public function obtenerEstadoTurno(): array
    {
        $conn = conectarDB();

        $sql = "
        SELECT *
        FROM (
            SELECT
                cod_tur_env,
                flag_estado,
                fecha_reg,
                fecha_ini,
                cod_usr,
                fecha_fin
            FROM TURNO_ENVASADO
            WHERE flag_estado = 1
            ORDER BY fecha_reg DESC
        )
        WHERE ROWNUM = 1
    ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        if (!$row) {
            return [
                'aperturado' => false,
                'flag_estado' => null,
                'fecha_reg'  => null,
                'cod_usr'    => null,
                'fecha_ini'  => null,
                'fecha_fin'  => null,
            ];
        }

        return [
            'aperturado' => $row['FLAG_ESTADO'] == '1',
            'flag_estado' => $row['FLAG_ESTADO'],
            'fecha_reg'  => $row['FECHA_REG'] ?? null,
            'fecha_ini'  => $row['FECHA_INI'] ?? null,
            'cod_tur_env' => $row['COD_TUR_ENV'],
            'cod_usr'    => $row['COD_USR'] ?? null,
            'fecha_fin'  => $row['FECHA_FIN'] ?? null,
        ];
    }
}
