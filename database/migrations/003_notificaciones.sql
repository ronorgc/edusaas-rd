-- =====================================================
-- EduSaaS RD - Migración 003: Log de Notificaciones
-- Ejecutar después de 002_saas_facturacion.sql
-- =====================================================

USE `edusaas_rd`;

-- =====================================================
-- TABLA: notificaciones_email
-- Registro de cada correo enviado para no duplicar
-- =====================================================
CREATE TABLE `notificaciones_email` (
  `id`              INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  `institucion_id`  INT UNSIGNED   NOT NULL,
  `tipo`            ENUM(
                      'vencimiento_7dias',
                      'vencimiento_3dias',
                      'vencimiento_hoy',
                      'plan_vencido',
                      'plan_renovado',
                      'bienvenida',
                      'suspension',
                      'personalizado'
                    ) NOT NULL,
  `destinatario`    VARCHAR(150)   NOT NULL  COMMENT 'Email al que se envió',
  `asunto`          VARCHAR(255)   NOT NULL,
  `estado`          ENUM('enviado','error','pendiente') DEFAULT 'pendiente',
  `error_detalle`   TEXT           NULL      COMMENT 'Descripción del error si falló',
  `enviado_por`     INT UNSIGNED   NULL      COMMENT 'Super admin que lo disparó (NULL = automático)',
  `created_at`      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`enviado_por`)    REFERENCES `usuarios`(`id`)      ON DELETE SET NULL,
  INDEX `idx_institucion` (`institucion_id`),
  INDEX `idx_tipo`        (`tipo`),
  INDEX `idx_created`     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
