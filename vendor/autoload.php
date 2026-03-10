<?php
// =====================================================
// EduSaaS RD - Autoloader de Clases
// Registra automáticamente las clases del proyecto
// sin necesidad de require/include manuales.
//
// Busca ClassName.php en cada directorio listado.
// ADR-009: app/Repositories/ eliminado — nunca se
// implementó. La capa Controller→Model→BD es suficiente.
// =====================================================

spl_autoload_register(function (string $className): void {
    // Directorios donde buscar la clase
    $directorios = [
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Services/',
        __DIR__ . '/../app/Middlewares/',   // SuscripcionMiddleware, VisorMiddleware
        __DIR__ . '/../app/Helpers/',       // ActivityLog, PlanHelper
        __DIR__ . '/',                      // vendor/ (Database, Router, SmtpClient)
    ];

    foreach ($directorios as $dir) {
        $archivo = $dir . $className . '.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});