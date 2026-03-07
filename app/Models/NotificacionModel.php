<?php
// =====================================================
// EduSaaS RD - NotificacionModel
// =====================================================

class NotificacionModel extends BaseModel
{
    protected string $table = 'notificaciones_email';

    /**
     * Verifica si ya se envió una notificación de ese tipo
     * a esa institución HOY (evita duplicados en el mismo día).
     */
    public function yaEnviadaHoy(int $institucionId, string $tipo): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notificaciones_email
             WHERE institucion_id = :id
               AND tipo           = :tipo
               AND estado         = 'enviado'
               AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute([':id' => $institucionId, ':tipo' => $tipo]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Historial de notificaciones con nombre de institución */
    public function getAllConDetalle(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, i.nombre AS institucion_nombre,
                    u.nombres AS enviado_nombres, u.apellidos AS enviado_apellidos
             FROM notificaciones_email n
             INNER JOIN instituciones i ON n.institucion_id = i.id
             LEFT JOIN  usuarios u      ON n.enviado_por    = u.id
             ORDER BY n.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Estadísticas rápidas para el panel */
    public function getStats(): array
    {
        $stmt = $this->db->query(
            "SELECT
               COUNT(*) AS total,
               SUM(estado = 'enviado') AS enviados,
               SUM(estado = 'error')   AS errores
             FROM notificaciones_email
             WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        return $stmt->fetch() ?: ['total' => 0, 'enviados' => 0, 'errores' => 0];
    }

    /** Historial con filtros para la vista dedicada */
    public function getFiltrado(array $filtros = [], int $limit = 100): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where[]          = 'n.tipo = :tipo';
            $params[':tipo']  = $filtros['tipo'];
        }
        if (!empty($filtros['estado'])) {
            $where[]              = 'n.estado = :estado';
            $params[':estado']    = $filtros['estado'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]              = '(i.nombre LIKE :buscar OR n.destinatario LIKE :buscar2)';
            $params[':buscar']    = '%' . $filtros['buscar'] . '%';
            $params[':buscar2']   = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['desde'])) {
            $where[]              = 'n.created_at >= :desde';
            $params[':desde']     = $filtros['desde'];
        }

        $sql = "SELECT n.*, i.nombre AS institucion_nombre,
                       u.nombres AS enviado_nombres, u.apellidos AS enviado_apellidos
                FROM notificaciones_email n
                INNER JOIN instituciones i ON n.institucion_id = i.id
                LEFT JOIN  usuarios u      ON n.enviado_por    = u.id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY n.created_at DESC LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Contadores por tipo para el dashboard */
    public function getContadoresPorTipo(): array
    {
        $rows = $this->db->query(
            "SELECT tipo, estado, COUNT(*) AS n
             FROM notificaciones_email
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY tipo, estado"
        )->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            $result[$r['tipo']][$r['estado']] = (int)$r['n'];
        }
        return $result;
    }
}