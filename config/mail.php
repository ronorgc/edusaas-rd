<?php
// =====================================================
// EduSaaS RD - Configuración de Correo (SMTP)
// =====================================================
// Lee siempre del .env en disco para que cambios
// guardados desde el panel surtan efecto de inmediato.
// =====================================================

// Leer el .env directamente (no depender de $_ENV que
// se carga una sola vez al inicio del proceso)
$_envPath = __DIR__ . '/../.env';
$_envVals = [];
if (file_exists($_envPath)) {
    foreach (file($_envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_linea) {
        if (str_starts_with(trim($_linea), '#') || !str_contains($_linea, '=')) continue;
        [$_k, $_v] = explode('=', $_linea, 2);
        $_envVals[trim($_k)] = trim($_v);
    }
}

// Función helper: busca primero en .env del disco, luego en $_ENV
$_e = fn(string $key, mixed $default = '') => $_envVals[$key] ?? $_ENV[$key] ?? $default;

return [
    'from_name'  => $_e('MAIL_FROM_NAME',  'EduSaaS RD'),
    'from_email' => $_e('MAIL_FROM_EMAIL', 'noreply@edusaas.do'),
    'reply_to'   => $_e('MAIL_REPLY_TO',   'soporte@edusaas.do'),

    // 'smtp' | 'mail' | 'log'
    'driver' => $_e('MAIL_DRIVER', 'smtp'),

    'smtp' => [
        'host'       => $_e('MAIL_SMTP_HOST',       'smtp.gmail.com'),
        'port'       => (int) $_e('MAIL_SMTP_PORT',  587),
        'encryption' => $_e('MAIL_SMTP_ENCRYPTION',  'tls'),
        'username'   => $_e('MAIL_SMTP_USER',        ''),
        'password'   => $_e('MAIL_SMTP_PASSWORD',    ''),
        'timeout'    => (int) $_e('MAIL_SMTP_TIMEOUT', 15),
    ],
];