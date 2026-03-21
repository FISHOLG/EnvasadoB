<?php

require_once __DIR__ . '/../../config/db.php';

class SoporteModel
{

/* LISTAR SOPORTES DISPONIBLES - ESTADO 0 | 1*/
public function listar(string $codTurno): array
{
    $conn = conectarDB();

    $sql = "SELECT  
                e.TIPO,
                e.COD_PRODUCCION,
                e.COD_ENVA_S,
                g.COD_GENVA_S,
                t.COD_LINEA,
                NVL(g.ESTADO,0) AS ESTADO
            FROM ENVA_SOPORTE e
            LEFT JOIN GES_ENVA_SOPORTE g 
                ON g.COD_ENVA_S = e.COD_ENVA_S
            LEFT JOIN TURNO_ENVA_LINEA t
                ON t.COD_GENVA_S = g.COD_GENVA_S
            WHERE NVL(g.ESTADO,0) IN (0,1)
            AND (
                t.COD_TUR_ENV = :codTurno
                OR t.COD_TUR_ENV IS NULL
            )
            ORDER BY e.COD_ENVA_S ASC";

    $stmt = oci_parse($conn,$sql);

    oci_bind_by_name($stmt,":codTurno",$codTurno);

    oci_execute($stmt);

    $data = [];

    while ($row = oci_fetch_assoc($stmt)) {
        $data[] = $row;
    }

    oci_free_statement($stmt);
    oci_close($conn);

    return $data;
}


/* INSERTAR SOPORTES*/
public function insertarMultiplesSoportes( array $soportes, string $codLinea, string $codTurno, string $codUsr
): bool {

    if (empty($soportes)) {
        return true;
    }

    $conn = conectarDB();

    /* CONTAR SOPORTES EN ESA LINEA Y TURNO */

    $sqlCount = "SELECT COUNT(*) TOTAL FROM GES_ENVA_SOPORTE g JOIN TURNO_ENVA_LINEA t ON t.COD_GENVA_S = g.COD_GENVA_S WHERE g.ESTADO IN (0,1) AND t.COD_LINEA = :codLinea AND t.COD_TUR_ENV = :codTurno";

    $stmtCount = oci_parse($conn,$sqlCount);

    oci_bind_by_name($stmtCount,":codLinea",$codLinea);
    oci_bind_by_name($stmtCount,":codTurno",$codTurno);

    oci_execute($stmtCount);

    $row = oci_fetch_assoc($stmtCount);

    $total = (int)($row["TOTAL"] ?? 0);

    oci_free_statement($stmtCount);

    foreach ($soportes as $codEnvaS) {

        $codEnvaS = trim($codEnvaS);

        if ($codEnvaS === "") {
            continue;
        }

        /* EVITAR DUPLICADOS */

        $sqlCheck = "SELECT COUNT(*) TOTAL FROM GES_ENVA_SOPORTE WHERE COD_ENVA_S = :codEnvaS AND ESTADO IN (0,1)";

        $stmtCheck = oci_parse($conn,$sqlCheck);

        oci_bind_by_name($stmtCheck,":codEnvaS",$codEnvaS);

        oci_execute($stmtCheck);

        $rowCheck = oci_fetch_assoc($stmtCheck);

        oci_free_statement($stmtCheck);

        if (($rowCheck["TOTAL"] ?? 0) > 0) {
            continue;
        }

        /* MAXIMO 4 SOPORTES */

        if ($total >= 4) {
            break;
        }

        /* LLAMAR PROCEDIMIENTO */

        $sqlProc = "BEGIN usp_borrar_asignar_tel_ges(NULL, NULL, :codLinea, :codTurno, :codUsr, :codEnvaS, 2); END;";

        $stmtProc = oci_parse($conn,$sqlProc);

        oci_bind_by_name($stmtProc,":codLinea",$codLinea);
        oci_bind_by_name($stmtProc,":codTurno",$codTurno);
        oci_bind_by_name($stmtProc,":codUsr",$codUsr);
        oci_bind_by_name($stmtProc,":codEnvaS",$codEnvaS);

        $ok = oci_execute($stmtProc,OCI_NO_AUTO_COMMIT);

        if(!$ok){
            $error = oci_error($stmtProc);
            oci_rollback($conn);
            throw new Exception($error["message"]);
        }

        oci_free_statement($stmtProc);

        $total++;
    }

    oci_commit($conn);
    oci_close($conn);

    return true;
}

/* ACTUALIZAR ESTADO DE LA PARIHUELA O SOPORTE*/

public function actualizarEstado(
    string $codGenvaS,
    int $estado
): bool {

    $conn = conectarDB();

    $sql = "BEGIN usp_cambiar_estado_ges( :codGenvaS, :estado ); END;";

    $stmt = oci_parse($conn,$sql);

    oci_bind_by_name($stmt,":codGenvaS",$codGenvaS);
    oci_bind_by_name($stmt,":estado",$estado);

    $ok = oci_execute($stmt,OCI_COMMIT_ON_SUCCESS);

    if(!$ok){
        $error = oci_error($stmt);
        throw new Exception($error["message"]);
    }

    oci_free_statement($stmt);
    oci_close($conn);

    return true;
}

/* ELIMINAR SOPORTE*/

public function eliminarSoporteDeLinea( string $codGenvaS, string $codLinea, string $codTurno, string $codUsr, string $codEnvaS
): bool {

    $conn = conectarDB();

    /* BUSCAR COD_TEL */
    $sqlTel = "SELECT COD_TEL FROM TURNO_ENVA_LINEA WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS)";

    $stmtTel = oci_parse($conn,$sqlTel);

    oci_bind_by_name($stmtTel,":codGenvaS",$codGenvaS);
    oci_execute($stmtTel);

    $rowTel = oci_fetch_assoc($stmtTel);

    oci_free_statement($stmtTel);

    /* SI NO EXISTE TEL → NO HAY NADA QUE BORRAR */
    if(!$rowTel){
        oci_close($conn);
        return true;
    }

    $codTel = $rowTel["COD_TEL"];

    /* LLAMAR PROCEDIMIENTO */
    $sql = "BEGIN
                usp_borrar_asignar_tel_ges( :codGenvaS, :codTel, :codLinea, :codTurno, :codUsr, :codEnvaS, 1 );
            END;";

    $stmt = oci_parse($conn,$sql);

    oci_bind_by_name($stmt,":codGenvaS",$codGenvaS);
    oci_bind_by_name($stmt,":codTel",$codTel);
    oci_bind_by_name($stmt,":codLinea",$codLinea);
    oci_bind_by_name($stmt,":codTurno",$codTurno);
    oci_bind_by_name($stmt,":codUsr",$codUsr);
    oci_bind_by_name($stmt,":codEnvaS",$codEnvaS);

    $ok = oci_execute($stmt,OCI_COMMIT_ON_SUCCESS);

    if(!$ok){
        $error = oci_error($stmt);
        throw new Exception($error["message"]);
    }

    oci_free_statement($stmt);
    oci_close($conn);

    return true;
}


/* SOPORTES POR LINEA*/
public function obtenerSoportesPorLinea(string $codLinea, string $codTurno): array
{

$conn = conectarDB();

$sql = "SELECT g.COD_GENVA_S, g.COD_ENVA_S, g.ESTADO, e.TIPO, e.COD_PRODUCCION FROM GES_ENVA_SOPORTE g
        JOIN ENVA_SOPORTE e ON e.COD_ENVA_S = g.COD_ENVA_S
        WHERE g.ESTADO IN (0,1) AND g.COD_GENVA_S IN ( SELECT t.COD_GENVA_S FROM TURNO_ENVA_LINEA t WHERE t.COD_LINEA = :codLinea AND t.COD_TUR_ENV = :codTurno )
        ORDER BY g.FECHA_REG ASC";

        $stmt = oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":codLinea",$codLinea);
        oci_bind_by_name($stmt,":codTurno",$codTurno);
        oci_execute($stmt);

