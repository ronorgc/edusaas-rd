# 🏗️ ARQUITECTURA — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Estado:** EN DESARROLLO ACTIVO

---

## 📌 Descripción del sistema

**EduSaaS RD** es un sistema SaaS multi-tenant para gestión académica y administrativa de instituciones educativas en República Dominicana.

**Modelo de negocio:**
- **EduSaaS RD** actúa como operador (SuperAdmin) que vende planes de suscripción a colegios.
- Cada colegio cliente accede a su propio panel de administración aislado.
- Los colegios gestionan: estudiantes, matrículas, calificaciones, asistencia, pagos y comunicados.

---

## 🔧 Stack tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Lenguaje backend | PHP | 8.2 |
| Base de datos | MariaDB | 10.4 |
| Frontend | Bootstrap + JS vanilla | Bootstrap 5.3.2 |
| Servidor local | XAMPP (Apache) | — |
| Servidor producción | cPanel / VPS Linux | Apache + mod_rewrite |
| Email | SmtpClient.php propio | — |
| Autenticación | Sessions PHP + bcrypt | — |

**Sin frameworks externos.** Todo el MVC es código propio (ver ADR-001).

---

## 🗂️ Estructura de directorios

```
edusaas-rd/
├── app/
│   ├── Controllers/        ← Lógica de request/response
│   │   ├── BaseController.php          (render, redirect, flash, requireRole, requireSuscripcion)
│   │   ├── AuthController.php          (login, logout, bloqueo por intentos)
│   │   ├── DashboardController.php     (dashboard superadmin)
│   │   ├── SuperAdminController.php    (2,443 líneas — instituciones, planes, cobros, etc.)
│   │   ├── AdminController.php         (panel del colegio — años, grados, secciones, períodos)
│   │   ├── EstudianteController.php    (CRUD estudiantes + tutor + foto)
│   │   ├── PreregistroController.php   (solicitudes de nuevos colegios)
│   │   └── PreinscripcionController.php (portal público de preinscripción)
│   │
│   ├── Models/             ← Active Record simplificado
│   │   ├── BaseModel.php               (find, where, create, update, delete, paginate)
│   │   ├── AnoEscolarModel.php         ⚠️ actualmente: AnoescolarMode.php — BUG-CRÍTICO-1
│   │   ├── ConfigModel.php             (configuración del sistema key-value)
│   │   ├── EstudianteModel.php
│   │   ├── GradoModel.php
│   │   ├── InstitucionModel.php
│   │   ├── NotificacionModel.php
│   │   ├── PagoSaasModel.php           (pagos de suscripción al SaaS)
│   │   ├── PlanModel.php
│   │   ├── PreregistroModel.php
│   │   ├── SeccionModel.php
│   │   ├── SuscripcionModel.php
│   │   └── UsuarioModel.php
│   │
│   ├── Helpers/            ← Utilidades de soporte
│   │   ├── ActivityLog.php             (registro de acciones por usuario)
│   │   └── PlanHelper.php              (verificación de módulos por plan)
│   │
│   ├── Middlewares/        ← Filtros de request
│   │   ├── SuscripcionMiddleware.php   (verifica suscripción activa del colegio)
│   │   └── VisorMiddleware.php         (modo visor — superadmin ve panel de colegio)
│   │
│   └── Services/           ← Lógica de dominio compleja
│       ├── EmailService.php            (9 tipos de email transaccional)
│       └── MetricasService.php         (KPIs SaaS: MRR, Churn, retención)
│
├── config/
│   ├── app.php             ← URL base, modo debug, rutas de uploads
│   ├── constants.php       ← Constantes del sistema (roles, límites, etc.)
│   ├── database.php        ← Credenciales BD (lee de .env)
│   └── mail.php            ← Config SMTP por defecto
│
├── database/
│   ├── migrations/         ← Vacío actualmente — pendiente organizar
│   └── edusaas_rd.sql      ← Schema completo (33 tablas)
│
├── docs/                   ← Documentación del proyecto
│   ├── BITACORA.md
│   ├── INSTRUCCIONES_PROYECTO.md
│   ├── PLAN_CORRECCIONES.md
│   ├── DEUDA_TECNICA.md
│   ├── ADR.md
│   ├── ARQUITECTURA.md     ← Este archivo
│   ├── SCHEMA_BD.md        ← Pendiente (requiere revisión del .sql)
│   ├── ROADMAP.md          ← Pendiente
│   ├── SEGURIDAD.md        ← Pendiente
│   └── DEPLOY.md           ← Pendiente
│
├── public/                 ← Único directorio expuesto al web
│   ├── .htaccess           ← Redirige todo a index.php
│   ├── index.php           ← Front Controller
│   ├── assets/img/         ← Assets estáticos
│   ├── forms/
│   │   ├── preinscripcion/ ← Portal público de preinscripción de alumnos
│   │   └── preregistro/    ← Portal público para colegios que quieren contratar
│   └── uploads/            ← Archivos subidos (fotos estudiantes, docs)
│
├── routes/
│   └── web.php             ← Definición de todas las rutas
│
├── storage/
│   └── logs/               ← Logs de mail, errores
│
├── vendor/                 ← Código de infraestructura propio
│   ├── Autoload.php        ← PSR-4 simplificado
│   ├── Database.php        ← Singleton PDO
│   ├── Router.php          ← Dispatch de rutas
│   └── SmtpClient.php      ← Cliente SMTP manual
│
├── views/                  ← Templates PHP
│   ├── auth/               ← Login
│   ├── colegio/admin/      ← Panel del colegio (activo)
│   ├── colegio/alumno/     ← Portal alumno (Fase 5 — vacío)
│   ├── colegio/maestro/    ← Portal maestro (Fase 5 — vacío)
│   ├── colegio/padre/      ← Portal padre (Fase 5 — vacío)
│   ├── emails/             ← Templates de email HTML
│   ├── errors/             ← 403, 404, mantenimiento, suspendido
│   ├── layouts/main.php    ← Layout principal (envuelve todas las vistas)
│   ├── partials/           ← Componentes reutilizables
│   └── superadmin/         ← Panel de EduSaaS RD
│
└── .env                    ← Variables de entorno (no en control de versiones)
```

