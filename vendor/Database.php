<?php
// =====================================================
// EduSaaS RD - Conexión a Base de Datos (Singleton)
// =====================================================

class Database
{
    private static ?PDO $instance = null;

    // Constructor privado: nadie puede hacer "new Database()"
    private function __construct() {}

    /**
     * Devuelve la única instancia de PDO en toda la app.
     * Si no existe, la crea.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                // En producción nunca mostrar detalles del error al usuario
                $appConfig = require __DIR__ . '/../config/app.php';
                if ($appConfig['debug']) {
                    die('Error de conexión: ' . $e->getMessage());
                } else {
                    // Registrar en el log del servidor para diagnóstico (sin exponer al usuario)
                    error_log('[EduSaaS] Error de conexión a BD: ' . $e->getMessage());
                    die('Error interno del servidor. Contacte al administrador.');
                }
            }
        }

        return self::$instance;
    }
}