-- =====================================================
-- EduSaaS RD - Migración 002: Planes y Facturación SaaS
-- Ejecutar DESPUÉS de database.sql principal
-- =====================================================

USE `edusaas_rd`;

-- =====================================================
-- TABLA: planes
-- Los 3 planes que ofreces a los colegios
-- =====================================================
CREATE TABLE `planes` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`              VARCHAR(50)    NOT NULL COMMENT 'Básico, Profesional, Premium',
  `descripcion`         TEXT           NULL,
  `precio_mensual`      DECIMAL(10,2)  NOT NULL,
  `precio_anual`        DECIMAL(10,2)  NOT NULL  COMMENT 'Precio con descuento anual',
  `max_estudiantes`     INT            NOT NULL  COMMENT '0 = ilimitado',
  `max_profesores`      INT            NOT NULL  COMMENT '0 = ilimitado',
  `max_secciones`       INT            NOT NULL  COMMENT '0 = ilimitado',
  -- Funcionalidades incluidas (1 = sí, 0 = no)
  `incluye_pagos`       TINYINT(1)     DEFAULT 0 COMMENT 'Módulo de pagos y cuotas',
  `incluye_reportes`    TINYINT(1)     DEFAULT 0 COMMENT 'Reportes avanzados PDF/Excel',
  `incluye_comunicados` TINYINT(1)     DEFAULT 1 COMMENT 'Comunicados a padres',
  `incluye_api`         TINYINT(1)     DEFAULT 0 COMMENT 'Acceso a API REST',
  `color`               VARCHAR(7)     DEFAULT '#1a56db' COMMENT 'Color para UI',
  `icono`               VARCHAR(50)    DEFAULT 'bi-box'  COMMENT 'Bootstrap icon',
  `orden`               INT            DEFAULT 0,
  `activo`              TINYINT(1)     DEFAULT 1,
  `created_at`          TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar los 3 planes
INSERT INTO `planes` (
  `nombre`, `descripcion`, `precio_mensual`, `precio_anual`,
  `max_estudiantes`, `max_profesores`, `max_secciones`,
  `incluye_pagos`, `incluye_reportes`, `incluye_comunicados`, `incluye_api`,
  `color`, `icono`, `orden`
) VALUES
(
  'Básico',
  'Ideal para centros educativos pequeños que inician su digitalización.',
  1500.00, 15000.00,
  150, 15, 6,
  0, 0, 1, 0,
  '#64748b', 'bi-box', 1
),
(
  'Profesional',
  'Para colegios medianos con necesidades de gestión completa.',
  3500.00, 35000.00,
  500, 40, 20,
  1, 1, 1, 0,
  '#1a56db', 'bi-briefcase-fill', 2
),
(
  'Premium',
  'Sin límites. Para instituciones grandes con múltiples secciones.',
  7000.00, 70000.00,
  0, 0, 0,
  1, 1, 1, 1,
  '#f59e0b', 'bi-star-fill', 3
);

-- =====================================================
-- TABLA: suscripciones
-- La suscripción activa de cada colegio
-- =====================================================
CREATE TABLE `suscripciones` (
  `id`                  INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`      INT UNSIGNED   NOT NULL,
  `plan_id`             INT UNSIGNED   NOT NULL,
  `tipo_facturacion`    ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  `monto`               DECIMAL(10,2)  NOT NULL COMMENT 'Monto acordado (puede diferir del plan)',
  `fecha_inicio`        DATE           NOT NULL,
  `fecha_vencimiento`   DATE           NOT NULL,
  `estado`              ENUM('activa','vencida','suspendida','cancelada') DEFAULT 'activa',
  `renovacion_auto`     TINYINT(1)     DEFAULT 0,
  `notas`               TEXT           NULL COMMENT 'Notas internas del super admin',
  `creado_por`          INT UNSIGNED   NULL COMMENT 'Super admin que creó la suscripción',
  `created_at`          TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`)        REFERENCES `planes`(`id`),
  FOREIGN KEY (`creado_por`)     REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_institucion`        (`institucion_id`),
  INDEX `idx_estado`             (`estado`),
  INDEX `idx_vencimiento`        (`fecha_vencimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pagos_saas
-- Pagos que TÚ recibes de los colegios (tu facturación)
-- =====================================================
CREATE TABLE `pagos_saas` (
  `id`                  INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`      INT UNSIGNED   NOT NULL,
  `suscripcion_id`      INT UNSIGNED   NOT NULL,
  `numero_factura`      VARCHAR(20)    NOT NULL UNIQUE COMMENT 'EduSaaS-2026-0001',
  `monto`               DECIMAL(10,2)  NOT NULL,
  `fecha_pago`          DATE           NOT NULL,
  `metodo_pago`         ENUM('transferencia','efectivo','tarjeta','cheque') NOT NULL,
  `referencia`          VARCHAR(100)   NULL COMMENT 'Número de transferencia, etc.',
  `periodo_desde`       DATE           NOT NULL COMMENT 'Inicio del período que cubre este pago',
  `periodo_hasta`       DATE           NOT NULL COMMENT 'Fin del período que cubre este pago',
  `estado`              ENUM('confirmado','pendiente','anulado') DEFAULT 'confirmado',
  `notas`               TEXT           NULL,
  `registrado_por`      INT UNSIGNED   NULL,
  `created_at`          TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`)  REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`suscripcion_id`)  REFERENCES `suscripciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`registrado_por`)  REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_institucion`         (`institucion_id`),
  INDEX `idx_fecha`               (`fecha_pago`),
  INDEX `idx_estado`              (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: log_accesos_saas
-- Registro de cuando cada colegio fue suspendido/activado
-- =====================================================
CREATE TABLE `log_estado_instituciones` (
  `id`             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `institucion_id` INT UNSIGNED  NOT NULL,
  `accion`         ENUM('activada','suspendida','cancelada','reactivada','plan_cambiado') NOT NULL,
  `motivo`         TEXT          NULL,
  `realizado_por`  INT UNSIGNED  NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`realizado_por`)  REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_institucion` (`institucion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
