-- =====================================================
-- EduSaaS RD — Migración 007: Configuración del Sistema
-- Tabla key-value para ajustes generales del SaaS
-- =====================================================

USE `edusaas_rd`;

CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
  `clave`       VARCHAR(100)   NOT NULL PRIMARY KEY,
  `valor`       TEXT           NULL,
  `tipo`        ENUM('text','textarea','email','url','color','number','boolean','image')
                               NOT NULL DEFAULT 'text',
  `grupo`       VARCHAR(50)    NOT NULL DEFAULT 'general',
  `descripcion` VARCHAR(255)   NULL,
  `updated_at`  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores por defecto
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `tipo`, `grupo`, `descripcion`) VALUES

-- EMPRESA
('empresa_nombre',       'EduSaaS RD',                    'text',    'empresa',  'Nombre comercial que aparece en recibos y emails'),
('empresa_razon_social', 'EduSaaS RD SRL',                'text',    'empresa',  'Razón social legal'),
('empresa_rnc',          '',                              'text',    'empresa',  'RNC o número de registro fiscal'),
('empresa_telefono',     '',                              'text',    'empresa',  'Teléfono de contacto'),
('empresa_email',        'soporte@edusaas.do',            'email',   'empresa',  'Email de soporte que aparece en recibos'),
('empresa_direccion',    'Santo Domingo, República Dominicana', 'textarea', 'empresa', 'Dirección física'),
('empresa_sitio_web',    'https://edusaas.do',            'url',     'empresa',  'Sitio web oficial'),

-- MARCA
('marca_color_primario', '#1a56db',                       'color',   'marca',    'Color principal de la interfaz'),
('marca_color_acento',   '#10b981',                       'color',   'marca',    'Color de acento y éxito'),
('marca_logo_url',       '',                              'image',   'marca',    'URL del logo (sube el archivo a /public/assets/img/)'),
('marca_nombre_sistema', 'EduSaaS RD',                   'text',    'marca',    'Nombre del sistema en la barra lateral'),
('marca_slogan',         'Sistema Educativo RD 🇩🇴',      'text',    'marca',    'Slogan debajo del logo en el sidebar'),

-- FACTURACIÓN
('factura_prefijo',      'EduSaaS',                       'text',    'facturacion', 'Prefijo de los números de factura (ej: EduSaaS-2026-0001)'),
('factura_moneda',       'RD$',                           'text',    'facturacion', 'Símbolo de moneda'),
('factura_moneda_nombre','Peso Dominicano',                'text',    'facturacion', 'Nombre completo de la moneda'),
('factura_itbis',        '0',                             'number',  'facturacion', 'Porcentaje de ITBIS/IVA (0 si no aplica)'),
('factura_nota_pie',     'Gracias por su preferencia. Para consultas: soporte@edusaas.do', 'textarea', 'facturacion', 'Nota al pie de todos los recibos'),

-- SISTEMA
('sistema_dias_aviso',   '7',                             'number',  'sistema',  'Días antes del vencimiento para enviar aviso'),
('sistema_max_intentos', '5',                             'number',  'sistema',  'Intentos fallidos de login antes de bloquear'),
('sistema_modo_mantenimiento', '0',                      'boolean', 'sistema',  'Activa página de mantenimiento para colegios'),
('sistema_registro_publico',   '0',                      'boolean', 'sistema',  'Permite que colegios se registren solos (sin superadmin)')

ON DUPLICATE KEY UPDATE `valor` = `valor`; -- No sobreescribir si ya existen
