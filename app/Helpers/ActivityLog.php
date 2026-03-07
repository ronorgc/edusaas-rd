<?php
// =====================================================
// EduSaaS RD - ActivityLog
// Helper estático para registrar eventos en log_actividad.
// Uso: ActivityLog::registrar('instituciones', 'suspender', 'Suspendida: Colegio X')
// =====================================================

class ActivityLog
{
    /**
     * Registra una acción en el log global.
     *
     * @param string     $modulo      Módulo afectado (instituciones, planes, cobros...)
     * @param string     $accion      Verbo de la acción (crear, editar, eliminar, aprobar...)
     * @param string     $descripcion Texto legible del evento
     * @param array      $opciones    Opciones adicionales:
     *                                - entidad_tipo: tipo de objeto afectado
     *                                - entidad_id:   ID del objeto afectado
     *                                - detalle:      array que se guardará como JSON
     */
    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        array  $opciones = []
    ): void {
        try {
            $db = Database::getInstance();

            $usuarioId     = $_SESSION['usuario_id']     ?? null;
            $usuarioNombre = null;

            if ($usuarioId) {
                $nombres   = $_SESSION['usuario']['nombres']   ?? '';
                $apellidos = $_SESSION['usuario']['apellidos'] ?? '';
                $usuarioNombre = trim("{$nombres} {$apellidos}") ?: null;
            }

            $detalle = isset($opciones['detalle'])
                ? json_encode($opciones['detalle'], JSON_UNESCAPED_UNICODE)
                : null;

            $db->prepare(
                "INSERT INTO log_actividad
                    (modulo, accion, descripcion, detalle, entidad_tipo, entidad_id,
                     usuario_id, usuario_nombre, ip)
                 VALUES
                    (:modulo, :accion, :desc, :detalle, :etipo, :eid,
                     :uid, :unombre, :ip)"
            )->execute([
                ':modulo'   => $modulo,
                ':accion'   => $accion,
                ':desc'     => $descripcion,
                ':detalle'  => $detalle,
                ':etipo'    => $opciones['entidad_tipo'] ?? null,
                ':eid'      => $opciones['entidad_id']   ?? null,
                ':uid'      => $usuarioId,
                ':unombre'  => $usuarioNombre,
                ':ip'       => $_SERVER['REMOTE_ADDR']   ?? null,
            ]);

        } catch (Exception $e) {
            // El log nunca debe romper la aplicación
            error_log('[ActivityLog] Error: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los últimos N registros con filtros opcionales.
     */
    public static function getRecientes(int $limite = 50, array $filtros = []): array
    {
        try {
            $db     = Database::getInstance();
            $where  = [];
            $params = [];

            if (!empty($filtros['modulo'])) {
                $where[]           = 'modulo = :modulo';
                $params[':modulo'] = $filtros['modulo'];
            }
            if (!empty($filtros['usuario_id'])) {
                $where[]              = 'usuario_id = :uid';
                $params[':uid']       = $filtros['usuario_id'];
            }
            if (!empty($filtros['entidad_tipo']) && !empty($filtros['entidad_id'])) {
                $where[]              = 'entidad_tipo = :etipo AND entidad_id = :eid';
                $params[':etipo']     = $filtros['entidad_tipo'];
                $params[':eid']       = $filtros['entidad_id'];
            }
            if (!empty($filtros['buscar'])) {
                $where[]              = 'descripcion LIKE :buscar';
                $params[':buscar']    = '%' . $filtros['buscar'] . '%';
            }
            if (!empty($filtros['desde'])) {
                $where[]              = 'created_at >= :desde';
                $params[':desde']     = $filtros['desde'];
            }

            $sql = "SELECT * FROM log_actividad"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY created_at DESC LIMIT " . (int)$limite;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Contadores por módulo para el dashboard del log.
     */
    public static function getContadoresPorModulo(): array
    {
        try {
            $db   = Database::getInstance();
            $rows = $db->query(
                "SELECT modulo, COUNT(*) AS n
                 FROM log_actividad
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY modulo ORDER BY n DESC"
            )->fetchAll();

            $result = [];
            foreach ($rows as $r) $result[$r['modulo']] = (int)$r['n'];
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
}