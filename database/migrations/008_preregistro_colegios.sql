-- =====================================================
-- EduSaaS RD — Migración 008: Preregistro de Colegios
-- Solicitudes de registro público de nuevas instituciones
-- =====================================================

USE `edusaas_rd`;

CREATE TABLE IF NOT EXISTS `preregistro_colegios` (
  `id`              INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(150)   NOT NULL,
  `tipo`            ENUM('publico','privado') NOT NULL DEFAULT 'privado',
  `email`           VARCHAR(150)   NOT NULL,
  `telefono`        VARCHAR(30)    NULL,
  `municipio`       VARCHAR(100)   NULL,
  `provincia`       VARCHAR(100)   NULL,
  `codigo_minerd`   VARCHAR(20)    NULL,
  `nombre_director` VARCHAR(150)   NULL COMMENT 'Nombre del director o contacto principal',
  `cargo_contacto`  VARCHAR(80)    NULL COMMENT 'Cargo del contacto (Director, Administrador...)',
  `cant_estudiantes`SMALLINT       NULL COMMENT 'Estimado de estudiantes',
  `mensaje`         TEXT           NULL COMMENT 'Mensaje libre del solicitante',
  `plan_interes`    INT UNSIGNED   NULL COMMENT 'Plan que les interesa (FK planes)',
  `estado`          ENUM('pendiente','aprobado','rechazado','contactado')
                                   NOT NULL DEFAULT 'pendiente',
  `notas_internas`  TEXT           NULL COMMENT 'Notas del superadmin al revisar',
  `institucion_id`  INT UNSIGNED   NULL COMMENT 'Institución creada al aprobar',
  `revisado_por`    INT UNSIGNED   NULL,
  `revisado_en`     DATETIME       NULL,
  `ip_origen`       VARCHAR(45)    NULL,
  `created_at`      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`plan_interes`)   REFERENCES `planes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`revisado_por`)   REFERENCES `usuarios`(`id`) ON DELETE SET NULL,

  INDEX `idx_estado`   (`estado`),
  INDEX `idx_email`    (`email`),
  INDEX `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
