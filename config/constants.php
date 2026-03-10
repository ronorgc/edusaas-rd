<?php
// =====================================================
// EduSaaS RD - Constantes del Sistema
// =====================================================

// --- Roles ---
define('ROL_SUPER_ADMIN', 1);
define('ROL_ADMIN',       2);
define('ROL_PROFESOR',    3);
define('ROL_PADRE',       4);
define('ROL_ESTUDIANTE',  5);

// --- Estados de Matrícula ---
define('MATRICULA_ACTIVA',      'activa');
define('MATRICULA_RETIRADA',    'retirada');
define('MATRICULA_GRADUADO',    'graduado');
define('MATRICULA_TRASLADADO',  'trasladado');

// --- Sistema de Calificaciones MINERD ---
define('CALIFICACION_MINIMA',   0);
define('CALIFICACION_MAXIMA',   100);
define('CALIFICACION_APROBADO', 70);   // Mínimo para aprobar

// --- Períodos Académicos ---
define('PERIODOS', [
    1 => '1er Período',
    2 => '2do Período',
    3 => '3er Período',
    4 => '4to Período',
    5 => 'Evaluación Final',
]);

// --- Niveles Educativos ---
define('NIVEL_INICIAL',     1);
define('NIVEL_PRIMARIA',    2);
define('NIVEL_SECUNDARIA',  3);

// --- Estados de Asistencia ---
define('ASISTENCIA_PRESENTE',     'presente');
define('ASISTENCIA_AUSENTE',      'ausente');
define('ASISTENCIA_TARDANZA',     'tardanza');
define('ASISTENCIA_JUSTIFICADA',  'justificada');

// --- Estados de Cuotas ---
define('CUOTA_PENDIENTE',   'pendiente');
define('CUOTA_PAGADA',      'pagada');
define('CUOTA_VENCIDA',     'vencida');
define('CUOTA_CANCELADA',   'cancelada');

// --- Métodos de Pago ---
define('PAGO_EFECTIVO',       'efectivo');
define('PAGO_TRANSFERENCIA',  'transferencia');
define('PAGO_CHEQUE',         'cheque');
define('PAGO_TARJETA',        'tarjeta');

// --- Tipos de Institución ---
define('INST_PUBLICA',  'publico');
define('INST_PRIVADA',  'privado');

// --- Paginación ---
define('REGISTROS_POR_PAGINA', 20);

// --- Planes SaaS ---
define('PLAN_BASICO',       1);
define('PLAN_PROFESIONAL',  2);
define('PLAN_PREMIUM',      3);

// --- Estados de Suscripción ---
define('SUSCRIPCION_ACTIVA',      'activa');
define('SUSCRIPCION_VENCIDA',     'vencida');
define('SUSCRIPCION_SUSPENDIDA',  'suspendida');
define('SUSCRIPCION_CANCELADA',   'cancelada');

// --- Tipos de Facturación ---
define('FACTURACION_MENSUAL', 'mensual');
define('FACTURACION_ANUAL',   'anual');

// --- Días de aviso antes del vencimiento ---
define('DIAS_AVISO_VENCIMIENTO', 7);

// --- Mensajes Flash ---
define('FLASH_SUCCESS', 'success');
define('FLASH_ERROR',   'error');
define('FLASH_WARNING', 'warning');
define('FLASH_INFO',    'info');

// --- Cron Job (recordatorio de vencimiento) ---
// Token secreto para llamadas automáticas al endpoint de cron.
// Cámbialo por cualquier cadena larga y aleatoria.
// Ejemplo de uso en cPanel/Linux:
//   0 8 * * * curl -s "https://tudominio.com/superadmin/cron/avisos-vencimiento?token=CRON_SECRET_AQUI"
define('CRON_SECRET', 'cron_edusaas_2024_cambia_esto');

// --- URL Base de la Aplicación (ADR-016) ---
// Centraliza la URL del sistema. Todas las vistas usan APP_URL directamente.
// Elimina el patrón (require config/app.php)['url'] en cada vista.
if (!defined('APP_URL')) {
    $_appCfg = require __DIR__ . '/app.php';
    define('APP_URL', rtrim($_appCfg['url'] ?? '', '/'));
    unset($_appCfg);
}