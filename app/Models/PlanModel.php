<?php
// =====================================================
// EduSaaS RD - PlanModel
// =====================================================

class PlanModel extends BaseModel
{
    protected string $table = 'planes';

    /** Devuelve solo los planes activos ordenados por precio */
    public function getActivos(): array
    {
        return $this->where(['activo' => 1], 'orden ASC');
    }
}
