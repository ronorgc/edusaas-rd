<?php
// =====================================================
// EduSaaS RD - Punto de Entrada Único (Front Controller)
// =====================================================
// TODA petición HTTP pasa por aquí gracias al .htaccess

// 1. Cargar variables de entorno desde .env
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        if (str_starts_with(trim($linea), '#')) continue; // ignorar comentarios
        [$clave, $valor] = explode('=', $linea, 2);
        $_ENV[trim($clave)] = trim($valor);
    }
}

// 2. Configuración de errores según entorno
$appConfig = require __DIR__ . '/../config/app.php';
if ($appConfig['debug']) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 3. Zona horaria
date_default_timezone_set($appConfig['timezone']);

// 4. Iniciar sesión
session_name($appConfig['session']['name']);
session_start();

// 5. Cargar autoloader y clases del vendor
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/Database.php';
require_once __DIR__ . '/../vendor/Router.php';

// 6. Cargar constantes del sistema
require_once __DIR__ . '/../config/constants.php';

// 7. Crear el router y cargar las rutas
$router = new Router();
require_once __DIR__ . '/../routes/web.php';

// 8. ¡Despachar la petición!
$router->dispatch();
