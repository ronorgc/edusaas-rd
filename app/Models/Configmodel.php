<?php
// =====================================================
// EduSaaS RD - ConfigModel
// Lee y escribe la configuración del sistema desde BD.
// Tiene caché en memoria para no hacer múltiples queries.
// =====================================================

class ConfigModel
{
    private static array $cache = [];
    private static bool  $cargado = false;

    // ── Leer un valor ────────────────────────────────

    /**
     * Obtiene el valor de una clave de configuración.
     * Retorna $default si la clave no existe.
     *
     * Uso: ConfigModel::get('empresa_nombre', 'EduSaaS RD')
     */
    public static function get(string $clave, mixed $default = ''): mixed
    {
        self::cargar();
        return self::$cache[$clave] ?? $default;
    }

    /**
     * Obtiene todas las claves de un grupo.
     * Uso: ConfigModel::getGrupo('empresa')
     */
    public static function getGrupo(string $grupo): array
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT clave, valor, tipo, descripcion
                 FROM configuracion_sistema
                 WHERE grupo = :g ORDER BY clave"
            );
            $stmt->execute([':g' => $grupo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Obtiene todos los grupos con sus claves.
     */
    public static function getTodo(): array
    {
        try {
            $db   = Database::getInstance();
            $rows = $db->query(
                "SELECT * FROM configuracion_sistema ORDER BY grupo, clave"
            )->fetchAll(PDO::FETCH_ASSOC);

            $grupos = [];
            foreach ($rows as $row) {
                $grupos[$row['grupo']][] = $row;
            }
            return $grupos;
        } catch (Exception) {
            return [];
        }
    }

    // ── Escribir valores ─────────────────────────────

    /**
     * Guarda un valor. Crea la clave si no existe.
     */
    public static function set(string $clave, mixed $valor): void
    {
        try {
            $db = Database::getInstance();
            $db->prepare(
                "INSERT INTO configuracion_sistema (clave, valor)
                 VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE valor = :v2"
            )->execute([':k' => $clave, ':v' => $valor, ':v2' => $valor]);

            // Actualizar caché en memoria
            self::$cache[$clave] = $valor;
        } catch (Exception) {}
    }

    /**
     * Guarda múltiples claves de golpe.
     * Uso: ConfigModel::setMultiple(['empresa_nombre' => 'X', ...])
     */
    public static function setMultiple(array $datos): void
    {
        foreach ($datos as $clave => $valor) {
            self::set($clave, $valor);
        }
    }

    // ── Privados ─────────────────────────────────────

    private static function cargar(): void
    {
        if (self::$cargado) return;
        try {
            $db   = Database::getInstance();
            $rows = $db->query("SELECT clave, valor FROM configuracion_sistema")
                       ->fetchAll(PDO::FETCH_KEY_PAIR);
            self::$cache  = $rows;
            self::$cargado = true;
        } catch (Exception) {
            self::$cargado = true; // Evitar loops si la tabla no existe aún
        }
    }

    /** Limpia la caché (útil después de guardar) */
    public static function limpiarCache(): void
    {
        self::$cache   = [];
        self::$cargado = false;
    }
}