        $data = [];
            while($row = oci_fetch_assoc($stmt)){
            $data[] = $row;
        }

        oci_free_statement($stmt);
        oci_close($conn);

return $data;

}

/* FINALIZAR PARIHUELA */
public function finalizarParihuela(string $codEnvaS,string $codLinea): ?string
{

    $conn = conectarDB();

    $sql = "UPDATE GES_ENVA_SOPORTE SET ESTADO = 2 WHERE TRIM(COD_ENVA_S)=TRIM(:codEnvaS) AND ESTADO = 1";

    $stmt = oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":codEnvaS",$codEnvaS);

    $ok = oci_execute($stmt,OCI_COMMIT_ON_SUCCESS);

    if(!$ok){
        $error = oci_error($stmt);
        throw new Exception($error['message']);
    }

    $rows = oci_num_rows($stmt);

    oci_close($conn);
    if($rows === 0){
        throw new Exception("No se pudo finalizar la parihuela.");
    }

    return null;
}



      /* ORIGEN USUARIO*/
public function obtenerOrigenPorUsuario(string $codUsr): ?string
{

$conn=conectarDB();

$sql="SELECT ORIGEN_ALT FROM USUARIO
WHERE TRIM(COD_USR)=TRIM(:codUsr)";

$stmt=oci_parse($conn,$sql);

oci_bind_by_name($stmt,":codUsr",$codUsr);

oci_execute($stmt);

$row=oci_fetch_assoc($stmt);

oci_free_statement($stmt);
oci_close($conn);      

return $row['ORIGEN_ALT'] ?? null;

}


