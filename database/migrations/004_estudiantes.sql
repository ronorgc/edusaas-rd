-- =====================================================
-- EduSaaS RD - Migración 004: Módulo de Estudiantes
-- Ejecutar después de 003_notificaciones.sql
-- =====================================================

USE `edusaas_rd`;

-- =====================================================
-- TABLA: grados
-- Catálogo de grados (1ro, 2do, …, 6to Bachillerato)
-- =====================================================
CREATE TABLE IF NOT EXISTS `grados` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,
  `nombre`          VARCHAR(60)     NOT NULL  COMMENT 'Ej: 1er Grado, 2do Bachillerato',
  `nivel`           ENUM('inicial','primario','secundario') NOT NULL DEFAULT 'primario',
  `orden`           TINYINT         DEFAULT 0,
  `activo`          TINYINT(1)      DEFAULT 1,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  INDEX `idx_inst` (`institucion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: secciones
-- Cada grado puede tener varias secciones: A, B, C…
-- =====================================================
CREATE TABLE IF NOT EXISTS `secciones` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,
  `grado_id`        INT UNSIGNED    NOT NULL,
  `nombre`          VARCHAR(10)     NOT NULL  COMMENT 'Ej: A, B, C, Matutina, Vespertina',
  `capacidad`       SMALLINT        DEFAULT 40,
  `activo`          TINYINT(1)      DEFAULT 1,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`grado_id`)       REFERENCES `grados`(`id`)       ON DELETE CASCADE,
  INDEX `idx_inst`  (`institucion_id`),
  INDEX `idx_grado` (`grado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: anos_escolares
-- Cada año escolar que maneja la institución
-- =====================================================
CREATE TABLE IF NOT EXISTS `anos_escolares` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,
  `nombre`          VARCHAR(20)     NOT NULL  COMMENT 'Ej: 2024-2025',
  `fecha_inicio`    DATE            NOT NULL,
  `fecha_fin`       DATE            NOT NULL,
  `activo`          TINYINT(1)      DEFAULT 1  COMMENT '1 = año escolar vigente',
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  INDEX `idx_inst` (`institucion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: estudiantes
-- Datos personales del estudiante (permanentes)
-- =====================================================
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,

  -- Identificación
  `codigo`          VARCHAR(20)     NOT NULL  COMMENT 'Código interno del colegio',
  `cedula`          VARCHAR(15)     NULL      COMMENT 'Cédula o pasaporte (opcional)',
  `nie`             VARCHAR(20)     NULL      COMMENT 'Número ID MINERD (opcional)',

  -- Datos personales
  `nombres`         VARCHAR(80)     NOT NULL,
  `apellidos`       VARCHAR(80)     NOT NULL,
  `fecha_nacimiento` DATE           NULL,
  `genero`          ENUM('M','F','otro') DEFAULT 'M',
  `lugar_nacimiento` VARCHAR(100)   NULL,
  `nacionalidad`    VARCHAR(50)     DEFAULT 'Dominicana',
  `foto`            VARCHAR(255)    NULL,  -- ruta relativa a /uploads/fotos/

  -- Contacto / dirección
  `direccion`       TEXT            NULL,
  `municipio`       VARCHAR(60)     NULL,
  `provincia`       VARCHAR(60)     NULL,
  `telefono`        VARCHAR(20)     NULL,
  `email`           VARCHAR(150)    NULL,

  -- Estado
  `activo`          TINYINT(1)      DEFAULT 1,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_codigo_inst` (`institucion_id`, `codigo`),
  UNIQUE KEY `uq_cedula_inst` (`institucion_id`, `cedula`),
  INDEX `idx_inst`      (`institucion_id`),
  INDEX `idx_nombres`   (`apellidos`, `nombres`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: tutores
-- Padre, madre o tutor legal del estudiante
-- =====================================================
CREATE TABLE IF NOT EXISTS `tutores` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `estudiante_id`   INT UNSIGNED    NOT NULL,
  `parentesco`      ENUM('padre','madre','tutor','abuelo','abuela','tio','otro') NOT NULL DEFAULT 'tutor',
  `nombres`         VARCHAR(80)     NOT NULL,
  `apellidos`       VARCHAR(80)     NOT NULL,
  `cedula`          VARCHAR(15)     NULL,
  `telefono`        VARCHAR(20)     NULL,
  `telefono_trabajo`VARCHAR(20)     NULL,
  `email`           VARCHAR(150)    NULL,
  `ocupacion`       VARCHAR(80)     NULL,
  `es_responsable`  TINYINT(1)      DEFAULT 0  COMMENT '1 = responsable de pagos',
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes`(`id`) ON DELETE CASCADE,
  INDEX `idx_est` (`estudiante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: matriculas
-- Cada vez que un estudiante se inscribe a un año escolar
-- en una sección determinada
-- =====================================================
CREATE TABLE IF NOT EXISTS `matriculas` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED    NOT NULL,
  `estudiante_id`   INT UNSIGNED    NOT NULL,
  `ano_escolar_id`  INT UNSIGNED    NOT NULL,
  `seccion_id`      INT UNSIGNED    NOT NULL,
  `fecha_matricula` DATE            NOT NULL,             -- sin DEFAULT, se pasa desde PHP
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

-- =====================================================
-- SECUENCIA: código de estudiante por institución
-- Sirve para generar el código interno automáticamente
-- =====================================================
CREATE TABLE IF NOT EXISTS `secuencias_codigo` (
  `institucion_id`  INT UNSIGNED    NOT NULL PRIMARY KEY,
  `ultimo_numero`   INT UNSIGNED    DEFAULT 0,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATOS DEMO: Grados estándar MINERD (Primaria)
-- Se insertan al crear una institución nueva
-- (Este INSERT es solo un ejemplo para desarrollo local)
-- =====================================================
-- INSERT INTO grados (institucion_id, nombre, nivel, orden) VALUES
--   (1,'1er Grado','primario',1), (1,'2do Grado','primario',2),
--   (1,'3er Grado','primario',3), (1,'4to Grado','primario',4),
--   (1,'5to Grado','primario',5), (1,'6to Grado','primario',6);
