<?php
// =====================================================
// EduSaaS RD - PagoSaasModel
// Pagos que TÚ recibes de los colegios
// =====================================================

class PagoSaasModel extends BaseModel
{
    protected string $table = 'pagos_saas';

    /** Historial de pagos de una institución con detalle */
    public function getByInstitucion(int $institucionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ps.*, u.nombres AS registrado_nombre, u.apellidos AS registrado_apellidos
             FROM pagos_saas ps
             LEFT JOIN usuarios u ON ps.registrado_por = u.id
             WHERE ps.institucion_id = :id
             ORDER BY ps.fecha_pago DESC"
        );
        $stmt->execute([':id' => $institucionId]);
        return $stmt->fetchAll();
    }

    /** Todos los pagos con nombre de institución (para reporte global) */
    public function getAllConDetalle(int $anio = 0, int $mes = 0): array
    {
        $sql = "SELECT ps.*, i.nombre AS institucion_nombre, p.nombre AS plan_nombre
                FROM pagos_saas ps
                INNER JOIN instituciones i ON ps.institucion_id = i.id
                INNER JOIN suscripciones s ON ps.suscripcion_id = s.id
                INNER JOIN planes p ON s.plan_id = p.id
                WHERE ps.estado = 'confirmado'";

        $params = [];
        if ($anio) {
            $sql .= " AND YEAR(ps.fecha_pago) = :anio";
            $params[':anio'] = $anio;
        }
        if ($mes) {
            $sql .= " AND MONTH(ps.fecha_pago) = :mes";
            $params[':mes'] = $mes;
        }

        $sql .= " ORDER BY ps.fecha_pago DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Genera el próximo número de factura: EduSaaS-2026-0001 */
    public function generarNumeroFactura(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pagos_saas WHERE YEAR(created_at) = :anio"
        );
        $stmt->execute([':anio' => $anio]);
        $correlativo = (int) $stmt->fetchColumn() + 1;
        return "EduSaaS-{$anio}-" . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    /** Ingresos agrupados por mes para el gráfico del dashboard */
    public function getIngresosPorMes(int $anio): array
    {
        $stmt = $this->db->prepare(
            "SELECT MONTH(fecha_pago) AS mes, SUM(monto) AS total
             FROM pagos_saas
             WHERE estado = 'confirmado' AND YEAR(fecha_pago) = :anio
             GROUP BY MONTH(fecha_pago)
             ORDER BY mes ASC"
        );
        $stmt->execute([':anio' => $anio]);
        $rows = $stmt->fetchAll();

        // Llenar los 12 meses (aunque no haya pagos en todos)
        $meses = array_fill(1, 12, 0);
        foreach ($rows as $row) {
            $meses[(int)$row['mes']] = (float)$row['total'];
        }
        return $meses;
    }
}
