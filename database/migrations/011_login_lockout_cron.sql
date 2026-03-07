-- =====================================================
-- EduSaaS RD — Migración 011: Bloqueo de Login + Cron Log
-- =====================================================

USE `edusaas_rd`;

-- 1. Campos de bloqueo en tabla usuarios
ALTER TABLE `usuarios`
  ADD COLUMN `intentos_fallidos`  TINYINT UNSIGNED NOT NULL DEFAULT 0
      COMMENT 'Intentos de login fallidos consecutivos'
  AFTER `ultimo_acceso`,
  ADD COLUMN `bloqueado_hasta`    DATETIME NULL DEFAULT NULL
      COMMENT 'Bloqueado hasta esta fecha/hora (NULL = no bloqueado)'
  AFTER `intentos_fallidos`;

-- 2. Tabla de log de ejecuciones automáticas (recordatorio vencimiento)
CREATE TABLE IF NOT EXISTS `cron_log` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `tarea`       VARCHAR(80)   NOT NULL COMMENT 'Nombre de la tarea ejecutada',
  `resultado`   ENUM('ok','error','sin_trabajo') NOT NULL DEFAULT 'ok',
  `detalle`     TEXT          NULL,
  `enviados`    SMALLINT      NOT NULL DEFAULT 0,
  `errores`     SMALLINT      NOT NULL DEFAULT 0,
  `ejecutado_en`TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tarea`    (`tarea`),
  INDEX `idx_ejecutado`(`ejecutado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
