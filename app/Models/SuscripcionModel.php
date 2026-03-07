<?php
// =====================================================
// EduSaaS RD - SuscripcionModel
// =====================================================

class SuscripcionModel extends BaseModel
{
    protected string $table = 'suscripciones';

    /** Suscripción activa de una institución (con datos del plan) */
    public function getActivaPorInstitucion(int $institucionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, p.nombre AS plan_nombre, p.precio_mensual,
                    p.max_estudiantes, p.max_profesores, p.max_secciones,
                    p.incluye_pagos, p.incluye_reportes, p.color AS plan_color
             FROM suscripciones s
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.institucion_id = :id AND s.estado = 'activa'
             ORDER BY s.fecha_vencimiento DESC
             LIMIT 1"
        );
        $stmt->execute([':id' => $institucionId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /** Historial completo de suscripciones de una institución */
    public function getHistorialPorInstitucion(int $institucionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, p.nombre AS plan_nombre
             FROM suscripciones s
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.institucion_id = :id
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([':id' => $institucionId]);
        return $stmt->fetchAll();
    }

    /** Instituciones que vencen en los próximos N días */
    public function getPorVencer(int $dias = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, i.nombre AS institucion_nombre, i.email AS institucion_email,
                    p.nombre AS plan_nombre,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM suscripciones s
             INNER JOIN instituciones i ON s.institucion_id = i.id
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.estado = 'activa'
               AND s.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
             ORDER BY s.fecha_vencimiento ASC"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll();
    }

    /** Todas las suscripciones con info de institución y plan (para tabla del super admin) */
    public function getAllConDetalle(string $estado = ''): array
    {
        $sql = "SELECT s.*, 
                       i.nombre AS institucion_nombre, i.tipo AS institucion_tipo,
                       i.email AS institucion_email, i.subdomain,
                       p.nombre AS plan_nombre, p.color AS plan_color,
                       DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
                FROM suscripciones s
                INNER JOIN instituciones i ON s.institucion_id = i.id
                INNER JOIN planes p ON s.plan_id = p.id";

        if ($estado) {
            $sql .= " WHERE s.estado = :estado";
        }
        $sql .= " ORDER BY s.fecha_vencimiento ASC";

        $stmt = $this->db->prepare($sql);
        if ($estado) {
            $stmt->execute([':estado' => $estado]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    /** Marca automáticamente como vencidas las suscripciones expiradas */
    public function marcarVencidas(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE suscripciones 
             SET estado = 'vencida'
             WHERE estado = 'activa' AND fecha_vencimiento < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
