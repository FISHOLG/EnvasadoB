<?php

global $router;
error_log("INDEX.PHP EJECUTADO");

/* REQUIRE CONTROLLERS */
require_once __DIR__ . '/../controllers/TestController.php';
require_once __DIR__ . '/../controllers/PlanDiarioController.php';
require_once __DIR__ . '/../controllers/LineasTrabajoController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/TurnoEnvasadoController.php';
require_once __DIR__ . '/../controllers/ArticuloCongeController.php';
require_once __DIR__ . '/../controllers/SoporteController.php';

/*================ RUTAS GET ================= */

// Ruta de prueba
$router->get('/envasado/test','TestController@ping'
);

// Listar planes diarios
$router->get('/envasado/plan-diario','PlanDiarioController@listar'
);

// Listar líneas disponibles
$router->get('/envasado/lineas-trabajo','LineasTrabajoController@listar'
);

// Obtener estado actual del turno
$router->get('/envasado/turno/estado','TurnoEnvasadoController@estado'
);

// Obtener líneas guardadas en cache
$router->get('/envasado/lineas/cache','TurnoEnvasadoController@obtenerLineas'
);

// Obtener plan guardado en cache
$router->get(
    '/envasado/plan/obtener',
    'TurnoEnvasadoController@obtenerPlan'
);

// Obtener artículos según plan en cache
$router->get(
    '/envasado/articulos-por-plan',
    'ArticuloCongeController@obtenerArticulosPorPlan'
);

// Obtener productos asignados por línea
$router->get(
    '/envasado/producto-linea',
    'TurnoEnvasadoController@obtenerProductoLinea'
);
// Listar todos los soportes
$router->get(
    '/envasado/soporte',
    'SoporteController@listar'
);



/*        ================= RUTAS POST ================= */

// Login usuario
$router->post(
    '/envasado/login',
    'AuthController@login'
);

// Ejecutar turno (abrir / cerrar)
$router->post(
    '/envasado/turno',
    'TurnoEnvasadoController@ejecutar'
);

// Limpiar plan diario en cache
$router->post(
    '/envasado/plan/limpiar',
    'TurnoEnvasadoController@limpiarPlan'
);

// Guardar líneas en cache
$router->post(
    '/envasado/lineas/cache',
    'TurnoEnvasadoController@guardarLineas'
);

// Limpiar líneas en cache
$router->post(
    '/envasado/lineas/cache/limpiar',
    'TurnoEnvasadoController@limpiarLineas'
);

// Guardar productos por línea
$router->post(
    '/envasado/producto-linea',
    'TurnoEnvasadoController@guardarProductoLinea'
);

// Limpiar productos por línea manualmente
$router->post(
    '/envasado/producto-linea/limpiar',
    'TurnoEnvasadoController@limpiarProductoLinea'
);

$router->post('/envasado/soporte/asignar', 'SoporteController@asignar');
$router->get('/envasado/soporte-linea', 'SoporteController@porLinea');
$router->post('/envasado/soporte/estado', 'SoporteController@actualizarEstado');
$router->post('/envasado/soporte/eliminar', 'SoporteController@eliminar');
