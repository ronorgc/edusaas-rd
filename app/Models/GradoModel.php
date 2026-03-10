<?php
// =====================================================
// EduSaaS RD — GradoModel
// =====================================================
// Gestión de grados académicos por institución.
// Los grados son datos semi-fijos (seed MINERD):
//   Inicial, Primero … Sexto (Primaria),
//   Primero … Cuarto (Secundaria).
// El CRUD de grados no existe en el panel admin —
// se gestionan solo desde el superadmin o via BD.
//
// Separado de GradoModel.php (B-GM-1 ✅)
// Antes contenía GradoModel + SeccionModel + AnoEscolarModel
// en un solo archivo. Ahora cada clase tiene su propio archivo.
// =====================================================

class GradoModel extends BaseModel
{
    protected string $table = 'grados';

    /**
     * Retorna los grados activos de una institución,
     * ordenados por nivel y nombre para mostrar en selectores.
     *
     * @param  int   $instId  ID de la institución
     * @return array          Lista de grados
     */
    public function getByInstitucion(int $instId): array
    {
        return $this->where(
            ['institucion_id' => $instId, 'activo' => 1],
            'orden ASC, nombre ASC'
        );
    }

    /**
     * Retorna los grados con conteo de secciones activas
     * y estudiantes matriculados en el año escolar activo.
     * Usado en la vista de listado de grados del panel admin.
     *
     * @param  int   $instId  ID de la institución
     * @return array          Grados con columnas total_secciones y total_matriculados
     */
    public function getConConteos(int $instId): array
    {
        $stmt = $this->db->prepare(
            "SELECT g.*,
                    COUNT(DISTINCT s.id) AS total_secciones,
                    COUNT(DISTINCT m.id) AS total_matriculados
             FROM grados g
             LEFT JOIN secciones      s ON s.grado_id      = g.id AND s.activo = 1
             LEFT JOIN matriculas     m ON m.seccion_id     = s.id AND m.estado = 'activa'
             LEFT JOIN anos_escolares a ON m.ano_escolar_id = a.id AND a.activo  = 1
             WHERE g.institucion_id = :id AND g.activo = 1
             GROUP BY g.id
             ORDER BY g.orden, g.nombre"
        );
        $stmt->execute([':id' => $instId]);
        return $stmt->fetchAll();
    }
}