---

## 🔄 Flujo de un request

```
[Browser] → GET /admin/estudiantes
    ↓
[public/.htaccess]
    → Rewrite a public/index.php
    ↓
[public/index.php]
    1. Carga .env
    2. require config/constants.php
    3. require config/app.php
    4. require config/database.php
    5. require vendor/Autoload.php
    6. require routes/web.php
    7. Router::dispatch($_SERVER['REQUEST_URI'])
    ↓
[vendor/Router.php]
    → Encuentra ruta '/admin/estudiantes'
    → Verifica sesión activa
    → Instancia EstudianteController
    → Llama EstudianteController::index()
    ↓
[app/Controllers/EstudianteController::index()]
    1. BaseController::requireRole(ROL_ADMIN_COLEGIO)
    2. SuscripcionMiddleware::verificar($institucionId)
    3. EstudianteModel::paginate(...)
    4. BaseController::render('colegio/admin/estudiantes/index', $data)
    ↓
[views/layouts/main.php]
    → Envuelve views/colegio/admin/estudiantes/index.php
    → Renderiza HTML completo
    ↓
[Browser] ← HTML response
```

---

## 🏢 Capas del sistema

### Capa 1 — Infraestructura (`vendor/`)
Responsabilidad: conectar HTTP con la aplicación.
- `Database.php`: conexión PDO única
- `Router.php`: mapear URLs a controllers
- `Autoload.php`: cargar clases
- `SmtpClient.php`: enviar emails via SMTP

### Capa 2 — Modelos (`app/Models/`)
Responsabilidad: acceso a datos y reglas de negocio simples.
- `BaseModel`: CRUD genérico (find, where, create, update, delete, paginate)
- Modelos específicos extienden BaseModel con queries propias
- Solo hablan con la base de datos — nunca con HTTP

### Capa 3 — Helpers y Middlewares
Responsabilidad: utilidades transversales y filtros de acceso.
- `ActivityLog`: registro inmutable de acciones
- `PlanHelper`: decisiones basadas en el plan del colegio
- `SuscripcionMiddleware`: bloquea acceso si suscripción vencida
- `VisorMiddleware`: modo solo-lectura para superadmin

### Capa 4 — Servicios (`app/Services/`)
Responsabilidad: lógica de dominio compleja que combina múltiples modelos.
- `EmailService`: orquesta el envío de 9 tipos de emails
- `MetricasService`: calcula KPIs SaaS (MRR, ARR, Churn, retención)

### Capa 5 — Controllers (`app/Controllers/`)
Responsabilidad: coordinar el flujo de un request.
- Reciben HTTP, delegan a models/services, responden con views
- `BaseController` provee: `render()`, `redirect()`, `flash()`, `requireRole()`, `validateCsrf()`

### Capa 6 — Vistas (`views/`)
Responsabilidad: presentación únicamente.
- Reciben variables del controller — no ejecutan lógica de negocio
- `layouts/main.php` es el wrapper de todo el panel
- Los templates de email son vistas independientes

---

## 🔐 Arquitectura de seguridad

### Autenticación
- Session PHP con `session_regenerate_id()` al login
- Bloqueo automático tras N intentos fallidos (configurable)
- `AuthController::login()` con timing-safe compare (bcrypt)

### Autorización
- `BaseController::requireRole($rol)` en cada acción sensible
- Roles: `ROL_SUPER_ADMIN`, `ROL_ADMIN_COLEGIO`, `ROL_PROFESOR`, `ROL_ALUMNO`, `ROL_PADRE`
- Modo Visor: SuperAdmin puede ver panel de cualquier colegio en modo solo-lectura

### CSRF
- Token generado por `BaseController::generateCsrfToken()`
- Validado por `BaseController::validateCsrf()` en todo POST
- ⚠️ Pendiente: algunos forms tienen `name="_csrf_token"` (underscore) incorrecto — ver BUG-B8b-06

