-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-03-2026 a las 02:40:32
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `edusaas_rd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anos_escolares`
--

CREATE TABLE `anos_escolares` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Ej: 2024-2025',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL COMMENT 'Color hexadecimal para UI',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','ausente','tardanza','justificada') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `calificacion` decimal(5,2) NOT NULL COMMENT 'Escala 0-100',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Profesor que registró',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--

CREATE TABLE `comunicados` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `tipo` enum('general','curso','individual') NOT NULL,
  `prioridad` enum('normal','alta','urgente') DEFAULT 'normal',
  `enviado_por` int(10) UNSIGNED NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicado_destinatarios`
--

CREATE TABLE `comunicado_destinatarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `comunicado_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_lectura` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos_pago`
--

CREATE TABLE `conceptos_pago` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Matrícula, Mensualidad, Uniforme',
  `descripcion` text DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `recurrente` tinyint(1) DEFAULT 0 COMMENT 'Si es mensual',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('text','textarea','email','url','color','number','boolean','image') NOT NULL DEFAULT 'text',
  `grupo` varchar(50) NOT NULL DEFAULT 'general',
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`clave`, `valor`, `tipo`, `grupo`, `descripcion`, `updated_at`) VALUES
('empresa_direccion', 'Santo Domingo, República Dominicana', 'textarea', 'empresa', 'Dirección física', '2026-03-06 10:38:29'),
('empresa_email', 'soporte@edusaas.do', 'email', 'empresa', 'Email de soporte que aparece en recibos', '2026-03-06 10:38:29'),
('empresa_nombre', 'EduSaaS RD', 'text', 'empresa', 'Nombre comercial que aparece en recibos y emails', '2026-03-06 10:38:29'),
('empresa_razon_social', 'EduSaaS RD SRL', 'text', 'empresa', 'Razón social legal', '2026-03-06 10:38:29'),
('empresa_rnc', '', 'text', 'empresa', 'RNC o número de registro fiscal', '2026-03-06 10:38:29'),
('empresa_sitio_web', 'https://edusaas.do', 'url', 'empresa', 'Sitio web oficial', '2026-03-06 10:38:29'),
('empresa_telefono', '', 'text', 'empresa', 'Teléfono de contacto', '2026-03-06 10:38:29'),
('factura_itbis', '18', 'number', 'facturacion', 'Porcentaje de ITBIS/IVA (0 si no aplica)', '2026-03-06 11:11:59'),
('factura_moneda', 'RD$', 'text', 'facturacion', 'Símbolo de moneda', '2026-03-06 10:38:29'),
('factura_moneda_nombre', 'Peso Dominicano', 'text', 'facturacion', 'Nombre completo de la moneda', '2026-03-06 10:38:29'),
('factura_nota_pie', 'Gracias por su preferencia. Para consultas: soporte@edusaas.do', 'textarea', 'facturacion', 'Nota al pie de todos los recibos', '2026-03-06 10:38:29'),
('factura_prefijo', 'EduSaaS', 'text', 'facturacion', 'Prefijo de los números de factura (ej: EduSaaS-2026-0001)', '2026-03-06 10:38:29'),
('marca_color_acento', '#10b981', 'color', 'marca', 'Color de acento y éxito', '2026-03-06 10:38:29'),
('marca_color_primario', '#1a56db', 'color', 'marca', 'Color principal de la interfaz', '2026-03-06 10:38:29'),
('marca_logo_url', '', 'image', 'marca', 'URL del logo (sube el archivo a /public/assets/img/)', '2026-03-06 10:38:29'),
('marca_nombre_sistema', 'EduSaaS RD', 'text', 'marca', 'Nombre del sistema en la barra lateral', '2026-03-06 10:38:29'),
('marca_slogan', 'Sistema Educativo RD 🇩🇴', 'text', 'marca', 'Slogan debajo del logo en el sidebar', '2026-03-06 10:38:29'),
('sistema_dias_aviso', '7', 'number', 'sistema', 'Días antes del vencimiento para enviar aviso', '2026-03-06 10:38:29'),
('sistema_max_intentos', '5', 'number', 'sistema', 'Intentos fallidos de login antes de bloquear', '2026-03-06 10:38:29'),
('sistema_modo_mantenimiento', '0', 'boolean', 'sistema', 'Activa página de mantenimiento para colegios', '2026-03-06 20:21:23'),
('sistema_registro_publico', '1', 'boolean', 'sistema', 'Permite que colegios se registren solos (sin superadmin)', '2026-03-06 22:44:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cron_log`
--

CREATE TABLE `cron_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `tarea` varchar(80) NOT NULL COMMENT 'Nombre de la tarea ejecutada',
  `resultado` enum('ok','error','sin_trabajo') NOT NULL DEFAULT 'ok',
  `detalle` text DEFAULT NULL,
  `enviados` smallint(6) NOT NULL DEFAULT 0,
  `errores` smallint(6) NOT NULL DEFAULT 0,
  `ejecutado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cron_log`
--

INSERT INTO `cron_log` (`id`, `tarea`, `resultado`, `detalle`, `enviados`, `errores`, `ejecutado_en`) VALUES
(1, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 16:47:00'),
(2, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 18:54:18'),
(3, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 20:07:15'),
(4, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 20:13:26'),
(5, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 20:15:28'),
(6, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 20:21:48'),
(7, 'avisos_vencimiento', 'sin_trabajo', NULL, 0, 0, '2026-03-06 22:37:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuotas`
--

