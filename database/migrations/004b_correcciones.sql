-- =====================================================
-- EduSaaS RD - Migración 004b: CORRECCIONES
-- Ejecutar si ya corriste 004_estudiantes.sql y hay errores.
-- Compatible con MySQL 5.7+ y MySQL 8.0+
--
-- Problemas que corrige:
--  1. Columnas faltantes en tablas que ya existían
--     (grados, secciones, anos_escolares sin activo/nivel/orden/capacidad)
--  2. DEFAULT (CURDATE()) → incompatible con MySQL 5.7
--  3. UNIQUE global en cedula/codigo → debe ser por institución
--  4. Tablas faltantes que el sistema ya referencia
-- =====================================================

USE `edusaas_rd`;

-- =====================================================
-- PASO 1: Columnas faltantes en tablas existentes
-- Usamos un procedimiento para no fallar si ya existen
-- =====================================================

DROP PROCEDURE IF EXISTS agregar_columna;
DELIMITER $$
CREATE PROCEDURE agregar_columna(
    IN tabla VARCHAR(64),
    IN columna VARCHAR(64),
    IN definicion TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tabla
          AND COLUMN_NAME  = columna
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tabla, '` ADD COLUMN `', columna, '` ', definicion);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- ── grados ──
CALL agregar_columna('grados', 'nivel',      "ENUM('inicial','primario','secundario') NOT NULL DEFAULT 'primario' AFTER nombre");
CALL agregar_columna('grados', 'orden',      "TINYINT DEFAULT 0 AFTER nivel");
CALL agregar_columna('grados', 'activo',     "TINYINT(1) DEFAULT 1 AFTER orden");
CALL agregar_columna('grados', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER activo");

-- ── secciones ──
CALL agregar_columna('secciones', 'capacidad',   "SMALLINT DEFAULT 40 AFTER nombre");
CALL agregar_columna('secciones', 'activo',      "TINYINT(1) DEFAULT 1 AFTER capacidad");
CALL agregar_columna('secciones', 'created_at',  "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER activo");

-- ── anos_escolares ──
CALL agregar_columna('anos_escolares', 'nombre',       "VARCHAR(20) NOT NULL DEFAULT '' AFTER institucion_id");
CALL agregar_columna('anos_escolares', 'fecha_inicio',  "DATE NOT NULL DEFAULT '2024-01-01' AFTER nombre");
CALL agregar_columna('anos_escolares', 'fecha_fin',     "DATE NOT NULL DEFAULT '2024-12-31' AFTER fecha_inicio");
CALL agregar_columna('anos_escolares', 'activo',        "TINYINT(1) DEFAULT 1 AFTER fecha_fin");
CALL agregar_columna('anos_escolares', 'created_at',    "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER activo");

-- ── estudiantes ──
CALL agregar_columna('estudiantes', 'nie',              "VARCHAR(20) NULL AFTER cedula");
CALL agregar_columna('estudiantes', 'lugar_nacimiento', "VARCHAR(100) NULL AFTER genero");
CALL agregar_columna('estudiantes', 'nacionalidad',     "VARCHAR(50) DEFAULT 'Dominicana' AFTER lugar_nacimiento");
CALL agregar_columna('estudiantes', 'foto',             "VARCHAR(255) NULL AFTER nacionalidad");
CALL agregar_columna('estudiantes', 'municipio',        "VARCHAR(60) NULL AFTER direccion");
CALL agregar_columna('estudiantes', 'provincia',        "VARCHAR(60) NULL AFTER municipio");
CALL agregar_columna('estudiantes', 'updated_at',       "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

-- ── tutores ──
CALL agregar_columna('tutores', 'telefono_trabajo', "VARCHAR(20) NULL AFTER telefono");
CALL agregar_columna('tutores', 'email',            "VARCHAR(150) NULL AFTER telefono_trabajo");
CALL agregar_columna('tutores', 'ocupacion',        "VARCHAR(80) NULL AFTER email");
CALL agregar_columna('tutores', 'es_responsable',   "TINYINT(1) DEFAULT 0 AFTER ocupacion");

-- ── matriculas ──
CALL agregar_columna('matriculas', 'observaciones', "TEXT NULL AFTER estado");

DROP PROCEDURE IF EXISTS agregar_columna;

-- =====================================================
-- PASO 2: Crear tablas faltantes (IF NOT EXISTS = seguro)
-- =====================================================

-- matriculas (si no existe)
CREATE TABLE IF NOT EXISTS `matriculas` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,
  `estudiante_id`   INT UNSIGNED    NOT NULL,
  `ano_escolar_id`  INT UNSIGNED    NOT NULL,
  `seccion_id`      INT UNSIGNED    NOT NULL,
  `fecha_matricula` DATE            NOT NULL,         -- sin DEFAULT (CURDATE()) → compatible 5.7
  `estado`          ENUM('activa','retirado','trasladado','graduado','reprobado') DEFAULT 'activa',
  `observaciones`   TEXT            NULL,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`)  REFERENCES `instituciones`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`estudiante_id`)   REFERENCES `estudiantes`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`ano_escolar_id`)  REFERENCES `anos_escolares`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seccion_id`)      REFERENCES `secciones`(`id`)      ON DELETE CASCADE,
  UNIQUE KEY `uq_matricula` (`estudiante_id`, `ano_escolar_id`),
  INDEX `idx_inst`    (`institucion_id`),
  INDEX `idx_est`     (`estudiante_id`),
  INDEX `idx_seccion` (`seccion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- secuencias_codigo
CREATE TABLE IF NOT EXISTS `secuencias_codigo` (
  `institucion_id`  INT UNSIGNED    NOT NULL PRIMARY KEY,
  `ultimo_numero`   INT UNSIGNED    DEFAULT 0,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PASO 3: Corregir UNIQUE global de cedula y codigo
-- (deben ser únicos por institución, no globalmente)
-- =====================================================

DROP PROCEDURE IF EXISTS quitar_unique;
DELIMITER $$
CREATE PROCEDURE quitar_unique(IN tabla VARCHAR(64), IN indice VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tabla
          AND INDEX_NAME   = indice
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tabla, '` DROP INDEX `', indice, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Quitar UNIQUE global
CALL quitar_unique('estudiantes', 'cedula');
CALL quitar_unique('estudiantes', 'codigo');

DROP PROCEDURE IF EXISTS quitar_unique;

-- Agregar UNIQUE compuesto (por institución)
DROP PROCEDURE IF EXISTS agregar_unique;
DELIMITER $$
CREATE PROCEDURE agregar_unique(IN tabla VARCHAR(64), IN indice VARCHAR(64), IN columnas VARCHAR(128))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tabla
          AND INDEX_NAME   = indice
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tabla, '` ADD UNIQUE KEY `', indice, '` (', columnas, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL agregar_unique('estudiantes', 'uq_codigo_inst',  '`institucion_id`, `codigo`');
CALL agregar_unique('estudiantes', 'uq_cedula_inst',  '`institucion_id`, `cedula`');

DROP PROCEDURE IF EXISTS agregar_unique;

-- =====================================================
-- PASO 4: Índices faltantes
-- =====================================================

DROP PROCEDURE IF EXISTS agregar_indice;
DELIMITER $$
CREATE PROCEDURE agregar_indice(IN tabla VARCHAR(64), IN indice VARCHAR(64), IN columnas VARCHAR(128))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tabla
          AND INDEX_NAME   = indice
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tabla, '` ADD INDEX `', indice, '` (', columnas, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL agregar_indice('grados',       'idx_inst',     '`institucion_id`');
CALL agregar_indice('secciones',    'idx_inst',     '`institucion_id`');
CALL agregar_indice('secciones',    'idx_grado',    '`grado_id`');
CALL agregar_indice('anos_escolares','idx_inst',    '`institucion_id`');
CALL agregar_indice('estudiantes',  'idx_inst',     '`institucion_id`');
CALL agregar_indice('estudiantes',  'idx_nombres',  '`apellidos`, `nombres`');
CALL agregar_indice('tutores',      'idx_est',      '`estudiante_id`');

DROP PROCEDURE IF EXISTS agregar_indice;

-- =====================================================
-- PASO 5: Corregir FK faltantes (solo si no existen)
-- =====================================================

DROP PROCEDURE IF EXISTS agregar_fk;
DELIMITER $$
CREATE PROCEDURE agregar_fk(
    IN tabla VARCHAR(64),
    IN fk_name VARCHAR(64),
    IN col VARCHAR(64),
    IN ref_tabla VARCHAR(64),
    IN ref_col VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA      = DATABASE()
          AND TABLE_NAME        = tabla
          AND CONSTRAINT_NAME   = fk_name
          AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', tabla, '` ADD CONSTRAINT `', fk_name,
            '` FOREIGN KEY (`', col, '`) REFERENCES `', ref_tabla, '` (`', ref_col, '`) ON DELETE CASCADE'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL agregar_fk('grados',        'fk_grados_inst',       'institucion_id', 'instituciones', 'id');
CALL agregar_fk('secciones',     'fk_secciones_inst',    'institucion_id', 'instituciones', 'id');
CALL agregar_fk('secciones',     'fk_secciones_grado',   'grado_id',       'grados',        'id');
CALL agregar_fk('anos_escolares','fk_anos_inst',          'institucion_id', 'instituciones', 'id');
CALL agregar_fk('estudiantes',   'fk_est_inst',           'institucion_id', 'instituciones', 'id');
CALL agregar_fk('tutores',       'fk_tutores_est',        'estudiante_id',  'estudiantes',   'id');

DROP PROCEDURE IF EXISTS agregar_fk;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================
SELECT 'grados'        AS tabla, COUNT(*) AS columnas FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='grados'
UNION ALL
SELECT 'secciones',      COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='secciones'
UNION ALL
SELECT 'anos_escolares', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='anos_escolares'
UNION ALL
SELECT 'estudiantes',    COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='estudiantes'
UNION ALL
SELECT 'tutores',        COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tutores'
UNION ALL
SELECT 'matriculas',     COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='matriculas'
UNION ALL
SELECT 'secuencias_codigo', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='secuencias_codigo';
