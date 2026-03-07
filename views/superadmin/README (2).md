# EduSaaS RD 🇩🇴
**Sistema de Gestión Educativa SaaS para la República Dominicana**

Plataforma multi-tenant que permite a centros educativos privados gestionar estudiantes, matrículas, preinscripciones y pagos, todo bajo una sola instalación con subdominios por institución.

---

## Tabla de contenidos

1. [Stack tecnológico](#stack-tecnológico)
2. [Arquitectura](#arquitectura)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Base de datos](#base-de-datos)
5. [Módulos implementados](#módulos-implementados)
6. [Roles y permisos](#roles-y-permisos)
7. [Planes de suscripción](#planes-de-suscripción)
8. [Instalación](#instalación)
9. [Configuración SMTP](#configuración-smtp)
10. [Cron jobs](#cron-jobs)
11. [Seguridad](#seguridad)
12. [Herramientas de diagnóstico](#herramientas-de-diagnóstico)
13. [Estado del desarrollo](#estado-del-desarrollo)

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 (sin framework) |
| Base de datos | MariaDB 10.4 / MySQL 8 |
| Frontend | Bootstrap 5 + Bootstrap Icons |
| Servidor | Apache (mod_rewrite) |
| Email | PHPMailer (SMTP) |
| Sesiones | PHP Sessions nativas |
| Templating | PHP puro (includes/requires) |

---

## Arquitectura

El sistema sigue el patrón **MVC manual** sin framework:

```
Request → public/index.php → Router → Controller → Model → View
                                   ↓
                            Middlewares (auth, visor, suscripcion)
```

### Multi-tenancy
Cada institución tiene:
- Un `subdomain` único (ej: `demo`, `colegio-san-jose`)
- Un usuario admin propio (`subdomain_admin`)
- Todos sus datos aislados por `institucion_id` en cada tabla

### Middlewares activos
| Middleware | Función |
|---|---|
| `SuscripcionMiddleware` | Bloquea acceso si suscripción vencida o suspendida. Inyecta aviso de trial en sesión |
| `VisorMiddleware` | Permite al superadmin navegar el sistema de un colegio en modo solo-lectura |

---

## Estructura del proyecto

```
edusaas-rd/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          Login, logout, modo visor
│   │   ├── BaseController.php          Render, redirect, flash, CSRF
│   │   ├── DashboardController.php     Dashboard del colegio
│   │   ├── EstudianteController.php    CRUD estudiantes
│   │   ├── PreinscripcionController.php  Formulario público + panel admin
│   │   ├── PreregistroController.php   Registro de colegios nuevos + aprobación
│   │   └── SuperAdminController.php    Panel completo del superadmin (2400+ líneas)
│   ├── Helpers/
│   │   └── ActivityLog.php             Log centralizado de acciones del sistema
│   ├── Middlewares/
│   │   ├── SuscripcionMiddleware.php
│   │   └── VisorMiddleware.php
│   ├── Models/
│   │   ├── BaseModel.php               CRUD genérico con PDO
│   │   ├── ConfigModel.php             Configuración del sistema (clave/valor)
│   │   ├── EstudianteModel.php
│   │   ├── GradoModel.php
│   │   ├── InstitucionModel.php
│   │   ├── NotificacionModel.php       Log de emails enviados
│   │   ├── PagoSaasModel.php           Pagos de suscripciones
│   │   ├── PlanModel.php
│   │   ├── PreregistroModel.php
│   │   ├── SuscripcionModel.php
│   │   └── UsuarioModel.php
│   └── Services/
│       ├── EmailService.php            Envío de emails via PHPMailer
│       └── MetricasService.php         KPIs del dashboard superadmin
├── config/
│   ├── app.php                         Nombre, URL, timezone, uploads
│   ├── constants.php                   ROL_*, SUSCRIPCION_*, CRON_SECRET
│   └── database.php                    Credenciales BD
├── database/
│   └── migrations/                     12 archivos SQL numerados
├── public/
│   ├── index.php                       Front controller
│   ├── assets/                         CSS, JS, imágenes
│   └── uploads/                        Archivos subidos
├── routes/
│   └── web.php                         ~120 rutas definidas
├── views/
│   ├── layouts/
│   │   └── main.php                    Layout principal con sidebar
│   ├── superadmin/                     Todas las vistas del panel SA
│   └── ...                             Vistas del colegio
└── .env                                Variables de entorno (no commitear)
```

---

## Base de datos

### Tablas principales

| Tabla | Descripción | Filas aprox. |
|---|---|---|
| `instituciones` | Colegios registrados | — |
| `suscripciones` | Suscripción activa por colegio | — |
| `pagos_saas` | Historial de cobros a colegios | — |
| `planes` | Planes disponibles (Básico/Profesional/Premium) | 4 |
| `usuarios` | Todos los usuarios del sistema | — |
| `roles` | super_admin, admin, profesor, padre, estudiante | 5 |
| `estudiantes` | Estudiantes por institución | — |
| `matriculas` | Matrículas activas | — |
| `preinscripciones` | Solicitudes de ingreso al colegio | — |
| `preregistro_colegios` | Solicitudes de nuevos colegios | — |
| `configuracion_sistema` | Configuración clave/valor | 21 |
| `log_actividad` | Auditoría de acciones del superadmin | — |
| `notificaciones_email` | Log de todos los emails enviados | — |
| `cron_log` | Historial de ejecuciones del cron | — |

### Migraciones
Los archivos en `database/migrations/` deben ejecutarse **en orden numérico**:

```
002_saas_facturacion.sql        → Estructura base SaaS
003_notificaciones.sql          → Sistema de emails
004_estudiantes.sql             → Módulo educativo
004b_correcciones.sql           → Fixes de la migración anterior
005_preinscripciones.sql        → Formulario de inscripción
006_fix_demo_y_garantias.sql    → Datos demo y FK garantizadas
007_configuracion_sistema.sql   → Panel de configuración
008_preregistro_colegios.sql    → Registro público de colegios
009_log_actividad.sql           → Log de auditoría global
010_trial_descuentos.sql        → Cuentas trial + descuentos en cobros
011_login_lockout_cron.sql      → Bloqueo por intentos fallidos + cron_log
012_audit_fixes.sql             → Fixes encontrados en auditoría (ENGINE=InnoDB, etc.)
```

---

## Módulos implementados

### 🏫 Panel Superadmin (`/superadmin`)

#### Gestión de Instituciones
- Listado con filtros (estado, búsqueda libre)
- Crear institución con usuario admin automático
- Editar datos (nombre, email, subdominio, municipio/provincia)
- Ver detalle: suscripción activa, historial de pagos, log de estados
- Suspender / Reactivar (con email automático al suspender)
- Eliminar permanente (con limpieza de archivos)
- Cambiar plan sin cobro
- Gestión de usuarios del colegio (reset password, toggle activo)
- Modo visor: navegar el sistema del colegio como solo-lectura
- **Export CSV** con todas las instituciones, planes y estadísticas
- Crear con cuenta **Trial** (7/14/30/60 días, monto 0)

#### Caja de Cobros
- Formulario de cobro individual (carga vía AJAX por institución)
- **Descuentos**: por porcentaje o monto fijo, con motivo
- Recibo post-cobro imprimible con ITBIS desglosado (18%)
- Renovación masiva: cobra múltiples colegios en una sola operación
- Panel de ingresos con filtros por año/mes
- Export CSV e impresión PDF de ingresos

#### Planes
- CRUD completo (nombre, precios mensual/anual, límites, features incluidas)
- Activar/desactivar (protegido si hay colegios activos)
- Color e ícono por plan

#### Notificaciones y Email
- Envío manual de avisos de vencimiento (7, 3, 0 días)
- Envío individual personalizado a cualquier institución
- Log de emails con filtros: tipo, estado, institución, fecha
- Estadísticas: tasa de entrega, total enviados, errores
- Configuración SMTP desde el panel (sin editar .env a mano)
- Test de envío SMTP

#### Usuarios Superadmin
- CRUD de cuentas con rol super_admin
- Cambio de contraseña seguro

#### Log de Actividad
- Registro automático de todas las acciones críticas
- Filtros por módulo, usuario, texto libre, fecha
- Contadores por módulo (últimos 30 días)
- Módulos rastreados: instituciones, planes, cobros, usuarios, configuración, preregistros, notificaciones

#### Salud del Sistema (`/superadmin/salud`)
- Versiones PHP y MySQL
- Tamaño total de la BD y tabla por tabla
- Emails fallidos (últimos 7 días)
- Suscripciones vencidas sin actualizar
- Preregistros pendientes
- Usuarios bloqueados + botón de desbloqueo manual
- Historial del cron (últimas 10 ejecuciones)
- Botón "Ejecutar recordatorios ahora"

#### Preregistros de Colegios
- Lista con estados: pendiente / contactado / aprobado / rechazado
- Ver solicitud detallada
- Aprobar → crea institución, usuario admin y suscripción automáticamente
- Rechazar con motivo
- Marcar como contactado

#### Configuración del Sistema
- Panel visual agrupado por sección: Empresa, Facturación, Marca, Sistema
- Subida de logo
- Toggle mantenimiento y registro público
- Número máximo de intentos de login (configurable)
- Días de aviso de vencimiento

---

### 🎓 Panel del Colegio

#### Dashboard
- Resumen de matrícula activa
- Banner de aviso de vencimiento (amarillo/rojo según días restantes)
- Banner de trial con barra de progreso y cuenta regresiva (morado → naranja → rojo)

#### Estudiantes
- Listado con búsqueda
- Crear / editar / ver detalle
- Campos: datos personales, médicos, ubicación, foto

#### Preinscripciones (Admin)
- Lista de solicitudes recibidas por el colegio
- Ver detalle completo con documentos
- Aprobar / rechazar / convertir a estudiante matriculado

---

### 🌐 Rutas Públicas

#### Registro de Colegios (`/registro`)
- Formulario standalone (sin layout del sistema)
- Campos: nombre, tipo, email, municipio, director, mensaje, plan de interés
- Email de notificación automático a superadmins
- Controlado por `sistema_registro_publico` en configuración

#### Preinscripción de Estudiantes (`/preinscripcion/{slug}`)
- Formulario público por institución (accedido por el slug/subdominio)
- Datos completos del estudiante + tutor
- Carga de documentos requeridos (foto, acta, cédula tutor, cert. médico, vacunas)

---

## Roles y permisos

| Rol | ID | Acceso |
|---|---|---|
| `super_admin` | 1 | Todo el panel `/superadmin` + modo visor |
| `admin` | 2 | Panel del colegio completo |
| `profesor` | 3 | 🔲 Módulo futuro |
| `padre` | 4 | 🔲 Módulo futuro |
| `estudiante` | 5 | 🔲 Módulo futuro |

---

## Planes de suscripción

| Plan | Precio mensual | Precio anual | Estudiantes | Profesores |
|---|---|---|---|---|
| Básico | RD$1,500 | RD$15,000 | 150 | 15 |
| Profesional | RD$3,500 | RD$35,000 | 500 | 40 |
| Premium | RD$7,000 | RD$70,000 | Ilimitado | Ilimitado |
| Caja | RD$500 | RD$5,600 | Ilimitado | Ilimitado |

Los precios y límites son editables desde el panel. El ITBIS (18%) se desglosa automáticamente en los recibos (modelo tax-inclusive: el precio ya incluye el impuesto).

---

## Instalación

### Requisitos
- PHP 8.0+
- MySQL 8.0+ o MariaDB 10.4+
- Apache con `mod_rewrite` habilitado
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`

### Pasos

```bash
# 1. Clonar o copiar el proyecto
cp -r edusaas-rd/ /var/www/html/

# 2. Configurar el .env
cp .env.example .env
# Editar: APP_URL, DB_*, MAIL_*

# 3. Ejecutar migraciones en orden
# En phpMyAdmin: importar cada archivo de database/migrations/ del 002 al 012

# 4. Configurar Apache
# El .htaccess en public/ maneja el routing
# Asegurarse que AllowOverride All esté activo

# 5. Permisos de escritura
chmod -R 775 public/uploads/
chmod -R 775 storage/

# 6. Cambiar contraseña del superadmin
# Usuario: superadmin / Contraseña inicial: password
# ⚠️ Cambiar INMEDIATAMENTE desde /superadmin/usuarios/1/editar
```

### Variables de entorno (`.env`)

```env
APP_NAME=EduSaaS RD
APP_URL=http://localhost/edusaas-rd/public
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=edusaas_rd
DB_USERNAME=root
DB_PASSWORD=tu_password

MAIL_DRIVER=smtp
MAIL_FROM_EMAIL=noreply@tudominio.com
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_ENCRYPTION=tls
MAIL_SMTP_USER=tu@gmail.com
MAIL_SMTP_PASSWORD=tuapppassword
```

---

## Configuración SMTP

El SMTP se puede configurar desde **Notificaciones → Configuración SMTP** en el panel superadmin sin tocar el `.env` directamente.

### Google Workspace / Gmail
1. Activar verificación en dos pasos
2. Generar contraseña de aplicación en `myaccount.google.com/security`
3. La contraseña se guarda **sin espacios** (el sistema los elimina automáticamente)

### Emails automáticos implementados

| Tipo | Cuándo se envía |
|---|---|
| `bienvenida` | Al crear una institución nueva |
| `plan_renovado` | Al registrar un pago |
| `suspension` | Al suspender una institución |
| `vencimiento_7dias` | 7 días antes del vencimiento (cron) |
| `vencimiento_3dias` | 3 días antes del vencimiento (cron) |
| `vencimiento_hoy` | El día del vencimiento (cron) |
| `trial_expirando` | 🔲 Pendiente de implementar |
| `trial_expirado` | 🔲 Pendiente de implementar |
| `personalizado` | Envío manual desde el panel |

---

## Cron jobs

El sistema tiene un endpoint para recordatorios automáticos de vencimiento.

### Configuración en el servidor

```bash
# Ejecutar diariamente a las 8:00 AM
0 8 * * * curl -s "https://tudominio.com/superadmin/cron/avisos-vencimiento?token=TU_CRON_SECRET" >> /var/log/edusaas_cron.log 2>&1
```

El token se configura en `config/constants.php`:
```php
define('CRON_SECRET', 'cambia_esto_por_algo_seguro');
```

La respuesta es JSON:
```json
{ "ok": true, "enviados": 3, "errores": 0, "detalle": ["Colegio A (7d)", "Colegio B (3d)"] }
```

El historial de ejecuciones es visible en **Salud del Sistema**.

---

## Seguridad

### Implementado
- ✅ Contraseñas con `password_hash(PASSWORD_BCRYPT)`
- ✅ CSRF token en todos los formularios POST
- ✅ Protección SQL Injection con PDO prepared statements en todo el sistema
- ✅ Bloqueo de cuenta tras 5 intentos fallidos (30 minutos, configurable)
- ✅ Desbloqueo manual desde el panel de salud
- ✅ Modo mantenimiento con bypass para superadmin
- ✅ VisorMiddleware: superadmin en modo visor no puede hacer POST
- ✅ Validación de rol en cada método del controlador (`requireRole()`)
- ✅ CRON_SECRET con `hash_equals()` para timing-safe comparison
- ✅ Subidas de archivos con validación de extensión y tamaño

### ⚠️ Pendiente antes de producción
- Cambiar contraseña del superadmin (inicial: `password`)
- Cambiar `CRON_SECRET` en `config/constants.php`
- Eliminar archivos de diagnóstico: `trs.php`, `debug_pre.php`, `debug_session.php`, `estado.php`, `quick_diag.php`, `test.php`
- Configurar `APP_ENV=production` y `APP_DEBUG=false`
- Configurar HTTPS y redirigir HTTP
- Revisar permisos de archivos en el servidor

---

## Herramientas de diagnóstico

> ⚠️ Solo para desarrollo. **Eliminar antes de producción.**

| Archivo | Función |
|---|---|
| `public/trs.php` | Auditoría completa: hace login como superadmin y prueba todas las rutas del sistema reportando HTTP status, errores PHP, tiempos de respuesta y estado de la BD |
| `public/debug_session.php` | Muestra el contenido de la sesión actual |
| `public/debug_pre.php` | Debug de preregistros |
| `public/estado.php` | Estado general de la aplicación |
| `public/quick_diag.php` | Diagnóstico rápido de conexión y configuración |

---

## Estado del desarrollo

### ✅ Completado

**SaaS / Superadmin**
- Multi-tenancy con subdominio por colegio
- CRUD completo de instituciones, planes y usuarios SA
- Sistema de suscripciones (mensual/anual/trial)
- Caja de cobros con descuentos y recibos con ITBIS
- Renovación masiva
- Reporte de ingresos (CSV + PDF imprimible)
- Notificaciones por email (automáticas + manuales)
- Log de actividad global
- Log de emails enviados
- Dashboard de salud del sistema
- Modo visor (superadmin navega como colegio, solo lectura)
- Bloqueo por intentos fallidos + desbloqueo manual
- Cron de recordatorios de vencimiento
- Panel de configuración visual completo
- Banner de trial con cuenta regresiva y barra de progreso
- Registro público de colegios con flujo de aprobación

**Módulo Educativo (Colegio)**
- Dashboard con métricas de suscripción
- Gestión de estudiantes (CRUD)
- Preinscripciones: formulario público + panel admin + conversión a estudiante

### 🔲 Pendiente (módulos futuros)

Estos módulos están en la BD (tablas creadas) pero aún sin interfaz:

- **Gestión académica**: años escolares, grados, secciones, asignaturas
- **Matrículas**: asignación de estudiantes a secciones
- **Calificaciones**: registro por asignatura y período
- **Asistencias**: control diario por sección
- **Horarios**: schedule semanal por sección/profesor
- **Comunicados**: mensajes a padres y estudiantes
- **Portal de padres**: acceso de tutores a información del estudiante
- **Portal de profesores**: registro de notas y asistencias
- **Módulo de pagos del colegio**: cuotas, conceptos, recibos
- **API REST**: acceso programático (solo plan Premium)
- **Reportes avanzados**: boletines, estadísticas, exportaciones

---

## Licencia

Proyecto privado — EduSaaS RD © 2026. Todos los derechos reservados.