CREATE TABLE `cuotas` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `concepto_pago_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `mes` int(11) DEFAULT NULL COMMENT 'Mes para pagos recurrentes (1-12)',
  `ano` int(11) DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagada','vencida','cancelada') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Referencia al usuario del estudiante',
  `codigo_estudiante` varchar(20) NOT NULL COMMENT 'Código único del estudiante',
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('M','F') NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `nie` varchar(20) DEFAULT NULL,
  `lugar_nacimiento` varchar(100) DEFAULT NULL,
  `nacionalidad` varchar(50) DEFAULT 'Dominicana',
  `direccion` text DEFAULT NULL,
  `municipio` varchar(60) DEFAULT NULL,
  `provincia` varchar(60) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tipo_sangre` varchar(5) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `condiciones_medicas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_padre`
--

CREATE TABLE `estudiante_padre` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `padre_id` int(10) UNSIGNED NOT NULL,
  `es_principal` tinyint(1) DEFAULT 0 COMMENT 'Contacto principal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados`
--

CREATE TABLE `grados` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL COMMENT 'Ej: 1er Grado, 2do Bachillerato',
  `nivel` enum('inicial','primario','secundario') NOT NULL DEFAULT 'primario',
  `orden` tinyint(4) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `seccion_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instituciones`
--

CREATE TABLE `instituciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `tipo` enum('publico','privado') NOT NULL,
  `codigo_minerd` varchar(50) DEFAULT NULL COMMENT 'Código oficial MINERD',
  `rnc` varchar(20) DEFAULT NULL COMMENT 'RNC para instituciones privadas',
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `subdomain` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL COMMENT 'Notas internas del super admin',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `instituciones`
--

INSERT INTO `instituciones` (`id`, `nombre`, `tipo`, `codigo_minerd`, `rnc`, `telefono`, `email`, `direccion`, `municipio`, `provincia`, `logo`, `subdomain`, `notas`, `activo`, `fecha_registro`, `created_at`, `updated_at`) VALUES
(2, 'Colegio Demo 155', 'publico', '00000', '', '809-500-0000', 'ronerborg@gmail.com', 'Carmen Renata IIIV', 'DN', 'Distrito Nacional', NULL, 'demo', NULL, 1, '2026-03-05 22:31:31', '2026-03-05 22:31:31', '2026-03-07 01:03:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_actividad`
--

CREATE TABLE `log_actividad` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modulo` varchar(50) NOT NULL COMMENT 'instituciones, planes, cobros, usuarios, config, preregistros...',
  `accion` varchar(80) NOT NULL COMMENT 'crear, editar, eliminar, aprobar, suspender...',
  `descripcion` varchar(255) NOT NULL COMMENT 'Texto legible del evento',
  `detalle` text DEFAULT NULL COMMENT 'JSON con datos adicionales (antes/después)',
  `entidad_tipo` varchar(50) DEFAULT NULL COMMENT 'Tipo de entidad afectada (institucion, plan, usuario...)',
  `entidad_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID de la entidad afectada',
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Quién hizo la acción',
  `usuario_nombre` varchar(150) DEFAULT NULL COMMENT 'Nombre snapshot (por si se borra el usuario)',
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `log_actividad`
--

