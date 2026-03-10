<?php
// =====================================================
// EduSaaS RD — SeccionModel
// =====================================================
// Gestión de secciones (grupos) por grado y año escolar.
// Una sección pertenece a un grado y a un año escolar.
// Ejemplo: "1-A" del "Primer Grado" del "Año 2025-2026".
//
// Separado de GradoModel.php (B-GM-1 ✅)
// =====================================================

class SeccionModel extends BaseModel
{
    protected string $table = 'secciones';

    /**
     * Retorna las secciones activas de un grado específico.
     * Usado en selectores de formulario (matrícula, horarios).
     *
     * @param  int   $gradoId  ID del grado
     * @return array           Lista de secciones
     */
    public function getByGrado(int $gradoId): array
    {
        return $this->where(
            ['grado_id' => $gradoId, 'activo' => 1],
            'nombre ASC'
        );
    }

    /**
     * Retorna todas las secciones activas de la institución
     * vinculadas al año escolar activo, con nombre del grado
     * y conteo de estudiantes matriculados.
     * Usado en el listado de secciones del panel admin.
     *
     * @param  int   $instId  ID de la institución
     * @return array          Secciones con grado_nombre, nivel, total_estudiantes
     */
    public function getByInstitucion(int $instId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    g.nombre AS grado_nombre,
                    g.nivel,
                    COUNT(m.id) AS total_estudiantes
             FROM secciones s
             INNER JOIN grados        g ON s.grado_id       = g.id
             INNER JOIN anos_escolares a ON s.ano_escolar_id = a.id AND a.activo = 1
             LEFT JOIN  matriculas     m ON m.seccion_id     = s.id AND m.estado = 'activa'
             WHERE s.institucion_id = :id AND s.activo = 1
             GROUP BY s.id
             ORDER BY g.orden, g.nombre, s.nombre"
        );
        $stmt->execute([':id' => $instId]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna las secciones de un año escolar específico.
     * Usado al consultar secciones de un año que no es el activo.
     *
     * @param  int   $instId       ID de la institución
     * @param  int   $anoEscolarId ID del año escolar
     * @return array               Lista de secciones con nombre del grado
     */
    public function getByAnoEscolar(int $instId, int $anoEscolarId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, g.nombre AS grado_nombre, g.nivel
             FROM secciones s
             INNER JOIN grados g ON s.grado_id = g.id
             WHERE s.institucion_id  = :inst
               AND s.ano_escolar_id  = :ano
               AND s.activo          = 1
             ORDER BY g.orden, g.nombre, s.nombre"
        );
        $stmt->execute([':inst' => $instId, ':ano' => $anoEscolarId]);
        return $stmt->fetchAll();
    }
}
