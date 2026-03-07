<?php
class GradoModel extends BaseModel
{
    protected string $table = 'grados';

    public function getByInstitucion(int $instId): array
    {
        return $this->where(['institucion_id' => $instId, 'activo' => 1], 'orden ASC, nombre ASC');
    }

    public function getConConteos(int $instId): array
    {
        $stmt = $this->db->prepare(
            "SELECT g.*,
                    COUNT(DISTINCT s.id) AS total_secciones,
                    COUNT(DISTINCT m.id) AS total_matriculados
             FROM grados g
             LEFT JOIN secciones      s ON s.grado_id = g.id AND s.activo = 1
             LEFT JOIN matriculas     m ON m.seccion_id = s.id AND m.estado = 'activa'
             LEFT JOIN anos_escolares a ON m.ano_escolar_id = a.id AND a.activo = 1
             WHERE g.institucion_id = :id AND g.activo = 1
             GROUP BY g.id
             ORDER BY g.orden, g.nombre"
        );
        $stmt->execute([':id' => $instId]);
        return $stmt->fetchAll();
    }
}

class SeccionModel extends BaseModel
{
    protected string $table = 'secciones';

    public function getByGrado(int $gradoId): array
    {
        return $this->where(['grado_id' => $gradoId, 'activo' => 1], 'nombre ASC');
    }

    /** Secciones del año escolar activo de la institución */
    public function getByInstitucion(int $instId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, g.nombre AS grado_nombre, g.nivel,
                    COUNT(m.id) AS total_estudiantes
             FROM secciones s
             INNER JOIN grados        g ON s.grado_id      = g.id
             INNER JOIN anos_escolares a ON s.ano_escolar_id = a.id AND a.activo = 1
             LEFT JOIN matriculas     m ON m.seccion_id    = s.id AND m.estado = 'activa'
             WHERE s.institucion_id = :id AND s.activo = 1
             GROUP BY s.id
             ORDER BY g.orden, g.nombre, s.nombre"
        );
        $stmt->execute([':id' => $instId]);
        return $stmt->fetchAll();
    }
}

class AnoEscolarModel extends BaseModel
{
    protected string $table = 'anos_escolares';

    public function getActivo(int $instId): ?array
    {
        return $this->findBy(['institucion_id' => $instId, 'activo' => 1]);
    }

    public function getByInstitucion(int $instId): array
    {
        return $this->where(['institucion_id' => $instId], 'fecha_inicio DESC');
    }
}
