<?php

require_once __DIR__ . '/../../config/db.php';

class TurnoCongeladoModel
{

    /* ================= EJECUTAR PROCEDIMIENTO TURNO ================= */

    public function ejecutarTurnoCongelado(?string $codTurno, string $usuario, int $flag): bool
    {
        $conn = conectarDB();

        $sql = "BEGIN usp_iniciar_finalizar_turno_conge(:cod_turno, :usuario, :turno, :flag); END;";
        $stmt = oci_parse($conn, $sql);

        if (!$stmt) {
            $e = oci_error($conn);
            throw new Exception($e['message']);
        }

        /* ================= CALCULAR TURNO ================= */
        $hora = (int) date('H');
        $turno = ($hora >= 8 && $hora < 18) ? 'TD' : 'TN';

        /* ================= BINDS ================= */
        oci_bind_by_name($stmt, ':cod_turno', $codTurno);
        oci_bind_by_name($stmt, ':usuario', $usuario);
        oci_bind_by_name($stmt, ':turno', $turno);
        oci_bind_by_name($stmt, ':flag', $flag);

        /* ================= EJECUCIÓN ================= */
        $ok = oci_execute($stmt);

        if (!$ok) {
            $e = oci_error($stmt);
            oci_free_statement($stmt);
            oci_close($conn);

            throw new Exception($e['message']);
        }

        oci_free_statement($stmt);
        oci_close($conn);

        return true;
    }

    /* ================= OBTENER TURNO CONGELADO (SIN FILTRO - COMO ENVASADO) ================= */

    public function obtenerTurnoCongelado(): ?array
    {
        $conn = conectarDB();

        $sql = "
            SELECT cod_tur_conge, cod_usr, fecha_ini, fecha_fin, flag_estado
            FROM TURNO_CONGELAMIENTO
            WHERE fecha_fin IS NULL
            FETCH FIRST 1 ROWS ONLY
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        if (!$row) return null;

        return [
            'cod_tur_conge' => $row['COD_TUR_CONGE'],
            'cod_usr'       => $row['COD_USR'],
            'fecha_ini'     => $row['FECHA_INI'],
            'fecha_fin'     => $row['FECHA_FIN'],
            'flag_estado'   => $row['FLAG_ESTADO'],
            'aperturado'    => $row['FLAG_ESTADO'] == '1'
        ];
    }

    /* ================= OBTENER ÚLTIMO TURNO ================= */

    public function obtenerUltimoTurnoCongelado(): ?array
    {
        $conn = conectarDB();

        $sql = "
            SELECT * FROM (
                SELECT cod_tur_conge, cod_usr, fecha_ini, fecha_fin
                FROM TURNO_CONGELAMIENTO
                WHERE fecha_fin IS NOT NULL
                ORDER BY fecha_fin DESC
            )
            WHERE ROWNUM = 1
        ";

        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        if (!$row) return null;

        return [
            'cod_tur_conge' => $row['COD_TUR_CONGE'],
            'cod_usr'       => $row['COD_USR'],
            'fecha_ini'     => $row['FECHA_INI'],
            'fecha_fin'     => $row['FECHA_FIN']
        ];
    }
}