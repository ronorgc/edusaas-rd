-- =====================================================
-- Migración 005 — Preinscripciones Online
-- =====================================================

CREATE TABLE IF NOT EXISTS `preinscripciones` (
  `id`                   int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `institucion_id`       int(10) UNSIGNED NOT NULL,
  `codigo_solicitud`     varchar(20)  NOT NULL COMMENT 'PRE-2026-0001',

  -- Datos del estudiante
  `nombres`              varchar(100) NOT NULL,
  `apellidos`            varchar(100) NOT NULL,
  `fecha_nacimiento`     date         NOT NULL,
  `sexo`                 enum('M','F') NOT NULL,
  `cedula`               varchar(20)  DEFAULT NULL,
  `nie`                  varchar(20)  DEFAULT NULL,
  `lugar_nacimiento`     varchar(100) DEFAULT NULL,
  `nacionalidad`         varchar(50)  DEFAULT 'Dominicana',
  `direccion`            text         DEFAULT NULL,
  `municipio`            varchar(60)  DEFAULT NULL,
  `provincia`            varchar(60)  DEFAULT NULL,
  `telefono`             varchar(20)  DEFAULT NULL,
  `email_estudiante`     varchar(100) DEFAULT NULL,
  `tipo_sangre`          varchar(5)   DEFAULT NULL,
  `alergias`             text         DEFAULT NULL,
  `condiciones_medicas`  text         DEFAULT NULL,

  -- Grado solicitado
  `grado_id`             int(10) UNSIGNED DEFAULT NULL COMMENT 'Grado al que aspira',
  `grado_nombre`         varchar(60)  DEFAULT NULL COMMENT 'Texto libre si no selecciona',

  -- Datos del tutor
  `tutor_parentesco`     enum('padre','madre','tutor','abuelo','abuela','tio','otro') DEFAULT 'tutor',
  `tutor_nombres`        varchar(100) NOT NULL,
  `tutor_apellidos`      varchar(100) NOT NULL,
  `tutor_cedula`         varchar(20)  DEFAULT NULL,
  `tutor_telefono`       varchar(20)  NOT NULL,
  `tutor_celular`        varchar(20)  DEFAULT NULL,
  `tutor_email`          varchar(150) NOT NULL,
  `tutor_ocupacion`      varchar(100) DEFAULT NULL,
  `tutor_direccion`      text         DEFAULT NULL,

  -- Escolaridad anterior
  `viene_de_otro_colegio` tinyint(1)  DEFAULT 0,
  `colegio_anterior`      varchar(200) DEFAULT NULL,
  `ultimo_grado_aprobado` varchar(100) DEFAULT NULL,

  -- Documentos (rutas de archivos)
  `doc_foto`             varchar(255) NOT NULL  COMMENT 'Foto reciente — OBLIGATORIO',
  `doc_acta_nacimiento`  varchar(255) NOT NULL  COMMENT 'Acta de nacimiento — OBLIGATORIO',
  `doc_cedula_tutor`     varchar(255) NOT NULL  COMMENT 'Cédula del tutor — OBLIGATORIO',
  `doc_cert_medico`      varchar(255) NOT NULL  COMMENT 'Certificado médico — OBLIGATORIO',
  `doc_tarjeta_vacuna`   varchar(255) NOT NULL  COMMENT 'Tarjeta de vacunación — OBLIGATORIO',
  `doc_notas_anteriores` varchar(255) DEFAULT NULL COMMENT 'Notas del colegio anterior',
  `doc_carta_saldo`      varchar(255) DEFAULT NULL COMMENT 'Carta de saldo',
  `doc_sigerd`           varchar(255) DEFAULT NULL COMMENT 'SIGERD / MINERD',

  -- Estado del proceso
  `estado`               enum('pendiente','en_revision','aprobada','rechazada','convertida') DEFAULT 'pendiente',
  `notas_admin`          text         DEFAULT NULL COMMENT 'Observaciones del revisor',
  `revisado_por`         int(10) UNSIGNED DEFAULT NULL,
  `fecha_revision`       timestamp    NULL DEFAULT NULL,
  `estudiante_id`        int(10) UNSIGNED DEFAULT NULL COMMENT 'Enlace al estudiante creado',

  `ip_origen`            varchar(45)  DEFAULT NULL,
  `created_at`           timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`           timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`codigo_solicitud`),
  KEY `idx_institucion`  (`institucion_id`),
  KEY `idx_estado`       (`estado`),
  KEY `idx_created`      (`created_at`),
  KEY `revisado_por`     (`revisado_por`),
  KEY `estudiante_id`    (`estudiante_id`),
  KEY `grado_id`         (`grado_id`),

  CONSTRAINT `preinscripciones_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `preinscripciones_ibfk_2` FOREIGN KEY (`revisado_por`)   REFERENCES `usuarios`      (`id`) ON DELETE SET NULL,
  CONSTRAINT `preinscripciones_ibfk_3` FOREIGN KEY (`estudiante_id`)  REFERENCES `estudiantes`   (`id`) ON DELETE SET NULL,
  CONSTRAINT `preinscripciones_ibfk_4` FOREIGN KEY (`grado_id`)       REFERENCES `grados`        (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Secuencia para códigos de preinscripción
CREATE TABLE IF NOT EXISTS `secuencias_preinscripcion` (
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `ultimo_numero`  int(10) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`institucion_id`),
  CONSTRAINT `secuencias_preinscripcion_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
