<?php
// =====================================================
// EduSaaS RD — AnoEscolarModel
// =====================================================
// Gestión del año escolar de la institución.
// Solo puede haber UN año activo por institución a la vez.
// El año activo es la referencia para secciones, matrículas,
// períodos y calificaciones del ciclo actual.
//
// ⚠️ B-GM-2 pendiente: agregar UNIQUE en BD para garantizar
//    que solo un año tenga activo=1 por institución.
//    ALTER TABLE anos_escolares
//      ADD UNIQUE KEY uq_inst_activo (institucion_id, activo);
//    Esto requiere que activo sea NULL cuando no está activo
//    (no 0), porque UNIQUE permite múltiples NULL.
//
// Separado de GradoModel.php (B-GM-1 ✅)
// =====================================================

class AnoEscolarModel extends BaseModel
{
    protected string $table = 'anos_escolares';

    /**
     * Retorna el año escolar activo de la institución.
     * Retorna null si no hay año activo configurado.
     * Llamado desde controllers antes de operar con secciones
     * o matrículas para garantizar contexto temporal válido.
     *
     * @param  int        $instId  ID de la institución
     * @return array|null          Año activo o null
     */
    public function getActivo(int $instId): ?array
    {
        return $this->findBy(['institucion_id' => $instId, 'activo' => 1]);
    }

    /**
     * Retorna todos los años escolares de la institución,
     * ordenados del más reciente al más antiguo.
     * Usado en el listado del panel admin.
     *
     * @param  int   $instId  ID de la institución
     * @return array          Lista de años escolares
     */
    public function getByInstitucion(int $instId): array
    {
        return $this->where(
            ['institucion_id' => $instId],
            'fecha_inicio DESC'
        );
    }

    /**
     * Activa un año escolar y desactiva todos los demás
     * de la misma institución en una sola transacción.
     * Garantiza que nunca haya más de un año activo.
     *
     * @param  int  $id      ID del año escolar a activar
     * @param  int  $instId  ID de la institución (validación de tenant)
     * @return bool          true si la operación fue exitosa
     */
    public function activar(int $id, int $instId): bool
    {
        try {
            $this->db->beginTransaction();

            // Desactivar todos los años de esta institución
            $this->db->prepare(
                "UPDATE anos_escolares SET activo = 0
                 WHERE institucion_id = ?"
            )->execute([$instId]);

            // Activar el año seleccionado
            $this->db->prepare(
                "UPDATE anos_escolares SET activo = 1
                 WHERE id = ? AND institucion_id = ?"
            )->execute([$id, $instId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}