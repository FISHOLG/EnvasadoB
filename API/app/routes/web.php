<?php

global $router;
// error_log("INDEX.PHP EJECUTADO");

/* REQUIRE CONTROLLERS */
require_once __DIR__ . '/../controllers/TestController.php';
require_once __DIR__ . '/../controllers/PlanDiarioController.php';
require_once __DIR__ . '/../controllers/LineasTrabajoController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/TurnoEnvasadoController.php';
require_once __DIR__ . '/../controllers/ArticuloCongeController.php';
require_once __DIR__ . '/../controllers/SoporteController.php';
require_once __DIR__ . '/../controllers/MaquinaCongeController.php';


/*================ RUTAS GET ================= */

// Ruta de prueba
$router->get('/envasado/test','TestController@ping');
// Listar planes diarios
$router->get('/envasado/plan-diario','PlanDiarioController@listar');
// Listar líneas disponibles
$router->get('/envasado/lineas-trabajo','LineasTrabajoController@listar');
// Obtener estado actual del turno
$router->get('/envasado/turno/estado','TurnoEnvasadoController@estado');
// Obtener líneas guardadas en cache
$router->get('/envasado/lineas/cache','TurnoEnvasadoController@obtenerLineas');
// Obtener plan guardado en cache
// $router->get('/envasado/plan/obtener','TurnoEnvasadoController@obtenerPlan'
// );
$router->get('/envasado/plan-cache','TurnoEnvasadoController@planCache');
// Obtener artículos según plan en cache
$router->get('/envasado/articulos-por-plan','ArticuloCongeController@obtenerArticulosPorPlan');
// Obtener productos asignados por línea
$router->get('/envasado/producto-linea','TurnoEnvasadoController@obtenerProductoLinea');
// Listar todos los soportes
$router->get('/envasado/soporte','SoporteController@listar');
// Login usuario
$router->post('/envasado/login','AuthController@login');
// Ejecutar turno (abrir / cerrar)
$router->post('/envasado/turno','TurnoEnvasadoController@ejecutar');
// Limpiar plan diario en cache
$router->post('/envasado/plan/limpiar','TurnoEnvasadoController@limpiarPlan');
// Guardar líneas en cache
$router->post('/envasado/lineas/cache','TurnoEnvasadoController@guardarLineas');
// Limpiar líneas en cache
$router->post('/envasado/lineas/cache/limpiar','TurnoEnvasadoController@limpiarLineas');
$router->get('/envasado/lineas/cache/limpiar','TurnoEnvasadoController@limpiarLineas');
// Guardar productos por línea
$router->post('/envasado/producto-linea','TurnoEnvasadoController@guardarProductoLinea');
// Limpiar productos por línea manualmente
$router->post('/envasado/producto-linea/limpiar','TurnoEnvasadoController@limpiarProductoLinea');
// Asignar soporte
$router->post('/envasado/soporte/asignar', 'SoporteController@asignar');
// Obtener soportes por línea   
$router->get('/envasado/soporte-linea', 'SoporteController@porLinea');
// Actualizar estado de soporte
$router->post('/envasado/soporte/estado', 'SoporteController@actualizarEstado');
// Eliminar soporte
$router->post('/envasado/soporte/eliminar', 'SoporteController@eliminarSoporte');

$router->get('/envasado/turno-global', 'TurnoEnvasadoController@estadoGlobal');
// Insertar detalle de soporte
$router->post('/envasado/soporte/detalle', 'SoporteController@insertarDetalle');
// Obtener detalle de soporte   
$router->get('/envasado/soporte/detallep', 'SoporteController@obtenerDetalleP');
// Finalizar soporte
$router->post('/envasado/soporte/finalizar', 'SoporteController@finalizar');
// Traer ultimo turno
$router->get('/envasado/turno-ultimo', 'TurnoEnvasadoController@ultimoTurno');
// Agregar plan a cache
$router->post('/envasado/plan-cache', 'TurnoEnvasadoController@agregarPlanCache');
// Insertar líneas en turno
$router->post('/envasado/turno/lineas', 'TurnoEnvasadoController@insertarLineasTurno');
// Listar tipos de envase
$router->get('/envasado/tipo-envase','SoporteController@obtenerTiposEnvase');
// Regresar soporte a línea
$router->post('/envasado/soporte/regresar', 'SoporteController@regresarSoporteLinea');
// ======================= CONGELADO =======================

// Listar máquinas de congelado
$router->get('/congelado/maquinas', 'MaquinaCongeController@listarMaquinas');
// Soportes finalizados
$router->get('/congelado/soportes-finalizados', 'SoporteController@obtenerSoportesFinalizados');
// Pasar soporte a congelado
$router->post('/congelado/soporte/pasar', 'SoporteController@pasarACongelado');
// Devolver soporte a finalizado
$router->post('/congelado/soporte/devolver', 'SoporteController@devolverAFinalizado');
// devolver congelado (ESTADO = 3)
$router->get('/congelado/soportes-congelador', 'SoporteController@obtenerSoportesCongelador');
// Leer cache de máquinas
$router->get('/congelado/maquina-soportes', 'SoporteController@obtenerSoportesMaquina');