/* LISTAR TIPOS DE ENVASE */
public function obtenerTiposEnvase(): array
{
    $conn = conectarDB();

    $sql = "SELECT COD_TIPO_ENVA, DESCRIPCION FROM TIPO_ENVA WHERE FLAG_ESTADO = 1 ORDER BY DESCRIPCION";

    $stmt = oci_parse($conn,$sql);
    oci_execute($stmt);
    $data = [];
    while($row = oci_fetch_assoc($stmt)){
        $data[] = $row;
    }

    oci_close($conn);

    return $data;
}

/* INSERTAR DETALLE */
public function insertarDetalle( string $codGenvaS, string $codArtCong, float $kilos, int $cantidad, string $codTipoEnva, string $codParteProducc, string $codUsr): bool {

if($cantidad <= 0){
        throw new Exception("Cantidad debe ser mayor a 0");
    }

    $conn = conectarDB();

/* BUSCAR COD_TEL */
$sqlTel = "SELECT COD_TEL FROM TURNO_ENVA_LINEA WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS)";

$stmtTel = oci_parse($conn,$sqlTel);

oci_bind_by_name($stmtTel,":codGenvaS",$codGenvaS);

oci_execute($stmtTel);

$rowTel = oci_fetch_assoc($stmtTel);

if(!$rowTel){
    throw new Exception("No se encontró COD_TEL para la parihuela.");
}

$codTel = $rowTel["COD_TEL"];

oci_free_statement($stmtTel);


/* INSERT DETALLE */

$sql="INSERT INTO GES_ENVA_SOPORTE_DET( COD_TEL, COD_ART_CONG, KILOS, CANTIDAD, COD_TIPO_ENVA, COD_PARTE_PRODUCC, COD_USR ) VALUES( :codTel, :codArtCong, :kilos, :cantidad, :codTipoEnva, :codParteProducc, :codUsr )";

$stmt = oci_parse($conn,$sql);

oci_bind_by_name($stmt,":codTel",$codTel);
oci_bind_by_name($stmt,":codArtCong",$codArtCong);
oci_bind_by_name($stmt,":kilos",$kilos);
oci_bind_by_name($stmt,":cantidad",$cantidad);
oci_bind_by_name($stmt,":codTipoEnva",$codTipoEnva);
oci_bind_by_name($stmt,":codParteProducc",$codParteProducc);
oci_bind_by_name($stmt,":codUsr",$codUsr);

$ok = oci_execute($stmt,OCI_NO_AUTO_COMMIT);

if(!$ok){
    $error = oci_error($stmt);
    oci_rollback($conn);
    throw new Exception($error["message"]);
}

oci_commit($conn);

oci_free_statement($stmt);
oci_close($conn);

return true;

}

/* DETALLE PARIHUELA*/

public function obtenerDetalleP(string $codGenvaS): array
{

$conn = conectarDB();

/* BUSCAR COD_TEL DESDE PARIHUELA*/

$sqlTel = "SELECT COD_TEL FROM TURNO_ENVA_LINEA WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS)";

$stmtTel = oci_parse($conn, $sqlTel);

oci_bind_by_name($stmtTel, ":codGenvaS", $codGenvaS);

$okTel = oci_execute($stmtTel);

if (!$okTel) {
    $error = oci_error($stmtTel);
    throw new Exception($error['message']);
}

$rowTel = oci_fetch_assoc($stmtTel);

if (!$rowTel) {
    oci_free_statement($stmtTel);
    oci_close($conn);
    return [];
}

$codTel = $rowTel["COD_TEL"];

oci_free_statement($stmtTel);


/* CONSULTA DETALLE PARIHUELA */

$sql = "SELECT d.ITEM, d.COD_ART_CONG, a.DESCR, d.KILOS, d.CANTIDAD, d.COD_TIPO_ENVA, t.DESCRIPCION AS DESCR_TIPO_ENVA, d.COD_PARTE_PRODUCC, TRIM(p.ESPECIE) AS ESPECIE, TO_CHAR(p.FECHA_PART,'DD/MM/YYYY') AS FECHA_PART, d.FECHA_REG
FROM GES_ENVA_SOPORTE_DET d LEFT JOIN ARTICULO_CONGE a ON a.COD_ART_CONG = d.COD_ART_CONG LEFT JOIN TIPO_ENVA t ON t.COD_TIPO_ENVA = d.COD_TIPO_ENVA LEFT JOIN PARTE_PRODUCCION p ON p.COD_PARTE_PRODUCC = d.COD_PARTE_PRODUCC
WHERE TRIM(d.COD_TEL) = TRIM(:codTel) ORDER BY d.ITEM ASC";

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ":codTel", $codTel);

