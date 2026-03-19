<?php

class CacheHelper
{
    /* 
       =========================================================
       RUTAS DE ARCHIVOS CACHE
       =========================================================
    */

    // Archivo para el plan diario
    public static string $file = __DIR__ . '/../storage/plan_envasado.json';

    // Archivo para líneas seleccionadas
    public static string $fileLineas = __DIR__ . '/../storage/lineas_envasado.json';

    // Archivo para productos por línea
    public static string $fileProductoLinea = __DIR__ . '/../storage/producto_linea.json';

    // Archivo para soportes por línea
    public static string $fileSoporteLinea = __DIR__ . '/../storage/soporte_linea.json';


    /* =========================================================
       ================= PLAN =================
       ========================================================= */

    /**
     * Guarda el plan diario en cache
     */
    public static function guardarPlan(array $planes, string $usuario): void
    {
        $data = [
            'usuario' => $usuario,
            'planes'  => $planes,
            'fecha'   => date('d-m-Y H:i:s')
        ];

        file_put_contents(
            self::$file,
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Obtiene el plan guardado
     */
    public static function obtenerPlan(): ?array
    {
        if (!file_exists(self::$file)) {
            return null;
        }

        $content = file_get_contents(self::$file);
        return json_decode($content, true);
    }

    /**
     * Elimina el plan del cache
     */
    public static function limpiarPlan(): void
    {
        if (file_exists(self::$file)) {
            unlink(self::$file);
        }
    }


    /* =========================================================
       ================= LINEAS =================
       ========================================================= */

    /**
     * Guarda las líneas activas en cache
     */
    public static function guardarLineas(array $lineas): void
    {
        $data = [
            'lineas' => $lineas,
            'fecha'  => date('d-m-Y H:i:s')
        ];

        file_put_contents(
            self::$fileLineas,
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Obtiene las líneas guardadas
     */
    public static function obtenerLineas(): ?array
    {
        if (!file_exists(self::$fileLineas)) {
            return null;
        }

        $content = file_get_contents(self::$fileLineas);
        return json_decode($content, true);
    }

    /**
     * Elimina las líneas del cache
     */
    public static function limpiarLineas(): void
    {
        if (file_exists(self::$fileLineas)) {
            unlink(self::$fileLineas);
        }
    }


    /* =========================================================
       ================= PRODUCTO POR LINEA =================
       ========================================================= */

    /**
     * Guarda o actualiza productos asociados a una línea.
     * - Si la línea ya existe → se reemplaza.
     * - Si no existe → se agrega.
     */
    public static function guardarProductoLinea(array $data): void
    {
        $current = [
            'fecha' => date('d-m-Y H:i:s'),
            'producto_linea' => []
        ];

        if (file_exists(self::$fileProductoLinea)) {
            $content = file_get_contents(self::$fileProductoLinea);
            $decoded = json_decode($content, true);

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
            self::$fileProductoLinea,
            json_encode($current, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Obtiene el JSON completo de productos por línea
     */
    public static function obtenerProductoLinea(): ?array
    {
        if (!file_exists(self::$fileProductoLinea)) {
            return null;
        }

        $content = file_get_contents(self::$fileProductoLinea);
        return json_decode($content, true);
    }

    /**
     * Limpia el cache de productos por línea
     */
    public static function limpiarProductoLinea(): void
    {
        if (file_exists(self::$fileProductoLinea)) {
            unlink(self::$fileProductoLinea);
        }
    }


    /* 
       ================= SOPORTE POR LINEA =================
     */

    /**
     * Guarda o actualiza soportes asociados a una línea.
     * - Si la línea ya existe → se reemplaza.
     * - Si no existe → se agrega.
     */
    public static function guardarSoporteLinea(array $data): void
    {
        $current = [
            'fecha' => date('d-m-Y H:i:s'),
            'soporte_linea' => []
        ];

        if (file_exists(self::$fileSoporteLinea)) {
            $content = file_get_contents(self::$fileSoporteLinea);
            $decoded = json_decode($content, true);

            if ($decoded) {
                $current = $decoded;
            }
        }

        $index = array_search(
            $data['cod_linea'],
            array_column($current['soporte_linea'], 'cod_linea')
        );

        if ($index !== false) {
            $current['soporte_linea'][$index] = $data;
        } else {
            $current['soporte_linea'][] = $data;
        }

        $current['fecha'] = date('d-m-Y H:i:s');

        file_put_contents(
            self::$fileSoporteLinea,
            json_encode($current, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Obtiene el JSON completo de soportes por línea
     */
    public static function obtenerSoporteLinea(): ?array
    {
        if (!file_exists(self::$fileSoporteLinea)) {
            return null;
        }

        $content = file_get_contents(self::$fileSoporteLinea);
        return json_decode($content, true);
    }

    /**
     * Limpia el cache de soportes por línea
     */
    public static function limpiarSoporteLinea(): void
    {
        if (file_exists(self::$fileSoporteLinea)) {
            unlink(self::$fileSoporteLinea);
        }
    }
}
