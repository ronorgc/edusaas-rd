<?php
// =====================================================
// EduSaaS RD - Configuración General de la Aplicación
// =====================================================

return [
    'name'     => $_ENV['APP_NAME']  ?? 'EduSaaS RD',
    'url'      => $_ENV['APP_URL']   ?? 'http://localhost/edusaas-rd/public',
    'env'      => $_ENV['APP_ENV']   ?? 'development',  // development | production
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'America/Santo_Domingo',
    'locale'   => 'es_DO',
    'version'  => '1.0.0',

    // Configuración de sesiones
    'session' => [
        'name'     => 'edusaas_session',
        'lifetime' => 7200, // 2 horas en segundos
    ],

    // Configuración de uploads
    'upload' => [
        'path'      => __DIR__ . '/../public/uploads/',
        'max_size'  => 5 * 1024 * 1024, // 5MB
        'allowed'   => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    ],
];