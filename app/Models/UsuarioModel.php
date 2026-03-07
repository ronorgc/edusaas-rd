<?php
// =====================================================
// EduSaaS RD - UsuarioModel
// =====================================================

class UsuarioModel extends BaseModel
{
    protected string $table = 'usuarios';

    /**
     * Busca un usuario por su username o email.
     * Se usa en el proceso de login.
     */
    public function findByUsernameOrEmail(string $valor): ?array
    {
        $sql = "SELECT u.*, r.nombre AS rol_nombre 
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE (u.username = :username OR u.email = :email)
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $valor, ':email' => $valor]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene todos los usuarios de una institución con su rol.
     */
    public function getByInstitucion(int $institucionId): array
    {
        $sql = "SELECT u.*, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.institucion_id = :id
                ORDER BY u.apellidos, u.nombres";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $institucionId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un usuario con la contraseña hasheada.
     */
    public function createWithPassword(array $datos): int
    {
        $datos['password'] = password_hash($datos['password'], PASSWORD_BCRYPT);
        return $this->create($datos);
    }

    /**
     * Cambia la contraseña de un usuario.
     */
    public function changePassword(int $id, string $nuevaPassword): bool
    {
        return $this->update($id, [
            'password' => password_hash($nuevaPassword, PASSWORD_BCRYPT)
        ]);
    }

    /**
     * Verifica si un username ya está en uso.
     */
    public function usernameExiste(string $username, ?int $exceptoId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE username = :username";
        $params = [':username' => $username];

        if ($exceptoId) {
            $sql .= " AND id != :id";
            $params[':id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
