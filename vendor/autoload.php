<?php
// =====================================================
// EduSaaS RD - Autoloader de Clases
// =====================================================

spl_autoload_register(function (string $className): void {
    // Mapeo de namespaces a directorios
    $directorios = [
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Repositories/',
        __DIR__ . '/../app/Services/',
        __DIR__ . '/../app/Middlewares/',   // ← SuscripcionMiddleware, VisorMiddleware
        __DIR__ . '/../app/Helpers/',             // ← PlanHelper y otros helpers
        __DIR__ . '/',  // vendor (Database, Router, etc.)
    ];

    foreach ($directorios as $dir) {
        $archivo = $dir . $className . '.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});