$ok = oci_execute($stmt);

if (!$ok) {
    $error = oci_error($stmt);
    throw new Exception($error['message']);
}


/* ARMAR RESULTADO*/

$data = [];

while ($row = oci_fetch_assoc($stmt)) {

    $row["PLAN_DESCRIPCION"] =
        ($row["ESPECIE"] ?? "") .
        " - " .
        ($row["FECHA_PART"] ?? "");

    $data[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

return $data;

}

/* SOPORTES FINALIZADOS*/

public function obtenerSoportesFinalizados(string $codTurno): array
{

$conn=conectarDB();

$sql="SELECT g.COD_GENVA_S, g.COD_ENVA_S, e.COD_PRODUCCION, e.TIPO, t.COD_LINEA, pl.DESCR AS DESC_LINEA, TO_CHAR(g.FECHA_REG,'DD-MON-YY HH24:MI') AS FECHA_REG,
MAX(a.DESCR) AS PRODUCTO, MAX(TRIM(p.ESPECIE) || ' - ' || TO_CHAR(p.FECHA_PART,'DD/MM/YYYY')) AS PLAN_DIARIO,
SUM(d.KILOS) AS TOTAL_KILOS, LISTAGG(te.DESCRIPCION, ', ') WITHIN GROUP (ORDER BY te.DESCRIPCION) AS TIPO_ENVASE 
FROM GES_ENVA_SOPORTE g JOIN ENVA_SOPORTE e ON e.COD_ENVA_S = g.COD_ENVA_S JOIN TURNO_ENVA_LINEA t ON t.COD_GENVA_S = g.COD_GENVA_S
LEFT JOIN PRODUCC_LINEAS pl ON pl.COD_LINEA = t.COD_LINEA LEFT JOIN GES_ENVA_SOPORTE_DET d ON d.COD_TEL = t.COD_TEL
LEFT JOIN ARTICULO_CONGE a ON a.COD_ART_CONG = d.COD_ART_CONG LEFT JOIN TIPO_ENVA te
ON te.COD_TIPO_ENVA = d.COD_TIPO_ENVA LEFT JOIN PARTE_PRODUCCION p
ON p.COD_PARTE_PRODUCC = d.COD_PARTE_PRODUCC WHERE g.ESTADO = 2
AND t.COD_TUR_ENV = :codTurno GROUP BY g.COD_GENVA_S, g.COD_ENVA_S, e.COD_PRODUCCION, e.TIPO, t.COD_LINEA, pl.DESCR,  g.FECHA_REG ORDER BY g.FECHA_REG DESC";

$stmt=oci_parse($conn,$sql);
oci_bind_by_name($stmt,":codTurno",$codTurno);
oci_execute($stmt);

$data=[];

while($row=oci_fetch_assoc($stmt)){
$data[]=$row;
}

oci_free_statement($stmt);
oci_close($conn);

return $data;

}

// REGRESAR SOPORTE LINEA 
public function obtenerLineaOrigen(string $codGenvaS): ?string
{
    $conn = conectarDB();

    $sql = "SELECT COD_LINEA FROM TURNO_ENVA_LINEA WHERE COD_GENVA_S = :codGenvaS";

    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ":codGenvaS", $codGenvaS);

    oci_execute($stmt);

    $row = oci_fetch_assoc($stmt);

    oci_free_statement($stmt);
    oci_close($conn);

    return $row["COD_LINEA"] ?? null;
}

