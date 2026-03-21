<?php

class CacheHelper
{
    private static string $storagePath = __DIR__ . '/../storage/';
    private static function ensureStorage(): void
    {
        if (!is_dir(self::$storagePath)) {
            mkdir(self::$storagePath, 0777, true);
        }
    }

    private static function buildPath(string $filename): string
    {
        self::ensureStorage();
        return self::$storagePath . $filename;
    }

    private static string $filePlan = 'plan_envasado.json';

    public static function guardarPlan(array $planes, string $usuario): void
    {
        $data = [
            'usuario' => $usuario,
            'planes'  => $planes,
            'fecha'   => date('d-m-Y H:i:s')
        ];

        file_put_contents(
            self::buildPath(self::$filePlan),
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    public static function obtenerPlan(): ?array
    {
        $file = self::buildPath(self::$filePlan);

        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    public static function limpiarPlan(): void
    {
        $file = self::buildPath(self::$filePlan);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /* ======================================================
       LINEAS POR USUARIO
    ====================================================== */

    private static function fileLineas(string $usuario): string
    {
        return self::buildPath("lineas_envasado_{$usuario}.json");
    }

public static function guardarLineas(array $lineas, string $usuario): array
{
    self::ensureStorage();

    $lineasFinal = [];

    foreach ($lineas as $linea) {

        $codLinea = $linea['cod_linea'] ?? null;
        $descr    = $linea['descr'] ?? $codLinea;

        if (!$codLinea) continue;

        /* VALIDAR SI LA LINEA YA ESTA OCUPADA */

        if (self::lineaEstaOcupadaEnCache($codLinea, $usuario)) {
            return [
                'ok' => false,
                'cod_linea' => $codLinea,
                'descr' => $descr
            ];
        }

        /* FORZAR USUARIO EN LA LINEA */

        $lineasFinal[] = [
            'cod_linea' => $codLinea,
            'descr'     => $descr,
            'usuario'   => $usuario
        ];
    }

    $data = [
        'usuario' => $usuario,
        'lineas'  => $lineasFinal,
        'fecha'   => date('d-m-Y H:i:s')
    ];

    file_put_contents(
        self::fileLineas($usuario),
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );

    return ['ok' => true];
}

public static function obtenerLineasConUsuario(): array
{
    self::ensureStorage();

    $mapa = [];
    $files = glob(self::$storagePath . "lineas_envasado_*.json");

    foreach ($files as $file) {

        preg_match('/lineas_envasado_(.*)\.json$/', $file, $matches);
        $usuarioArchivo = $matches[1] ?? null;

        $contenido = json_decode(file_get_contents($file), true);

        if (!isset($contenido['lineas'])) continue;

        foreach ($contenido['lineas'] as $linea) {
            $mapa[$linea['cod_linea']] = $usuarioArchivo;
        }
    }

    return $mapa;
}

    /* 
   VALIDAR LINEA OCUPADA EN CACHE
 */

    public static function lineaEstaOcupadaEnCache(string $codLinea, string $usuarioActual): bool
    {
        self::ensureStorage();

        $files = glob(self::$storagePath . "lineas_envasado_*.json");

        foreach ($files as $file) {

            // Extraer usuario del nombre del archivo
            preg_match('/lineas_envasado_(.*)\.json$/', $file, $matches);
            $usuarioArchivo = $matches[1] ?? null;

            // Ignorar archivo del usuario actual
            if ($usuarioArchivo === $usuarioActual) {
                continue;
            }

            $contenido = json_decode(file_get_contents($file), true);

            if (!isset($contenido['lineas']) || !is_array($contenido['lineas'])) {
                continue;
            }

            foreach ($contenido['lineas'] as $linea) {
                if (($linea['cod_linea'] ?? null) === $codLinea) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function obtenerLineasOcupadas(string $usuarioActual): array
    {
        self::ensureStorage();

        $ocupadas = [];
        $files = glob(self::$storagePath . "lineas_envasado_*.json");

        foreach ($files as $file) {

            preg_match('/lineas_envasado_(.*)\.json$/', $file, $matches);
            $usuarioArchivo = $matches[1] ?? null;

            if ($usuarioArchivo === $usuarioActual) continue;

            $contenido = json_decode(file_get_contents($file), true);

            if (!isset($contenido['lineas'])) continue;

            foreach ($contenido['lineas'] as $linea) {
                $ocupadas[] = $linea['cod_linea'];
            }
        }

        return array_unique($ocupadas);
    }



    public static function obtenerLineas(string $usuario): ?array
    {
        $file = self::fileLineas($usuario);

        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    // public static function limpiarLineas(string $usuario): void
    // {
    //     $file = self::fileLineas($usuario);

    //     if (file_exists($file)) {
    //         unlink($file);
    //     }
    // }
public static function limpiarLineas(?string $usuario = null): void
{
    self::ensureStorage();

    // LIMPIEZA GLOBAL
    if ($usuario === null) {
        $files = glob(self::$storagePath . "lineas_envasado_*.json");

        foreach ($files as $file) {
            unlink($file);
        }
        return;
    }

    // LIMPIEZA INDIVIDUAL
    $file = self::fileLineas($usuario);

    if (file_exists($file)) {
        unlink($file);
    }
}

    /* ======================================================
       PRODUCTO POR LINEA (POR USUARIO)
    ====================================================== */

    private static function fileProductoLinea(string $usuario): string
    {
        return self::buildPath("producto_linea_{$usuario}.json");
    }

    public static function guardarProductoLinea(array $data, string $usuario): void
    {
        $file = self::fileProductoLinea($usuario);

        $current = [
            'usuario' => $usuario,
            'fecha' => date('d-m-Y H:i:s'),
            'producto_linea' => []
        ];

        if (file_exists($file)) {
            $decoded = json_decode(file_get_contents($file), true);
            if ($decoded) {
                $current = $decoded;
            }
        }

        $index = array_search(
            $data['cod_linea'],
            array_column($current['producto_linea'], 'cod_linea')
        );

        if ($index !== false) {
            $current['producto_linea'][$index] = $data;
        } else {
            $current['producto_linea'][] = $data;
        }

        $current['fecha'] = date('d-m-Y H:i:s');

        file_put_contents(
            $file,
            json_encode($current, JSON_PRETTY_PRINT)
        );
    }

    public static function obtenerProductoLinea(string $usuario): ?array
    {
        $file = self::fileProductoLinea($usuario);

        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }


    public static function limpiarProductoLinea(?string $usuario = null): void
{
    self::ensureStorage();

    // LIMPIEZA GLOBAL
    if ($usuario === null) {
        $files = glob(self::$storagePath . "producto_linea_*.json");

        foreach ($files as $file) {
            unlink($file);
        }
        return;
    }

    // LIMPIEZA INDIVIDUAL
    $file = self::fileProductoLinea($usuario);

    if (file_exists($file)) {
        unlink($file);
    }
}

    /* 
   TURNO GLOBAL
 */

private static string $fileTurnoGlobal = 'turno_global.json';

public static function setTurnoGlobal(bool $activo): void
{
    file_put_contents(
        self::buildPath(self::$fileTurnoGlobal),
        json_encode([
            "turno_activo" => $activo,
            "fecha" => date('d-m-Y H:i:s')
        ], JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

public static function turnoGlobalActivo(): bool
{
    $file = self::buildPath(self::$fileTurnoGlobal);

    if (!file_exists($file)) return false;

    $data = json_decode(file_get_contents($file), true);

    return $data['turno_activo'] ?? false;
}

// =============================000 CONGELADO  ==================================
/* ======================================================
   CACHE MAQUINAS CONGELADO
====================================================== */

private static string $fileMaquinasCongelado = 'det_maquinas_congelado.json';

public static function guardarSoporteMaquina(string $codMaq, string $codGenvaS, string $tipo, string $codProduccion): void
{

    $file = self::buildPath(self::$fileMaquinasCongelado);

    $data = [];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
    }

    if (!isset($data[$codMaq])) {
        $data[$codMaq] = [];
    }

    $data[$codMaq][] = [
        "COD_GENVA_S" => $codGenvaS,
        "TIPO" => $tipo,
        "COD_PRODUCCION" => $codProduccion
    ];

    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );
}


public static function obtenerSoportesMaquina(string $codMaq = '*'): array
{
    $file = self::buildPath(self::$fileMaquinasCongelado);

    if (!file_exists($file)) {
        return [];
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return [];
    }

    // Si se pide "*" devolvemos TODO el cache
    if ($codMaq === '*') {
        return $data;
    }

    // Si se pide una máquina específica
    return $data[$codMaq] ?? [];
}


public static function eliminarSoporteMaquina(string $codGenvaS): void
{
    $file = self::buildPath(self::$fileMaquinasCongelado);

    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true) ?? [];

    foreach ($data as $maq => $soportes) {

        $data[$maq] = array_values(
            array_filter(
                $soportes,
                fn($s) => ($s['COD_GENVA_S'] ?? null) !== $codGenvaS
            )
        );

        // eliminar máquina si queda vacía
        if (empty($data[$maq])) {
            unset($data[$maq]);
        }
    }

    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );
}
}