INSERT INTO `log_actividad` (`id`, `modulo`, `accion`, `descripcion`, `detalle`, `entidad_tipo`, `entidad_id`, `usuario_id`, `usuario_nombre`, `ip`, `created_at`) VALUES
(1, 'instituciones', 'suspender', 'Suspendida: uno', NULL, 'institucion', 3, 1, NULL, '::1', '2026-03-06 15:45:03'),
(2, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 16:47:00'),
(3, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 16:48:08'),
(4, 'instituciones', 'reactivar', 'Reactivada: ', NULL, 'institucion', 3, 1, NULL, '::1', '2026-03-06 18:51:07'),
(5, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 18:54:18'),
(6, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 18:55:50'),
(7, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 18:55:50'),
(8, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 19:41:20'),
(9, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 19:41:20'),
(10, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:07:14'),
(11, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:07:15'),
(12, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:13:25'),
(13, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:13:26'),
(14, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:15:27'),
(15, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:15:28'),
(16, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:21:48'),
(17, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, NULL, '::1', '2026-03-06 20:21:48'),
(18, 'usuarios', 'bloqueo_login', 'Cuenta bloqueada por intentos: superadmin', NULL, 'usuario', 1, NULL, NULL, '::1', '2026-03-06 21:23:18'),
(19, 'usuarios', 'desbloquear', 'Usuario desbloqueado manualmente: demo_admin', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 21:26:14'),
(20, 'instituciones', 'crear', 'Institución creada: FE', NULL, 'institucion', 4, 1, 'Super Administrador', '::1', '2026-03-06 21:33:58'),
(21, 'instituciones', 'crear', 'Institución creada: sd', NULL, 'institucion', 5, 1, 'Super Administrador', '::1', '2026-03-06 21:36:07'),
(22, 'instituciones', 'suspender', 'Suspendida: uno', NULL, 'institucion', 3, 1, 'Super Administrador', '::1', '2026-03-06 21:48:24'),
(23, 'instituciones', 'reactivar', 'Reactivada: uno', NULL, 'institucion', 3, 1, 'Super Administrador', '::1', '2026-03-06 21:49:52'),
(24, 'instituciones', 'cambiar_plan', 'Plan cambiado a Profesional: uno', '{\"plan\":\"Profesional\"}', 'institucion', 3, 1, 'Super Administrador', '::1', '2026-03-06 21:51:16'),
(25, 'instituciones', 'cambiar_plan', 'Plan cambiado a Básico: uno', '{\"plan\":\"Básico\"}', 'institucion', 3, 1, 'Super Administrador', '::1', '2026-03-06 21:52:14'),
(26, 'instituciones', 'exportar', 'Export CSV de instituciones (4 registros)', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 21:56:14'),
(27, 'instituciones', 'eliminar', 'Eliminada: sd', NULL, 'institucion', 5, 1, 'Super Administrador', '::1', '2026-03-06 21:58:53'),
(28, 'instituciones', 'eliminar', 'Eliminada: FE', NULL, 'institucion', 4, 1, 'Super Administrador', '::1', '2026-03-06 21:58:58'),
(29, 'usuarios', 'crear', 'Super admin creado: ccccccc mm', NULL, 'usuario', NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:23:00'),
(30, 'usuarios', 'eliminar', 'Super admin eliminado: ccccccc mm', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:26:48'),
(31, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:33:14'),
(32, 'notificaciones', 'cron_avisos', 'Avisos vencimiento: 0 enviados, 0 errores', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:37:39'),
(33, 'configuracion', 'editar', 'Configuración del sistema actualizada', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:44:55'),
(34, 'instituciones', 'exportar', 'Export CSV de instituciones (2 registros)', NULL, NULL, NULL, 1, 'Super Administrador', '::1', '2026-03-06 22:58:44'),
(35, 'preregistros', 'aprobar', 'Preregistro aprobado: FE', '{\"plan_id\":3}', 'institucion', 6, 1, 'Super Administrador', '::1', '2026-03-06 23:02:30'),
(36, 'instituciones', 'suspender', 'Suspendida: Colegio Demo 1', NULL, 'institucion', 2, 1, 'Super Administrador', '::1', '2026-03-06 23:46:21'),
(37, 'instituciones', 'reactivar', 'Reactivada: Colegio Demo 1', NULL, 'institucion', 2, 1, 'Super Administrador', '::1', '2026-03-06 23:46:37'),
(38, 'instituciones', 'editar', 'Editada: Colegio Demo 155', NULL, 'institucion', 2, 1, 'Super Administrador', '::1', '2026-03-07 01:01:13'),
(39, 'instituciones', 'editar', 'Editada: Colegio Demo 155', NULL, 'institucion', 2, 1, 'Super Administrador', '::1', '2026-03-07 01:04:44'),
(40, 'instituciones', 'eliminar', 'Eliminada: uno', NULL, 'institucion', 3, 1, 'Super Administrador', '::1', '2026-03-07 01:06:15'),
(41, 'instituciones', 'eliminar', 'Eliminada: FE', NULL, 'institucion', 6, 1, 'Super Administrador', '::1', '2026-03-07 01:06:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_estado_instituciones`
--

CREATE TABLE `log_estado_instituciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `accion` enum('activada','suspendida','cancelada','reactivada','plan_cambiado') NOT NULL,
  `motivo` text DEFAULT NULL,
  `realizado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `log_estado_instituciones`
--

INSERT INTO `log_estado_instituciones` (`id`, `institucion_id`, `accion`, `motivo`, `realizado_por`, `created_at`) VALUES
(1, 2, 'suspendida', 'm', 1, '2026-03-05 23:18:43'),
(2, 2, 'suspendida', 'm', 1, '2026-03-05 23:18:48'),
(3, 2, 'reactivada', 'Reactivada manualmente', 1, '2026-03-05 23:19:13'),
(4, 2, 'plan_cambiado', 'Datos editados por super admin', 1, '2026-03-06 01:30:56'),
(5, 2, 'plan_cambiado', 'Plan cambiado a Básico — m', 1, '2026-03-06 01:32:04'),
(6, 2, 'plan_cambiado', 'Plan cambiado a Profesional — k', 1, '2026-03-06 11:10:04'),
(12, 2, 'plan_cambiado', 'Datos editados por super admin', 1, '2026-03-06 21:46:21'),
(18, 2, 'suspendida', 'n', 1, '2026-03-06 23:46:20'),
(19, 2, 'reactivada', 'Reactivada manualmente', 1, '2026-03-06 23:46:36'),
(20, 2, 'plan_cambiado', 'Datos editados por super admin', 1, '2026-03-07 01:01:13'),
(21, 2, 'plan_cambiado', 'Datos editados por super admin', 1, '2026-03-07 01:03:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `ano_escolar_id` int(10) UNSIGNED NOT NULL,
  `seccion_id` int(10) UNSIGNED NOT NULL,
  `fecha_matricula` date NOT NULL,
  `numero_matricula` varchar(20) NOT NULL,
  `estado` enum('activa','retirada','graduado','trasladado') DEFAULT 'activa',
  `repitente` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles`
--

CREATE TABLE `niveles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Inicial, Primaria, Secundaria',
  `descripcion` varchar(200) DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `niveles`
--

INSERT INTO `niveles` (`id`, `nombre`, `descripcion`, `orden`) VALUES
(1, 'Inicial', 'Nivel Inicial (3-5 años)', 1),
(2, 'Primaria', 'Nivel Primario (1ro-6to)', 2),
(3, 'Secundaria', 'Nivel Secundario (1ro-6to)', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_email`
--

CREATE TABLE `notificaciones_email` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('vencimiento_7dias','vencimiento_3dias','vencimiento_hoy','plan_vencido','plan_renovado','bienvenida','suspension','personalizado','trial_expirando','trial_expirado') NOT NULL,
  `destinatario` varchar(150) NOT NULL COMMENT 'Email al que se envió',
  `asunto` varchar(255) NOT NULL,
  `estado` enum('enviado','error','pendiente') DEFAULT 'pendiente',
  `error_detalle` text DEFAULT NULL COMMENT 'Descripción del error si falló',
  `enviado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Super admin que lo disparó (NULL = automático)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones_email`
--

INSERT INTO `notificaciones_email` (`id`, `institucion_id`, `tipo`, `destinatario`, `asunto`, `estado`, `error_detalle`, `enviado_por`, `created_at`) VALUES
(2, 2, 'bienvenida', 'ronerborg@gmail.com', '¡Bienvenido a EduSaaS RD! — Tus credenciales de acceso', 'enviado', NULL, 1, '2026-03-05 22:31:34'),
(3, 2, 'suspension', 'ronerborg@gmail.com', '🔒 Acceso suspendido — Colegio Demo', 'enviado', NULL, 1, '2026-03-05 23:18:48'),
(4, 2, 'suspension', 'ronerborg@gmail.com', '🔒 Acceso suspendido — Colegio Demo', 'enviado', NULL, 1, '2026-03-05 23:18:50'),
(5, 2, 'personalizado', 'ronerborg@gmail.com', 'hola', 'enviado', NULL, 1, '2026-03-06 01:26:05'),
(6, 2, 'plan_renovado', 'ronerborg@gmail.com', '✅ Renovación de suscripción EduSaaS-2026-0002', 'enviado', NULL, 1, '2026-03-06 11:08:18'),
(11, 2, 'plan_renovado', 'ronerjborg@gmail.com', '✅ Confirmación de pago EduSaaS-2026-0003', 'enviado', NULL, 1, '2026-03-06 22:04:19'),
(12, 2, 'plan_renovado', 'ronerjborg@gmail.com', '✅ Confirmación de pago EduSaaS-2026-0004', 'enviado', NULL, 1, '2026-03-06 22:06:12'),
(15, 2, 'personalizado', 'ronerjborg@gmail.com', 'holad', 'enviado', NULL, 1, '2026-03-06 22:18:48'),
(16, 2, 'suspension', 'ronerjborg@gmail.com', '🔒 Acceso suspendido — Colegio Demo 1', 'enviado', NULL, 1, '2026-03-06 23:46:21'),
(17, 2, 'personalizado', 'ronerjborg@gmail.com', '✅ Acceso reactivado — Colegio Demo 1', 'enviado', NULL, 1, '2026-03-06 23:46:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `parentesco` varchar(50) NOT NULL COMMENT 'Padre, Madre, Tutor',
  `telefono` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `lugar_trabajo` varchar(200) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `cuota_id` int(10) UNSIGNED NOT NULL,
  `numero_recibo` varchar(50) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','cheque','tarjeta') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL COMMENT 'Número de transferencia o cheque',
  `recibido_por` int(10) UNSIGNED DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_saas`
--

CREATE TABLE `pagos_saas` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `suscripcion_id` int(10) UNSIGNED NOT NULL,
  `numero_factura` varchar(20) NOT NULL COMMENT 'EduSaaS-2026-0001',
  `monto` decimal(10,2) NOT NULL,
  `descuento_pct` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje de descuento aplicado',
  `descuento_monto` decimal(10,2) DEFAULT NULL COMMENT 'Monto descontado en RD$',
  `monto_original` decimal(10,2) DEFAULT NULL COMMENT 'Monto antes del descuento',
  `descuento_motivo` varchar(150) DEFAULT NULL COMMENT 'Motivo del descuento (negociación, promoción...)',
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('transferencia','efectivo','tarjeta','cheque') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL COMMENT 'Número de transferencia, etc.',
  `periodo_desde` date NOT NULL COMMENT 'Inicio del período que cubre este pago',
  `periodo_hasta` date NOT NULL COMMENT 'Fin del período que cubre este pago',
  `estado` enum('confirmado','pendiente','anulado') DEFAULT 'confirmado',
  `notas` text DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_saas`
--

INSERT INTO `pagos_saas` (`id`, `institucion_id`, `suscripcion_id`, `numero_factura`, `monto`, `descuento_pct`, `descuento_monto`, `monto_original`, `descuento_motivo`, `fecha_pago`, `metodo_pago`, `referencia`, `periodo_desde`, `periodo_hasta`, `estado`, `notas`, `registrado_por`, `created_at`) VALUES
(2, 2, 2, 'EduSaaS-2026-0001', 3500.00, NULL, NULL, NULL, NULL, '2026-03-05', 'transferencia', '4500', '2026-03-05', '2026-04-05', 'confirmado', NULL, 1, '2026-03-05 22:31:31'),
(3, 2, 3, 'EduSaaS-2026-0002', 1500.00, NULL, NULL, NULL, NULL, '2026-03-06', 'transferencia', NULL, '2026-03-06', '2026-04-06', 'confirmado', 'Renovación masiva', 1, '2026-03-06 11:08:16'),
(5, 2, 7, 'EduSaaS-2026-0003', 3500.00, NULL, NULL, NULL, NULL, '2026-03-06', 'transferencia', '0002l', '2026-03-06', '2026-04-06', 'confirmado', 'k', 1, '2026-03-06 22:04:16'),
(6, 2, 8, 'EduSaaS-2026-0004', 3500.00, NULL, NULL, NULL, NULL, '2026-03-06', 'transferencia', 'lo', '2026-03-06', '2026-04-06', 'confirmado', 'lo', 1, '2026-03-06 22:06:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id` int(10) UNSIGNED NOT NULL,
  `ano_escolar_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT '1er Período, 2do Período, etc.',
  `numero` int(11) NOT NULL COMMENT '1, 2, 3, 4, 5',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Básico, Profesional, Premium',
  `descripcion` text DEFAULT NULL,
  `precio_mensual` decimal(10,2) NOT NULL,
  `precio_anual` decimal(10,2) NOT NULL COMMENT 'Precio con descuento anual',
  `max_estudiantes` int(11) NOT NULL COMMENT '0 = ilimitado',
  `max_profesores` int(11) NOT NULL COMMENT '0 = ilimitado',
  `max_secciones` int(11) NOT NULL COMMENT '0 = ilimitado',
  `incluye_pagos` tinyint(1) DEFAULT 0 COMMENT 'Módulo de pagos y cuotas',
  `incluye_reportes` tinyint(1) DEFAULT 0 COMMENT 'Reportes avanzados PDF/Excel',
  `incluye_comunicados` tinyint(1) DEFAULT 1 COMMENT 'Comunicados a padres',
  `incluye_api` tinyint(1) DEFAULT 0 COMMENT 'Acceso a API REST',
  `color` varchar(7) DEFAULT '#1a56db' COMMENT 'Color para UI',
  `icono` varchar(50) DEFAULT 'bi-box' COMMENT 'Bootstrap icon',
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre`, `descripcion`, `precio_mensual`, `precio_anual`, `max_estudiantes`, `max_profesores`, `max_secciones`, `incluye_pagos`, `incluye_reportes`, `incluye_comunicados`, `incluye_api`, `color`, `icono`, `orden`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Básico', 'Ideal para centros educativos pequeños que inician su digitalización.', 1500.00, 15000.00, 150, 15, 6, 0, 0, 1, 0, '#64748b', 'bi-box', 1, 1, '2026-03-04 03:33:34', '2026-03-06 02:24:53'),
(2, 'Profesional', 'Para colegios medianos con necesidades de gestión completa.', 3500.00, 35000.00, 500, 40, 20, 1, 1, 1, 0, '#1a56db', 'bi-briefcase-fill', 2, 1, '2026-03-04 03:33:34', '2026-03-04 03:33:34'),
(3, 'Premium', 'Sin límites. Para instituciones grandes con múltiples secciones.', 7000.00, 70000.00, 0, 0, 0, 1, 1, 1, 1, '#f59e0b', 'bi-star-fill', 3, 1, '2026-03-04 03:33:34', '2026-03-06 22:14:54'),
(4, 'Caja', 'Plan para gestionar pagos', 500.00, 5600.00, 0, 0, 0, 1, 0, 0, 0, '#1a56db', 'bi-rocket-takeoff', 4, 1, '2026-03-06 02:27:01', '2026-03-06 02:27:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preinscripciones`
--

CREATE TABLE `preinscripciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `codigo_solicitud` varchar(20) NOT NULL COMMENT 'PRE-2026-0001',
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('M','F') NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `nie` varchar(20) DEFAULT NULL,
  `lugar_nacimiento` varchar(100) DEFAULT NULL,
  `nacionalidad` varchar(50) DEFAULT 'Dominicana',
  `direccion` text DEFAULT NULL,
  `municipio` varchar(60) DEFAULT NULL,
  `provincia` varchar(60) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email_estudiante` varchar(100) DEFAULT NULL,
  `tipo_sangre` varchar(5) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `condiciones_medicas` text DEFAULT NULL,
  `grado_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Grado al que aspira',
  `grado_nombre` varchar(60) DEFAULT NULL COMMENT 'Texto libre si no selecciona',
  `tutor_parentesco` enum('padre','madre','tutor','abuelo','abuela','tio','otro') DEFAULT 'tutor',
  `tutor_nombres` varchar(100) NOT NULL,
  `tutor_apellidos` varchar(100) NOT NULL,
  `tutor_cedula` varchar(20) DEFAULT NULL,
  `tutor_telefono` varchar(20) NOT NULL,
  `tutor_celular` varchar(20) DEFAULT NULL,
  `tutor_email` varchar(150) NOT NULL,
  `tutor_ocupacion` varchar(100) DEFAULT NULL,
  `tutor_direccion` text DEFAULT NULL,
  `viene_de_otro_colegio` tinyint(1) DEFAULT 0,
  `colegio_anterior` varchar(200) DEFAULT NULL,
  `ultimo_grado_aprobado` varchar(100) DEFAULT NULL,
  `doc_foto` varchar(255) NOT NULL COMMENT 'Foto reciente — OBLIGATORIO',
  `doc_acta_nacimiento` varchar(255) NOT NULL COMMENT 'Acta de nacimiento — OBLIGATORIO',
  `doc_cedula_tutor` varchar(255) NOT NULL COMMENT 'Cédula del tutor — OBLIGATORIO',
  `doc_cert_medico` varchar(255) NOT NULL COMMENT 'Certificado médico — OBLIGATORIO',
  `doc_tarjeta_vacuna` varchar(255) NOT NULL COMMENT 'Tarjeta de vacunación — OBLIGATORIO',
  `doc_notas_anteriores` varchar(255) DEFAULT NULL COMMENT 'Notas del colegio anterior',
  `doc_carta_saldo` varchar(255) DEFAULT NULL COMMENT 'Carta de saldo',
  `doc_sigerd` varchar(255) DEFAULT NULL COMMENT 'SIGERD / MINERD',
  `estado` enum('pendiente','en_revision','aprobada','rechazada','convertida') DEFAULT 'pendiente',
  `notas_admin` text DEFAULT NULL COMMENT 'Observaciones del revisor',
  `revisado_por` int(10) UNSIGNED DEFAULT NULL,
  `fecha_revision` timestamp NULL DEFAULT NULL,
  `estudiante_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Enlace al estudiante creado',
  `ip_origen` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preregistro_colegios`
--

CREATE TABLE `preregistro_colegios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('publico','privado') NOT NULL DEFAULT 'privado',
  `email` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `codigo_minerd` varchar(20) DEFAULT NULL,
  `nombre_director` varchar(150) DEFAULT NULL COMMENT 'Nombre del director o contacto principal',
  `cargo_contacto` varchar(80) DEFAULT NULL COMMENT 'Cargo del contacto (Director, Administrador...)',
  `cant_estudiantes` smallint(6) DEFAULT NULL COMMENT 'Estimado de estudiantes',
  `mensaje` text DEFAULT NULL COMMENT 'Mensaje libre del solicitante',
  `plan_interes` int(10) UNSIGNED DEFAULT NULL COMMENT 'Plan que les interesa (FK planes)',
  `estado` enum('pendiente','aprobado','rechazado','contactado') NOT NULL DEFAULT 'pendiente',
  `notas_internas` text DEFAULT NULL COMMENT 'Notas del superadmin al revisar',
  `institucion_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Institución creada al aprobar',
  `revisado_por` int(10) UNSIGNED DEFAULT NULL,
  `revisado_en` datetime DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preregistro_colegios`
--

INSERT INTO `preregistro_colegios` (`id`, `nombre`, `tipo`, `email`, `telefono`, `municipio`, `provincia`, `codigo_minerd`, `nombre_director`, `cargo_contacto`, `cant_estudiantes`, `mensaje`, `plan_interes`, `estado`, `notas_internas`, `institucion_id`, `revisado_por`, `revisado_en`, `ip_origen`, `created_at`, `updated_at`) VALUES
(1, 'uno', 'privado', 'juan@director.com', '809-500-0001', 'DN', NULL, '00000', 'juan', 'director', 150, 'df', 1, 'aprobado', NULL, NULL, 1, '2026-03-06 11:05:01', '::1', '2026-03-06 14:31:16', '2026-03-06 15:05:01'),
(2, 'FE', 'privado', 'ronerjborg@gmail.com', '8298560000', 'DN', NULL, 'aaa', 'lolo ko', 'director', 500, 'm', 3, 'aprobado', 'm', NULL, 1, '2026-03-06 19:02:29', '::1', '2026-03-06 23:00:22', '2026-03-06 23:02:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `codigo_profesor` varchar(20) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `titulo_academico` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Permisos específicos del rol' CHECK (json_valid(`permisos`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `permisos`, `created_at`) VALUES
(1, 'super_admin', 'Administrador global del sistema', NULL, '2026-03-04 01:20:56'),
(2, 'admin', 'Administrador de institución', NULL, '2026-03-04 01:20:56'),
(3, 'profesor', 'Docente', NULL, '2026-03-04 01:20:56'),
(4, 'padre', 'Padre o tutor', NULL, '2026-03-04 01:20:56'),
(5, 'estudiante', 'Estudiante', NULL, '2026-03-04 01:20:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

CREATE TABLE `secciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `ano_escolar_id` int(10) UNSIGNED NOT NULL,
  `grado_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(10) NOT NULL COMMENT 'A, B, C',
  `capacidad` smallint(6) DEFAULT 40,
  `activo` tinyint(1) DEFAULT 1,
  `capacidad_maxima` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion_asignatura`
--

CREATE TABLE `seccion_asignatura` (
  `id` int(10) UNSIGNED NOT NULL,
  `seccion_id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` int(10) UNSIGNED NOT NULL,
  `profesor_id` int(10) UNSIGNED NOT NULL,
  `horas_semanales` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secuencias_codigo`
--

CREATE TABLE `secuencias_codigo` (
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `ultimo_numero` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secuencias_preinscripcion`
--

CREATE TABLE `secuencias_preinscripcion` (
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `ultimo_numero` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `tipo_facturacion` enum('mensual','anual') NOT NULL DEFAULT 'mensual',
  `es_trial` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si es período de prueba gratuito',
  `trial_dias` smallint(6) DEFAULT NULL COMMENT 'Días de prueba otorgados',
  `monto` decimal(10,2) NOT NULL COMMENT 'Monto acordado (puede diferir del plan)',
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('activa','vencida','suspendida','cancelada') DEFAULT 'activa',
  `renovacion_auto` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL COMMENT 'Notas internas del super admin',
  `creado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Super admin que creó la suscripción',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `institucion_id`, `plan_id`, `tipo_facturacion`, `es_trial`, `trial_dias`, `monto`, `fecha_inicio`, `fecha_vencimiento`, `estado`, `renovacion_auto`, `notas`, `creado_por`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 'mensual', 0, NULL, 1500.00, '2026-03-05', '2026-04-05', 'cancelada', 0, NULL, 1, '2026-03-05 22:31:31', '2026-03-06 11:08:16'),
(3, 2, 2, 'mensual', 0, NULL, 3500.00, '2026-03-06', '2026-04-06', 'cancelada', 0, 'Renovación masiva', 1, '2026-03-06 11:08:16', '2026-03-06 22:04:16'),
(7, 2, 2, 'mensual', 0, NULL, 3500.00, '2026-03-06', '2026-04-06', 'cancelada', 0, 'k', 1, '2026-03-06 22:04:16', '2026-03-06 22:06:10'),
(8, 2, 2, 'mensual', 0, NULL, 3500.00, '2026-03-06', '2026-04-06', 'activa', 0, 'lo', 1, '2026-03-06 22:06:10', '2026-03-06 23:46:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutores`
--

CREATE TABLE `tutores` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `parentesco` enum('padre','madre','tutor','abuelo','abuela','tio','otro') NOT NULL DEFAULT 'tutor',
  `nombres` varchar(80) NOT NULL,
  `apellidos` varchar(80) NOT NULL,
  `cedula` varchar(15) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono_trabajo` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ocupacion` varchar(80) DEFAULT NULL,
  `es_responsable` tinyint(1) DEFAULT 0 COMMENT '1 = responsable de pagos',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `institucion_id` int(10) UNSIGNED DEFAULT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `intentos_fallidos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Intentos de login fallidos consecutivos',
  `bloqueado_hasta` datetime DEFAULT NULL COMMENT 'Bloqueado hasta esta fecha/hora (NULL = no bloqueado)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `institucion_id`, `rol_id`, `username`, `email`, `password`, `nombres`, `apellidos`, `cedula`, `telefono`, `foto`, `activo`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'superadmin', 'admin@edusaas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Administrador', NULL, NULL, NULL, 1, '2026-03-07 01:39:43', 0, NULL, '2026-03-04 01:21:00', '2026-03-07 01:39:43'),
(6, 2, 2, 'demo_admin', 'ronerborg@gmail.com', '$2y$10$7OdH8MqWXxoBvvQQsyJhKu5UblZ524IAAw0qNDUYRFUW345FRIQei', 'Administrador', 'Colegio Demo', NULL, NULL, NULL, 1, '2026-03-06 19:41:29', 0, NULL, '2026-03-05 22:31:31', '2026-03-06 21:26:14');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `anos_escolares`
--
ALTER TABLE `anos_escolares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institucion_activo` (`institucion_id`,`activo`),
  ADD KEY `idx_inst` (`institucion_id`);

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institucion` (`institucion_id`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asistencia` (`matricula_id`,`fecha`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_calificacion` (`matricula_id`,`asignatura_id`,`periodo_id`),
  ADD KEY `asignatura_id` (`asignatura_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_matricula` (`matricula_id`),
  ADD KEY `idx_periodo` (`periodo_id`);

--
-- Indices de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_fecha` (`fecha_envio`);

--
-- Indices de la tabla `comunicado_destinatarios`
--
ALTER TABLE `comunicado_destinatarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_destinatario` (`comunicado_id`,`usuario_id`),
  ADD KEY `idx_usuario_leido` (`usuario_id`,`leido`);

--
-- Indices de la tabla `conceptos_pago`
--
ALTER TABLE `conceptos_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institucion` (`institucion_id`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `cron_log`
--
ALTER TABLE `cron_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tarea` (`tarea`),
  ADD KEY `idx_ejecutado` (`ejecutado_en`);

--
-- Indices de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `institucion_id` (`institucion_id`),
  ADD KEY `concepto_pago_id` (`concepto_pago_id`),
  ADD KEY `idx_matricula` (`matricula_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_vencimiento` (`fecha_vencimiento`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_codigo` (`institucion_id`,`codigo_estudiante`),
  ADD UNIQUE KEY `uq_cedula_inst` (`institucion_id`,`cedula`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_inst` (`institucion_id`),
  ADD KEY `idx_nombres` (`apellidos`,`nombres`);

--
-- Indices de la tabla `estudiante_padre`
--
ALTER TABLE `estudiante_padre`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_relacion` (`estudiante_id`,`padre_id`),
  ADD KEY `padre_id` (`padre_id`);

--
-- Indices de la tabla `grados`
--
ALTER TABLE `grados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inst` (`institucion_id`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asignatura_id` (`asignatura_id`),
  ADD KEY `idx_seccion` (`seccion_id`),
  ADD KEY `idx_profesor` (`profesor_id`);

--
-- Indices de la tabla `instituciones`
--
ALTER TABLE `instituciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subdomain` (`subdomain`),
  ADD KEY `idx_subdomain` (`subdomain`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_entidad` (`entidad_tipo`,`entidad_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `log_estado_instituciones`
--
ALTER TABLE `log_estado_instituciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realizado_por` (`realizado_por`),
  ADD KEY `idx_institucion` (`institucion_id`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_matricula` (`institucion_id`,`estudiante_id`,`ano_escolar_id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `ano_escolar_id` (`ano_escolar_id`),
  ADD KEY `seccion_id` (`seccion_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `niveles`
--
ALTER TABLE `niveles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones_email`
--
ALTER TABLE `notificaciones_email`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_institucion` (`institucion_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_recibo` (`institucion_id`,`numero_recibo`),
  ADD KEY `recibido_por` (`recibido_por`),
  ADD KEY `idx_cuota` (`cuota_id`),
  ADD KEY `idx_fecha` (`fecha_pago`);

--
-- Indices de la tabla `pagos_saas`
--
ALTER TABLE `pagos_saas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `suscripcion_id` (`suscripcion_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_fecha` (`fecha_pago`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ano_escolar` (`ano_escolar_id`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `preinscripciones`
--
ALTER TABLE `preinscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_codigo` (`codigo_solicitud`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `revisado_por` (`revisado_por`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `grado_id` (`grado_id`);

--
-- Indices de la tabla `preregistro_colegios`
--
ALTER TABLE `preregistro_colegios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_interes` (`plan_interes`),
  ADD KEY `institucion_id` (`institucion_id`),
  ADD KEY `revisado_por` (`revisado_por`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_codigo` (`institucion_id`,`codigo_profesor`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ano_escolar_id` (`ano_escolar_id`),
  ADD KEY `idx_institucion_ano` (`institucion_id`,`ano_escolar_id`),
  ADD KEY `idx_inst` (`institucion_id`),
  ADD KEY `idx_grado` (`grado_id`);

--
-- Indices de la tabla `seccion_asignatura`
--
ALTER TABLE `seccion_asignatura`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asignacion` (`seccion_id`,`asignatura_id`),
  ADD KEY `asignatura_id` (`asignatura_id`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indices de la tabla `secuencias_codigo`
--
ALTER TABLE `secuencias_codigo`
  ADD PRIMARY KEY (`institucion_id`);

--
-- Indices de la tabla `secuencias_preinscripcion`
--
ALTER TABLE `secuencias_preinscripcion`
  ADD PRIMARY KEY (`institucion_id`);

--
-- Indices de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_trial` (`es_trial`,`estado`);

--
-- Indices de la tabla `tutores`
--
ALTER TABLE `tutores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_est` (`estudiante_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `idx_institucion` (`institucion_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anos_escolares`
--
ALTER TABLE `anos_escolares`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comunicado_destinatarios`
--
ALTER TABLE `comunicado_destinatarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conceptos_pago`
--
ALTER TABLE `conceptos_pago`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cron_log`
--
ALTER TABLE `cron_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_padre`
--
ALTER TABLE `estudiante_padre`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grados`
--
ALTER TABLE `grados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `instituciones`
--
ALTER TABLE `instituciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `log_estado_instituciones`
--
ALTER TABLE `log_estado_instituciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `notificaciones_email`
--
ALTER TABLE `notificaciones_email`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `padres`
--
ALTER TABLE `padres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_saas`
--
ALTER TABLE `pagos_saas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `preinscripciones`
--
ALTER TABLE `preinscripciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preregistro_colegios`
--
ALTER TABLE `preregistro_colegios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seccion_asignatura`
--
ALTER TABLE `seccion_asignatura`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tutores`
--
ALTER TABLE `tutores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `anos_escolares`
--
ALTER TABLE `anos_escolares`
  ADD CONSTRAINT `anos_escolares_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `asignaturas_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `profesores` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `profesores` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comunicados_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comunicado_destinatarios`
--
ALTER TABLE `comunicado_destinatarios`
  ADD CONSTRAINT `comunicado_destinatarios_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comunicado_destinatarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `conceptos_pago`
--
ALTER TABLE `conceptos_pago`
  ADD CONSTRAINT `conceptos_pago_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD CONSTRAINT `cuotas_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cuotas_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cuotas_ibfk_3` FOREIGN KEY (`concepto_pago_id`) REFERENCES `conceptos_pago` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estudiantes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estudiante_padre`
--
ALTER TABLE `estudiante_padre`
  ADD CONSTRAINT `estudiante_padre_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estudiante_padre_ibfk_2` FOREIGN KEY (`padre_id`) REFERENCES `padres` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grados`
--
ALTER TABLE `grados`
  ADD CONSTRAINT `grados_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `horarios_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `horarios_ibfk_3` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD CONSTRAINT `log_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `log_estado_instituciones`
--
ALTER TABLE `log_estado_instituciones`
  ADD CONSTRAINT `log_estado_instituciones_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_estado_instituciones_ibfk_2` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_ibfk_3` FOREIGN KEY (`ano_escolar_id`) REFERENCES `anos_escolares` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_ibfk_4` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones_email`
--
ALTER TABLE `notificaciones_email`
  ADD CONSTRAINT `notificaciones_email_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificaciones_email_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `padres`
--
ALTER TABLE `padres`
  ADD CONSTRAINT `padres_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `padres_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`recibido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos_saas`
--
ALTER TABLE `pagos_saas`
  ADD CONSTRAINT `pagos_saas_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_saas_ibfk_2` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_saas_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD CONSTRAINT `periodos_ibfk_1` FOREIGN KEY (`ano_escolar_id`) REFERENCES `anos_escolares` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `preinscripciones`
--
ALTER TABLE `preinscripciones`
  ADD CONSTRAINT `preinscripciones_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `preinscripciones_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `preinscripciones_ibfk_3` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `preinscripciones_ibfk_4` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `preregistro_colegios`
--
ALTER TABLE `preregistro_colegios`
  ADD CONSTRAINT `preregistro_colegios_ibfk_1` FOREIGN KEY (`plan_interes`) REFERENCES `planes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `preregistro_colegios_ibfk_2` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `preregistro_colegios_ibfk_3` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `profesores_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profesores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD CONSTRAINT `secciones_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `secciones_ibfk_2` FOREIGN KEY (`ano_escolar_id`) REFERENCES `anos_escolares` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `secciones_ibfk_3` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`);

--
-- Filtros para la tabla `seccion_asignatura`
--
ALTER TABLE `seccion_asignatura`
  ADD CONSTRAINT `seccion_asignatura_ibfk_1` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seccion_asignatura_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seccion_asignatura_ibfk_3` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `secuencias_codigo`
--
ALTER TABLE `secuencias_codigo`
  ADD CONSTRAINT `secuencias_codigo_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `secuencias_preinscripcion`
--
ALTER TABLE `secuencias_preinscripcion`
  ADD CONSTRAINT `secuencias_preinscripcion_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  ADD CONSTRAINT `suscripciones_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tutores`
--
ALTER TABLE `tutores`
  ADD CONSTRAINT `tutores_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