public function moverSoporteLinea( string $codGenvaS, string $codLineaDestino, string $codTurno ): bool {

    $conn = conectarDB();

    /* ================= VALIDAR CAPACIDAD ================= */
    $sqlCount = "SELECT 
                    COUNT(*) TOTAL,
                    SUM(CASE WHEN g.ESTADO = 1 THEN 1 ELSE 0 END) ACTIVOS
                 FROM TURNO_ENVA_LINEA t
                 JOIN GES_ENVA_SOPORTE g 
                    ON g.COD_GENVA_S = t.COD_GENVA_S
                 WHERE t.COD_LINEA = :codLinea
                 AND t.COD_TUR_ENV = :codTurno";

    $stmtCount = oci_parse($conn, $sqlCount);

    oci_bind_by_name($stmtCount, ":codLinea", $codLineaDestino);
    oci_bind_by_name($stmtCount, ":codTurno", $codTurno);

    oci_execute($stmtCount);

    $row = oci_fetch_assoc($stmtCount);
    oci_free_statement($stmtCount);

    $total   = intval($row["TOTAL"] ?? 0);
    $activos = intval($row["ACTIVOS"] ?? 0);

    if ($total >= 4) {
        throw new Exception("Máximo 4 soportes en la línea.");
    }

    if ($activos >= 2) {
        throw new Exception("Máximo 2 activos en la línea.");
    }

    /* ================= UPDATE (CLAVE) ================= */
    $sqlUpdate = "UPDATE TURNO_ENVA_LINEA
                  SET COD_LINEA = :codLinea
                  WHERE COD_GENVA_S = :codGenvaS
                  AND COD_TUR_ENV = :codTurno";

    $stmtUpdate = oci_parse($conn, $sqlUpdate);

    oci_bind_by_name($stmtUpdate, ":codLinea", $codLineaDestino);
    oci_bind_by_name($stmtUpdate, ":codGenvaS", $codGenvaS);
    oci_bind_by_name($stmtUpdate, ":codTurno", $codTurno);

    $ok = oci_execute($stmtUpdate, OCI_NO_AUTO_COMMIT);

    if (!$ok) {
        $error = oci_error($stmtUpdate);
        oci_rollback($conn);
        throw new Exception($error["message"]);
    }

    oci_free_statement($stmtUpdate);

    oci_commit($conn);
    oci_close($conn);

    return true;
}


/* PASAR A CONGELADO POR MAQUINA */
public function pasarACongelado(
    string $codGenvaS
): bool
{
    $conn = conectarDB();

    if (!$conn) {
        throw new Exception("Error al conectar con la base de datos.");
    }
    $sql = "UPDATE GES_ENVA_SOPORTE
            SET ESTADO = 3
            WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS)
            AND ESTADO = 2";

    $stmt = oci_parse($conn, $sql);

    if (!$stmt) {
        $error = oci_error($conn);
        throw new Exception($error['message']);
    }

    oci_bind_by_name($stmt, ":codGenvaS", $codGenvaS);

    $ok = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if (!$ok) {

        $error = oci_error($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        throw new Exception($error['message']);
    }

    $rows = oci_num_rows($stmt);

    oci_free_statement($stmt);
    oci_close($conn);

    /* VALIDAR SI REALMENTE SE ACTUALIZÓ */

    if ($rows === 0) {
        throw new Exception("No se pudo actualizar el soporte. Puede que no esté en estado FINALIZADO.");
    }

    return true;
}

/* DEVOLVER A FINALIZADO */
public function devolverAFinalizado(string $codGenvaS): bool
{

    $conn = conectarDB();

    if (!$conn) {
        throw new Exception("Error al conectar con la base de datos.");
    }

    $sql = "UPDATE GES_ENVA_SOPORTE SET ESTADO = 2 WHERE TRIM(COD_GENVA_S) = TRIM(:codGenvaS) AND ESTADO = 3";

    $stmt = oci_parse($conn, $sql);

    if (!$stmt) {
        $error = oci_error($conn);
        throw new Exception($error['message']);
    }

    oci_bind_by_name($stmt, ":codGenvaS", $codGenvaS);

    $ok = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if (!$ok) {

        $error = oci_error($stmt);

        oci_free_statement($stmt);
        oci_close($conn);

        throw new Exception($error['message']);
    }

    $rows = oci_num_rows($stmt);

    oci_free_statement($stmt);
    oci_close($conn);

    /* VALIDAR SI SE ACTUALIZÓ */

    if ($rows === 0) {
        throw new Exception("No Se Pudo Actualizar Soporte.");
    }
    return true;
}

}