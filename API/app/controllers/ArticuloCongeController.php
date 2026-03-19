<?php

require_once __DIR__ . '/../models/ArticuloCongeModel.php';
require_once __DIR__ . '/../helper/CacheHelper.php';

class ArticuloCongeController
{
    public function obtenerArticulosPorPlan()
    {
        try {

            // Obtener cache de planes
            $cache = CacheHelper::obtenerPlan();

            if (!$cache || !isset($cache['planes']) || count($cache['planes']) === 0) {
                return [
                    'ok' => false,
                    'message' => 'NO HAY PLANES EN CACHE'
                ];
            }

            // Extraer especies
            $especies = array_filter(array_map(function($p){
                return trim($p['especie'] ?? '');
            }, $cache['planes']));

            if (count($especies) === 0) {
                return [
                    'ok' => false,
                    'message' => 'NO HAY ESPECIES VÁLIDAS EN EL PLAN'
                ];
            }

            //  Consultar modelo
            $model = new ArticuloCongeModel();
            $data = $model->obtenerPorEspecies($especies);

            return [
                'ok' => true,
                'data' => $data
            ];

        } catch (Exception $e) {

            return [
                'ok' => false,
                'message' => 'ERROR AL OBTENER ARTÍCULOS',
                'error' => $e->getMessage()
            ];
        }
    }
}
