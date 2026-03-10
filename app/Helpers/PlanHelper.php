<?php
// =====================================================
// PlanHelper.php
// =====================================================
// Punto central para verificar límites y módulos del plan
// activo de una institución.
//
// CÓMO FUNCIONA:
//   SuscripcionMiddleware carga el plan completo en
//   $_SESSION['plan'] al inicio de cada request autenticado.
//   PlanHelper lee esa sesión y consulta la BD cuando necesita
//   el conteo actual de registros.
//
// USO EN CONTROLADORES:
//   PlanHelper::puedeCrearEstudiante($instId)  → bool
//   PlanHelper::puedeCrearProfesor($instId)    → bool
//   PlanHelper::puedeCrearSeccion($instId)     → bool
//   PlanHelper::tieneModulo('pagos')           → bool
//   PlanHelper::getLimite('estudiantes')       → int (0 = ilimitado)
//   PlanHelper::getResumenUso($instId)         → array
//
// Bugs corregidos:
//   B-PH-1 ✅ — puedeCrearEstudiante() usaba prepare()->execute()
//               como condición de un ternario (retorna bool, no conteo)
//               y luego ejecutaba una segunda query sin prepared statement.
//               Ahora usa un único prepared statement en todos los métodos.
//               También corregidos puedeCrearProfesor(), puedeCrearSeccion()
//               y todos los contar*() que usaban query() con interpolación.
// =====================================================

class PlanHelper
{
    // ══════════════════════════════════════════════════
    // VERIFICACIÓN DE LÍMITES
    // ══════════════════════════════════════════════════

    /**
     * ¿Puede el colegio registrar un estudiante más?
     * Retorna true si el plan es ilimitado (max = 0)
     * o si el conteo actual no alcanzó el máximo.
     *
     * @param int $instId  ID de la institución
     * @return bool
     */
    public static function puedeCrearEstudiante(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_estudiantes'] ?? 0);
        if ($max === 0) return true; // 0 = ilimitado en este plan

        // ← B-PH-1 corregido: un único prepared statement, sin interpolación
        return self::contarRegistros('estudiantes', $instId) < $max;
    }

    /**
     * ¿Puede el colegio registrar un profesor más?
     *
     * @param int $instId  ID de la institución
     * @return bool
     */
    public static function puedeCrearProfesor(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_profesores'] ?? 0);
        if ($max === 0) return true;

        // ← B-PH-1 corregido: antes usaba query() con interpolación
        return self::contarRegistros('profesores', $instId) < $max;
    }

    /**
     * ¿Puede el colegio crear una sección más?
     *
     * @param int $instId  ID de la institución
     * @return bool
     */
    public static function puedeCrearSeccion(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_secciones'] ?? 0);
        if ($max === 0) return true;

        // ← B-PH-1 corregido: antes usaba query() con interpolación
        return self::contarRegistros('secciones', $instId) < $max;
    }

    // ══════════════════════════════════════════════════
    // VERIFICACIÓN DE MÓDULOS
    // ══════════════════════════════════════════════════

    /**
     * ¿El plan activo incluye este módulo?
     * El superadmin siempre tiene acceso total independientemente del plan.
     *
     * @param string $modulo  'pagos' | 'reportes' | 'comunicados' | 'api'
     * @return bool
     */
    public static function tieneModulo(string $modulo): bool
    {
        if (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN) return true;

        $key = 'incluye_' . $modulo;
        return (bool)($_SESSION['plan'][$key] ?? false);
    }

    // ══════════════════════════════════════════════════
    // INFO DEL PLAN
    // ══════════════════════════════════════════════════

    /**
     * Devuelve el límite máximo de un recurso según el plan activo.
     *
     * @param string $campo  'estudiantes' | 'profesores' | 'secciones'
     * @return int           0 si el plan es ilimitado para ese recurso
     */
    public static function getLimite(string $campo): int
    {
        return (int)($_SESSION['plan']['max_' . $campo] ?? 0);
    }

    /**
     * Nombre del plan activo de la sesión.
     *
     * @return string
     */
    public static function getNombrePlan(): string
    {
        return $_SESSION['plan']['nombre'] ?? 'Sin plan';
    }

    // ══════════════════════════════════════════════════
    // CONTEOS (acceso directo a BD con prepared statements)
    // ══════════════════════════════════════════════════

    /**
     * Cuenta los estudiantes activos de la institución.
     *
     * @param int $instId
     * @return int
     */
    public static function contarEstudiantes(int $instId): int
    {
        return self::contarRegistros('estudiantes', $instId);
    }

    /**
     * Cuenta los profesores activos de la institución.
     *
     * @param int $instId
     * @return int
     */
    public static function contarProfesores(int $instId): int
    {
        return self::contarRegistros('profesores', $instId);
    }

    /**
     * Cuenta las secciones activas de la institución.
     *
     * @param int $instId
     * @return int
     */
    public static function contarSecciones(int $instId): int
    {
        return self::contarRegistros('secciones', $instId);
    }

    /**
     * Devuelve el resumen de uso actual vs límite del plan.
     * Usado en vistas del dashboard para mostrar el estado del plan.
     *
     * @param  int   $instId
     * @return array  Estructura: ['estudiantes' => ['actual', 'max', 'label', 'icono'], ...]
     */
    public static function getResumenUso(int $instId): array
    {
        return [
            'estudiantes' => [
                'actual' => self::contarEstudiantes($instId),
                'max'    => self::getLimite('estudiantes'),
                'label'  => 'Estudiantes',
                'icono'  => 'bi-people-fill',
            ],
            'profesores' => [
                'actual' => self::contarProfesores($instId),
                'max'    => self::getLimite('profesores'),
                'label'  => 'Profesores',
                'icono'  => 'bi-person-workspace',
            ],
            'secciones' => [
                'actual' => self::contarSecciones($instId),
                'max'    => self::getLimite('secciones'),
                'label'  => 'Secciones',
                'icono'  => 'bi-grid-fill',
            ],
        ];
    }

    // ══════════════════════════════════════════════════
    // HELPER PRIVADO
    // ══════════════════════════════════════════════════

    /**
     * Ejecuta un COUNT(*) con prepared statement sobre cualquier tabla
     * que tenga columnas institucion_id y activo.
     *
     * Centraliza la consulta para evitar repetir el patrón prepare/execute
     * en cada método público y garantizar que nunca se use interpolación.
     *
     * @param  string $tabla   Nombre de la tabla ('estudiantes', 'profesores', 'secciones')
     * @param  int    $instId  ID de la institución
     * @return int             Cantidad de registros activos
     */
    private static function contarRegistros(string $tabla, int $instId): int
    {
        // Las tablas son constantes definidas en el código, no input del usuario,
        // pero validamos de todas formas para proteger contra usos futuros incorrectos.
        $tablasPermitidas = ['estudiantes', 'profesores', 'secciones'];
        if (!in_array($tabla, $tablasPermitidas, true)) {
            return 0;
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE institucion_id = :id AND activo = 1"
        );
        $stmt->execute([':id' => $instId]);

        return (int)$stmt->fetchColumn();
    }
}