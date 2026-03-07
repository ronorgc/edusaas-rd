<?php
// =====================================================
// EduSaaS RD - PreregistroModel
// Solicitudes de registro público de colegios
// =====================================================

class PreregistroModel extends BaseModel
{
    protected string $table = 'preregistro_colegios';

    public function getPendientes(): array
    {
        return $this->db->query(
            "SELECT r.*, p.nombre AS plan_nombre
             FROM preregistro_colegios r
             LEFT JOIN planes p ON r.plan_interes = p.id
             WHERE r.estado = 'pendiente'
             ORDER BY r.created_at DESC"
        )->fetchAll();
    }

    public function getTodos(string $estado = ''): array
    {
        $sql = "SELECT r.*, p.nombre AS plan_nombre
                FROM preregistro_colegios r
                LEFT JOIN planes p ON r.plan_interes = p.id";
        if ($estado) {
            $stmt = $this->db->prepare($sql . " WHERE r.estado = :e ORDER BY r.created_at DESC");
            $stmt->execute([':e' => $estado]);
            return $stmt->fetchAll();
        }
        return $this->db->query($sql . " ORDER BY r.created_at DESC")->fetchAll();
    }

    public function getConDetalle(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, p.nombre AS plan_nombre,
                    i.nombre AS inst_nombre,
                    u.nombres AS revisor_nombre, u.apellidos AS revisor_apellido
             FROM preregistro_colegios r
             LEFT JOIN planes p ON r.plan_interes = p.id
             LEFT JOIN instituciones i ON r.institucion_id = i.id
             LEFT JOIN usuarios u ON r.revisado_por = u.id
             WHERE r.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailYaRegistrado(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM preregistro_colegios
             WHERE email = :e AND estado IN ('pendiente','aprobado','contactado')"
        );
        $stmt->execute([':e' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }
}