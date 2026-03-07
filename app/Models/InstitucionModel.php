<?php
// =====================================================
// EduSaaS RD - InstitucionModel
// =====================================================

class InstitucionModel extends BaseModel
{
    protected string $table = 'instituciones';

    /** Lista completa con el estado de suscripción de cada una */
    public function getAllConSuscripcion(): array
    {
        $stmt = $this->db->query(
            "SELECT i.*,
                    s.estado        AS suscripcion_estado,
                    s.fecha_vencimiento,
                    p.nombre        AS plan_nombre,
                    p.color         AS plan_color,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM instituciones i
             LEFT JOIN suscripciones s ON s.institucion_id = i.id
                   AND s.estado IN ('activa','vencida','suspendida')
             LEFT JOIN planes p ON s.plan_id = p.id
             ORDER BY i.nombre ASC"
        );
        return $stmt->fetchAll();
    }

    /** Busca institución por subdominio */
    public function findBySubdomain(string $subdomain): ?array
    {
        return $this->findBy(['subdomain' => $subdomain, 'activo' => 1]);
    }

    /** Estadísticas globales para el dashboard del super admin */
    public function getEstadisticasGlobales(): array
    {
        $db = $this->db;

        $stats = [];

        $stmt = $db->query("SELECT COUNT(*) FROM instituciones WHERE activo = 1");
        $stats['total_instituciones'] = (int) $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM suscripciones WHERE estado = 'activa'");
        $stats['suscripciones_activas'] = (int) $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM suscripciones WHERE estado = 'vencida'");
        $stats['suscripciones_vencidas'] = (int) $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM suscripciones WHERE estado = 'suspendida'");
        $stats['suscripciones_suspendidas'] = (int) $stmt->fetchColumn();

        // Ingresos del mes actual
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM pagos_saas
             WHERE estado = 'confirmado'
               AND MONTH(fecha_pago) = MONTH(CURDATE())
               AND YEAR(fecha_pago)  = YEAR(CURDATE())"
        );
        $stmt->execute();
        $stats['ingresos_mes'] = (float) $stmt->fetchColumn();

        // Ingresos del año actual
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM pagos_saas
             WHERE estado = 'confirmado'
               AND YEAR(fecha_pago) = YEAR(CURDATE())"
        );
        $stmt->execute();
        $stats['ingresos_anio'] = (float) $stmt->fetchColumn();

        // Total estudiantes en todo el sistema
        $stmt = $db->query("SELECT COUNT(*) FROM estudiantes WHERE activo = 1");
        $stats['total_estudiantes'] = (int) $stmt->fetchColumn();

        return $stats;
    }
}
