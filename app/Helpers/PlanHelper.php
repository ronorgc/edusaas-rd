<?php
// =====================================================
// EduSaaS RD - PlanHelper
// Punto central para verificar límites y módulos del plan.
//
// CÓMO FUNCIONA:
// SuscripcionMiddleware carga el plan completo en
// $_SESSION['plan'] al inicio de cada request.
// PlanHelper lee esa sesión y responde preguntas.
//
// USO EN CONTROLADORES:
//   PlanHelper::puedeCrearEstudiante($instId)
//   PlanHelper::tieneModulo('pagos')
//   PlanHelper::getLimite('estudiantes')
// =====================================================

class PlanHelper
{
    // ── Verificar límites de cantidad ────────────────

    /**
     * ¿Puede el colegio crear un estudiante más?
     * Retorna true si no hay límite o si no llegó al máximo.
     */
    public static function puedeCrearEstudiante(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_estudiantes'] ?? 0);
        if ($max === 0) return true; // 0 = ilimitado

        $db  = Database::getInstance();
        $n   = (int)$db->prepare(
            "SELECT COUNT(*) FROM estudiantes WHERE institucion_id = :id AND activo = 1"
        )->execute([':id' => $instId]) ? $db->query(
            "SELECT COUNT(*) FROM estudiantes WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn() : 0;

        return $n < $max;
    }

    /**
     * ¿Puede el colegio crear un profesor más?
     */
    public static function puedeCrearProfesor(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_profesores'] ?? 0);
        if ($max === 0) return true;

        $db = Database::getInstance();
        $n  = (int)$db->query(
            "SELECT COUNT(*) FROM profesores WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn();

        return $n < $max;
    }

    /**
     * ¿Puede el colegio crear una sección más?
     */
    public static function puedeCrearSeccion(int $instId): bool
    {
        $max = (int)($_SESSION['plan']['max_secciones'] ?? 0);
        if ($max === 0) return true;

        $db = Database::getInstance();
        $n  = (int)$db->query(
            "SELECT COUNT(*) FROM secciones WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn();

        return $n < $max;
    }

    // ── Verificar acceso a módulos ───────────────────

    /**
     * ¿El plan incluye este módulo?
     * $modulo: 'pagos' | 'reportes' | 'comunicados' | 'api'
     */
    public static function tieneModulo(string $modulo): bool
    {
        // Super admin siempre tiene acceso total
        if (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN) return true;

        $key = 'incluye_' . $modulo;
        return (bool)($_SESSION['plan'][$key] ?? false);
    }

    // ── Info del plan actual ─────────────────────────

    /**
     * Devuelve el valor de un límite del plan activo.
     * $campo: 'estudiantes' | 'profesores' | 'secciones'
     * Retorna 0 si es ilimitado.
     */
    public static function getLimite(string $campo): int
    {
        return (int)($_SESSION['plan']['max_' . $campo] ?? 0);
    }

    /**
     * Devuelve el nombre del plan actual.
     */
    public static function getNombrePlan(): string
    {
        return $_SESSION['plan']['nombre'] ?? 'Sin plan';
    }

    /**
     * Devuelve cuántos estudiantes activos tiene el colegio.
     */
    public static function contarEstudiantes(int $instId): int
    {
        $db = Database::getInstance();
        return (int)$db->query(
            "SELECT COUNT(*) FROM estudiantes WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn();
    }

    public static function contarProfesores(int $instId): int
    {
        $db = Database::getInstance();
        return (int)$db->query(
            "SELECT COUNT(*) FROM profesores WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn();
    }

    public static function contarSecciones(int $instId): int
    {
        $db = Database::getInstance();
        return (int)$db->query(
            "SELECT COUNT(*) FROM secciones WHERE institucion_id = {$instId} AND activo = 1"
        )->fetchColumn();
    }

    /**
     * Resumen del plan para mostrar en vistas.
     * Retorna array con uso actual vs límite por categoría.
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
}