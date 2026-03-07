<?php
class EstudianteModel extends BaseModel
{
    protected string $table = 'estudiantes';

    public function getAllConMatricula(int $instId, array $filtros = []): array
    {
        $where  = ['e.institucion_id = :inst', 'e.activo = 1'];
        $params = [':inst' => $instId, ':inst2' => $instId];

        if (!empty($filtros['busqueda'])) {
            $where[]      = "(e.nombres LIKE :q OR e.apellidos LIKE :q OR e.codigo_estudiante LIKE :q)";
            $params[':q'] = '%' . $filtros['busqueda'] . '%';
        }
        if (!empty($filtros['grado_id'])) {
            $where[]          = 'g.id = :grado';
            $params[':grado'] = $filtros['grado_id'];
        }
        if (!empty($filtros['seccion_id'])) {
            $where[]            = 'm.seccion_id = :seccion';
            $params[':seccion'] = $filtros['seccion_id'];
        }

        $sql = "SELECT e.*,
                       m.id           AS matricula_id,
                       m.estado       AS matricula_estado,
                       s.nombre       AS seccion_nombre,
                       g.nombre       AS grado_nombre,
                       g.id           AS grado_id,
                       a.nombre       AS ano_escolar
                FROM estudiantes e
                LEFT JOIN matriculas     m ON m.estudiante_id  = e.id
                      AND m.institucion_id = :inst2
                LEFT JOIN secciones      s ON m.seccion_id     = s.id
                LEFT JOIN grados         g ON s.grado_id       = g.id
                LEFT JOIN anos_escolares a ON m.ano_escolar_id = a.id AND a.activo = 1
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.apellidos, e.nombres";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getConTutores(int $id): ?array
    {
        $est = $this->find($id);
        if (!$est) return null;

        $stmt = $this->db->prepare(
            "SELECT * FROM tutores WHERE estudiante_id = :id ORDER BY es_responsable DESC"
        );
        $stmt->execute([':id' => $id]);
        $est['tutores'] = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            "SELECT m.*, s.nombre AS seccion, g.nombre AS grado, a.nombre AS ano_escolar
             FROM matriculas m
             INNER JOIN secciones     s ON m.seccion_id    = s.id
             INNER JOIN grados        g ON s.grado_id      = g.id
             INNER JOIN anos_escolares a ON m.ano_escolar_id = a.id
             WHERE m.estudiante_id = :id
             ORDER BY a.fecha_inicio DESC"
        );
        $stmt->execute([':id' => $id]);
        $est['historial_matriculas'] = $stmt->fetchAll();

        return $est;
    }

    public function generarCodigo(int $instId): string
    {
        // No iniciamos transacción aquí — el controlador ya tiene una activa.
        $this->db->prepare(
            "INSERT INTO secuencias_codigo (institucion_id, ultimo_numero)
             VALUES (:id, 1)
             ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1"
        )->execute([':id' => $instId]);

        $num = (int) $this->db->query(
            "SELECT ultimo_numero FROM secuencias_codigo WHERE institucion_id = {$instId}"
        )->fetchColumn();

        return 'EST-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function getStats(int $instId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(sexo = 'M') AS masculino,
                    SUM(sexo = 'F') AS femenino
             FROM estudiantes
             WHERE institucion_id = :id AND activo = 1"
        );
        $stmt->execute([':id' => $instId]);
        $stats = $stmt->fetch();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM matriculas m
             INNER JOIN anos_escolares a ON m.ano_escolar_id = a.id
             WHERE m.institucion_id = :id AND a.activo = 1 AND m.estado = 'activa'"
        );
        $stmt->execute([':id' => $instId]);
        $stats['matriculados'] = (int) $stmt->fetchColumn();

        return $stats;
    }
}