### Multi-tenant isolation
- Toda query del panel de colegio filtra por `institucion_id` de la sesión
- SuperAdmin puede acceder a cualquier tenant via Modo Visor
- ⚠️ Pendiente: auditar todas las queries del AdminController para confirmar isolation

---

## 🗄️ Base de datos — Visión general

**Motor:** MariaDB 10.4  
**Total de tablas:** 33  
**Schema completo:** `database/edusaas_rd.sql`  
**Detalle de tablas:** ver `SCHEMA_BD.md` (pendiente — requiere revisión del .sql)

### Tablas principales del SaaS (SuperAdmin)

| Tabla | Descripción |
|-------|-------------|
| `instituciones` | Colegios clientes registrados |
| `suscripciones` | Historial de suscripciones por institución |
| `planes` | Planes disponibles (Básico, Estándar, Premium) |
| `pagos_saas` | Pagos de suscripción recibidos |
| `usuarios` | Todos los usuarios del sistema (todos los roles) |
| `preregistro_colegios` | Solicitudes de colegios que quieren contratar |
| `notificaciones` | Notificaciones enviadas desde el panel |
| `actividad_log` | Log de acciones de usuarios |
| `configuracion` | Configuración del sistema (key-value) |

### Tablas del colegio (Admin/Colegio)

| Tabla | Descripción |
|-------|-------------|
| `anos_escolares` | Años escolares por institución |
| `grados` | Grados (pre-cargados con MINERD) |
| `secciones` | Secciones por grado y año escolar |
| `periodos` | Períodos de evaluación |
| `estudiantes` | Registro de alumnos |
| `tutores` | Tutores/padres de estudiantes |
| `matriculas` | Matrículas de estudiantes por año |
| `preinscripciones` | Solicitudes de preinscripción pública |

### Tablas pendientes de implementación (Sprints 3.x+)

| Tabla | Módulo | Sprint |
|-------|--------|--------|
| `asignaturas` | Materias | 3.1 |
| `profesores` | Docentes | 2.2 |
| `horarios` | Carga horaria | 3.2 |
| `asistencia` | Control de asistencia | 3.3 |
| `calificaciones` | Notas por período | 3.4 |
| `comunicados` | Mensajería interna | 3.5 |
| `pagos_colegio` | Pagos de alumnos | Fase 4 |

---

## 🌐 Dos paneles, una aplicación

```
URL /superadmin/*  →  Panel EduSaaS RD
    SuperAdminController, DashboardController
    → Gestiona colegios, planes, cobros, configuración
    → Solo accesible con ROL_SUPER_ADMIN

URL /admin/*       →  Panel del Colegio
    AdminController, EstudianteController, etc.
    → Gestiona estudiantes, configuración académica
    → Solo accesible con ROL_ADMIN_COLEGIO (+ modo visor del superadmin)

URL /             →  Portales públicos
    PreregistroController, PreinscripcionController
    → Sin autenticación requerida
    → Formularios accesibles por URL pública del colegio
```

---

## 📧 Flujo de emails

```
[Evento del sistema]
    ↓ (ej: colegio aprueba preinscripción)
[Controller llama EmailService::método()]
    ↓
[EmailService carga template views/emails/xxx.php]
    ↓
[EmailService envuelve en views/emails/layout.php]
    ↓
[SmtpClient::send() via socket SMTP]
    ↓
[Log en storage/logs/mail.log]
```

**Templates activos:**
- `bienvenida.php` — nuevo admin de colegio
- `confirmacion_pago.php` — pago de suscripción recibido
- `aviso_vencimiento.php` — próximo vencimiento
- `suspension.php` — cuenta suspendida
- `reset_clave.php` — recuperación de contraseña
- `preinscripcion_aprobada.php` — alumno preinscrito aprobado
- `personalizado.php` — email libre desde el panel superadmin
- `smtp_test.php` — prueba de configuración SMTP

---

## 🚀 Fases de desarrollo

| Fase | Nombre | Estado |
|------|--------|--------|
| 1 | Infraestructura SaaS + SuperAdmin | ✅ ~95% completo |
| 2.1 | Config académica del colegio | ✅ Completo |
| 2.2 | Profesores | ⏳ Pendiente |
| 2.3 | Matrículas | ⏳ Pendiente |
| 3.x | Asistencia, Calificaciones, Comunicados | ⏳ Pendiente |
| 4 | Pagos del colegio (alumnos) | ⏳ Pendiente |
| 5 | Portales Alumno / Maestro / Padre | ⏳ Pendiente |
| 6 | Reportes y exportaciones | ⏳ Pendiente |

**Ver detalle:** `ROADMAP.md` (pendiente)

---

## 🔧 Decisiones de arquitectura

Ver `ADR.md` para el detalle completo de los 16 ADRs del proyecto.

**ADRs más relevantes para entender la arquitectura:**
- ADR-001: Por qué MVC casero
- ADR-003: Cómo funciona el multi-tenancy
- ADR-004: Por qué Front Controller único
- ADR-007: Por qué Singleton para BD
- ADR-009: Implicaciones del target Linux/cPanel

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `SCHEMA_BD.md` (pendiente) · `ADR.md` · `PLAN_CORRECCIONES.md`*
