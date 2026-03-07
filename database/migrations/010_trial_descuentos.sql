-- =====================================================
-- EduSaaS RD — Migración 010: Trial + Descuentos
-- =====================================================

USE `edusaas_rd`;

-- 1. Soporte de Trial en suscripciones
ALTER TABLE `suscripciones`
  ADD COLUMN `es_trial`           TINYINT(1)    NOT NULL DEFAULT 0
      COMMENT 'Si es período de prueba gratuito'
  AFTER `tipo_facturacion`,
  ADD COLUMN `trial_dias`         SMALLINT      NULL
      COMMENT 'Días de prueba otorgados'
  AFTER `es_trial`;

-- 2. Descuentos en pagos_saas
ALTER TABLE `pagos_saas`
  ADD COLUMN `descuento_pct`      DECIMAL(5,2)  NULL DEFAULT NULL
      COMMENT 'Porcentaje de descuento aplicado'
  AFTER `monto`,
  ADD COLUMN `descuento_monto`    DECIMAL(10,2) NULL DEFAULT NULL
      COMMENT 'Monto descontado en RD$'
  AFTER `descuento_pct`,
  ADD COLUMN `monto_original`     DECIMAL(10,2) NULL DEFAULT NULL
      COMMENT 'Monto antes del descuento'
  AFTER `descuento_monto`,
  ADD COLUMN `descuento_motivo`   VARCHAR(150)  NULL DEFAULT NULL
      COMMENT 'Motivo del descuento (negociación, promoción...)'
  AFTER `monto_original`;

-- 3. Agregar tipo 'trial' al ENUM de notificaciones_email
ALTER TABLE `notificaciones_email`
  MODIFY COLUMN `tipo` ENUM(
    'vencimiento_7dias', 'vencimiento_3dias', 'vencimiento_hoy',
    'plan_vencido', 'plan_renovado', 'bienvenida', 'suspension',
    'personalizado', 'trial_expirando', 'trial_expirado'
  ) NOT NULL;

-- 4. Índice para buscar trials activos eficientemente
ALTER TABLE `suscripciones`
  ADD INDEX `idx_trial` (`es_trial`, `estado`);
