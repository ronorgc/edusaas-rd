<?php
// =====================================================
// EduSaaS RD - Modelo Base (Todos los modelos lo extienden)
// =====================================================

abstract class BaseModel
{
    protected PDO $db;
    protected string $table  = '';      // Nombre de la tabla en BD
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // --------------------------------------------------
    // LECTURA
    // --------------------------------------------------

    /**
     * Busca un registro por su ID.
     * Ejemplo: $usuario = $usuarioModel->find(5);
     */
    public function find(int $id): ?array
    {
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Devuelve todos los registros de la tabla.
     * Ejemplo: $todos = $usuarioModel->all();
     */
    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca registros según condiciones simples.
     * Ejemplo: $activos = $usuarioModel->where(['activo' => 1, 'rol_id' => 2]);
     */
    public function where(array $conditions, string $orderBy = ''): array
    {
        $clausulas = [];
        $valores   = [];

        foreach ($conditions as $campo => $valor) {
            $clausulas[] = "`{$campo}` = :{$campo}";
            $valores[":{$campo}"] = $valor;
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $clausulas);
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    /**
     * Como where() pero devuelve solo el primer resultado.
     */
    public function findBy(array $conditions): ?array
    {
        $resultados = $this->where($conditions);
        return $resultados[0] ?? null;
    }

    // --------------------------------------------------
    // ESCRITURA
    // --------------------------------------------------

    /**
     * Inserta un nuevo registro.
     * Devuelve el ID insertado.
     * Ejemplo: $id = $usuarioModel->create(['username' => 'juan', 'email' => '...']);
     */
    public function create(array $datos): int
    {
        $campos  = array_keys($datos);
        $valores = array_map(fn($c) => ":{$c}", $campos);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', array_map(fn($c) => "`{$c}`", $campos)),
            implode(', ', $valores)
        );

        $stmt = $this->db->prepare($sql);

        $params = [];
        foreach ($datos as $campo => $valor) {
            $params[":{$campo}"] = $valor;
        }

        $stmt->execute($params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un registro por ID.
     * Devuelve true si se actualizó al menos 1 fila.
     * Ejemplo: $ok = $usuarioModel->update(5, ['email' => 'nuevo@correo.com']);
     */
    public function update(int $id, array $datos): bool
    {
        $sets   = [];
        $params = [':id' => $id];

        foreach ($datos as $campo => $valor) {
            $sets[]                = "`{$campo}` = :{$campo}";
            $params[":{$campo}"] = $valor;
        }

        $sql  = "UPDATE `{$this->table}` SET " . implode(', ', $sets)
              . " WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina físicamente un registro por ID.
     * En este sistema se recomienda usar softDelete() cuando sea posible.
     */
    public function delete(int $id): bool
    {
        $sql  = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft delete: pone activo = 0 en lugar de borrar físicamente.
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['activo' => 0]);
    }

    // --------------------------------------------------
    // UTILIDADES
    // --------------------------------------------------

    /**
     * Cuenta registros según condiciones.
     * Ejemplo: $total = $usuarioModel->count(['institucion_id' => 3]);
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $sql  = "SELECT COUNT(*) FROM `{$this->table}`";
            $stmt = $this->db->query($sql);
        } else {
            $clausulas = [];
            $valores   = [];
            foreach ($conditions as $campo => $valor) {
                $clausulas[] = "`{$campo}` = :{$campo}";
                $valores[":{$campo}"] = $valor;
            }
            $sql  = "SELECT COUNT(*) FROM `{$this->table}` WHERE " . implode(' AND ', $clausulas);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($valores);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica si existe un registro con esas condiciones.
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }

    /**
     * Paginación simple.
     * Devuelve ['datos' => [...], 'total' => N, 'paginas' => N, 'pagina_actual' => N]
     */
    public function paginate(int $pagina = 1, int $porPagina = REGISTROS_POR_PAGINA, array $conditions = []): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $total  = $this->count($conditions);

        if (empty($conditions)) {
            $sql  = "SELECT * FROM `{$this->table}` LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
        } else {
            $clausulas = [];
            $valores   = [];
            foreach ($conditions as $campo => $valor) {
                $clausulas[] = "`{$campo}` = :{$campo}";
                $valores[":{$campo}"] = $valor;
            }
            $sql  = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $clausulas)
                  . " LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            foreach ($valores as $k => $v) {
                $stmt->bindValue($k, $v);
            }
        }

        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return [
            'datos'        => $stmt->fetchAll(),
            'total'        => $total,
            'paginas'      => (int) ceil($total / $porPagina),
            'pagina_actual' => $pagina,
            'por_pagina'   => $porPagina,
        ];
    }
}
