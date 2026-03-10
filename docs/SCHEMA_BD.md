# 🗄️ SCHEMA DE BASE DE DATOS — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Motor:** MariaDB 10.4 · Charset: `utf8mb4_unicode_ci`  
**Total de tablas:** 36 *(la bitácora indicaba 33 — ver Nota de auditoría al final)*

---

## 🗺️ Mapa de tablas por dominio

```
DOMINIO SAAS (SuperAdmin)
├── instituciones           ← Colegios clientes
├── suscripciones           ← Suscripciones por institución
├── planes                  ← Planes disponibles (seed: 4)
├── pagos_saas              ← Pagos de suscripción
├── usuarios                ← Todos los usuarios del sistema
├── roles                   ← Roles del sistema (seed: 5)
├── preregistro_colegios    ← Solicitudes de nuevos colegios
├── notificaciones_email    ← Log de emails enviados
├── log_actividad           ← Auditoría de acciones
├── log_estado_instituciones← Historial de cambios de estado
├── configuracion_sistema   ← Config key-value (seed: 21 claves)
└── cron_log                ← Log de tareas programadas

DOMINIO ACADÉMICO (Colegio)
├── anos_escolares          ← Años escolares por colegio
├── grados                  ← Grados (seed MINERD por colegio)
├── secciones               ← Secciones por grado y año
├── periodos                ← Períodos de evaluación
├── asignaturas             ← Materias del colegio
├── niveles                 ← Niveles (seed global: Inicial/Primaria/Secundaria)
├── horarios                ← Carga horaria (sección+asignatura+profesor)
└── seccion_asignatura      ← Pivot: sección ↔ asignatura ↔ profesor

DOMINIO ESTUDIANTES
├── estudiantes             ← Registro de alumnos
├── tutores                 ← Tutor directo del estudiante (embedded)
├── padres                  ← Padres con posible usuario del sistema
├── estudiante_padre        ← Pivot: estudiante ↔ padre
├── matriculas              ← Matrícula por año escolar
├── preinscripciones        ← Solicitudes públicas de inscripción
└── secuencias_preinscripcion ← Contador de PRE-YYYY-NNNN por colegio

DOMINIO PAGOS (Colegio)
├── conceptos_pago          ← Matrícula, mensualidad, etc.
├── cuotas                  ← Cuotas generadas por matrícula
└── pagos                   ← Pagos registrados contra cuotas

DOMINIO ACADÉMICO AVANZADO
├── calificaciones          ← Notas por matrícula+asignatura+período
├── asistencias             ← Asistencia diaria por matrícula
├── comunicados             ← Mensajes a padres/alumnos
└── comunicado_destinatarios← Pivot + estado de lectura

SECUENCIAS
└── secuencias_codigo       ← Contador de códigos de estudiante por colegio
```

---

## 📋 Detalle de tablas

### `instituciones` — Colegios clientes

| Columna | Tipo | Nulo | Default | Notas |
|---------|------|------|---------|-------|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | PK |
| `nombre` | VARCHAR(200) | No | — | — |
| `tipo` | ENUM('publico','privado') | No | — | — |
| `codigo_minerd` | VARCHAR(50) | Sí | NULL | Código oficial MINERD |
| `rnc` | VARCHAR(20) | Sí | NULL | Para instituciones privadas |
| `telefono` | VARCHAR(20) | Sí | NULL | — |
| `email` | VARCHAR(100) | Sí | NULL | — |
| `direccion` | TEXT | Sí | NULL | — |
| `municipio` | VARCHAR(100) | Sí | NULL | — |
| `provincia` | VARCHAR(100) | Sí | NULL | — |
| `logo` | VARCHAR(255) | Sí | NULL | — |
| `subdomain` | VARCHAR(50) | Sí | NULL | UNIQUE — slug del portal |
| `notas` | TEXT | Sí | NULL | Notas internas del superadmin |
| `activo` | TINYINT(1) | Sí | 1 | — |
| `fecha_registro` | TIMESTAMP | No | NOW() | — |
| `created_at` | TIMESTAMP | No | NOW() | — |
| `updated_at` | TIMESTAMP | No | NOW() | ON UPDATE |

**Índices:** PK(`id`), UNIQUE(`subdomain`), IDX(`subdomain`), IDX(`activo`)  
**FKs entrantes:** Prácticamente todas las tablas del colegio hacen CASCADE desde aquí.

---

### `suscripciones` — Suscripciones de colegios

| Columna | Tipo | Nulo | Default | Notas |
|---------|------|------|---------|-------|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | PK |
| `institucion_id` | INT UNSIGNED | No | — | FK → instituciones |
| `plan_id` | INT UNSIGNED | No | — | FK → planes |
| `tipo_facturacion` | ENUM('mensual','anual') | No | 'mensual' | — |
| `es_trial` | TINYINT(1) | No | 0 | Período de prueba |
| `trial_dias` | SMALLINT(6) | Sí | NULL | Días de prueba otorgados |
| `monto` | DECIMAL(10,2) | No | — | Monto acordado (puede diferir del plan) |
| `fecha_inicio` | DATE | No | — | — |
| `fecha_vencimiento` | DATE | No | — | — |
| `estado` | ENUM('activa','vencida','suspendida','cancelada') | Sí | 'activa' | — |
| `renovacion_auto` | TINYINT(1) | Sí | 0 | — |
| `notas` | TEXT | Sí | NULL | Notas internas |
| `creado_por` | INT UNSIGNED | Sí | NULL | FK → usuarios |
| `created_at` | TIMESTAMP | No | NOW() | — |
| `updated_at` | TIMESTAMP | No | NOW() | ON UPDATE |

**Índices:** PK, IDX(`institucion_id`), IDX(`estado`), IDX(`fecha_vencimiento`), IDX(`es_trial,estado`)  
**⚠️ ADR-012 PENDIENTE:** Falta columna `fecha_baja DATETIME NULL` — sin ella el Churn Rate es impreciso.

---

### `planes` — Planes disponibles

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `nombre` | VARCHAR(50) | Básico, Profesional, Premium, Caja |
| `precio_mensual` | DECIMAL(10,2) | — |
| `precio_anual` | DECIMAL(10,2) | Precio con descuento anual |
| `max_estudiantes` | INT | 0 = ilimitado |
| `max_profesores` | INT | 0 = ilimitado |
| `max_secciones` | INT | 0 = ilimitado |
| `incluye_pagos` | TINYINT(1) | Módulo de pagos |
| `incluye_reportes` | TINYINT(1) | Reportes PDF/Excel |
| `incluye_comunicados` | TINYINT(1) | Default: 1 |
| `incluye_api` | TINYINT(1) | Acceso a API REST |
| `color` | VARCHAR(7) | Hex para UI |
| `icono` | VARCHAR(50) | Bootstrap icon |
| `orden` | INT | Orden de presentación |
| `activo` | TINYINT(1) | — |

**Seed data:**

| id | Nombre | Mensual | Anual | Estudiantes | Profesores | Secciones |
|----|--------|---------|-------|-------------|------------|-----------|
| 1 | Básico | RD$1,500 | RD$15,000 | 150 | 15 | 6 |
| 2 | Profesional | RD$3,500 | RD$35,000 | 500 | 40 | 20 |
| 3 | Premium | RD$7,000 | RD$70,000 | ∞ | ∞ | ∞ |
| 4 | Caja | RD$500 | RD$5,600 | ∞ | ∞ | ∞ |

---

### `pagos_saas` — Pagos de suscripción al SaaS

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `suscripcion_id` | INT UNSIGNED | FK → suscripciones CASCADE |
| `numero_factura` | VARCHAR(20) | UNIQUE — formato `EduSaaS-2026-0001` |
| `monto` | DECIMAL(10,2) | — |
| `descuento_pct` | DECIMAL(5,2) | % descuento |
| `descuento_monto` | DECIMAL(10,2) | Monto descontado |
| `monto_original` | DECIMAL(10,2) | Antes del descuento |
| `descuento_motivo` | VARCHAR(150) | Motivo del descuento |
| `fecha_pago` | DATE | — |
| `metodo_pago` | ENUM('transferencia','efectivo','tarjeta','cheque') | — |
| `referencia` | VARCHAR(100) | N° transferencia, etc. |
| `periodo_desde` | DATE | Inicio del período cubierto |
| `periodo_hasta` | DATE | Fin del período cubierto |
| `estado` | ENUM('confirmado','pendiente','anulado') | Default: 'confirmado' |
| `notas` | TEXT | — |
| `registrado_por` | INT UNSIGNED | FK → usuarios SET NULL |

**Nota BUG-M-02:** UNIQUE en `numero_factura` actúa como red de seguridad para la race condition, pero la lógica de generación en `PagoSaasModel::generarNumeroFactura()` debe corregirse igualmente.

---

### `usuarios` — Todos los usuarios del sistema

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | NULL para superadmin · FK → instituciones CASCADE |
| `rol_id` | INT UNSIGNED | FK → roles |
| `username` | VARCHAR(50) | UNIQUE |
| `email` | VARCHAR(100) | IDX |
| `password` | VARCHAR(255) | bcrypt hash |
| `nombres` | VARCHAR(100) | — |
| `apellidos` | VARCHAR(100) | — |
| `cedula` | VARCHAR(20) | — |
| `telefono` | VARCHAR(20) | — |
| `foto` | VARCHAR(255) | — |
| `activo` | TINYINT(1) | Default: 1 |
| `ultimo_acceso` | TIMESTAMP | NULL si nunca accedió |
| `intentos_fallidos` | TINYINT(3) UNSIGNED | Default: 0 |
| `bloqueado_hasta` | DATETIME | NULL = no bloqueado |
| `created_at` / `updated_at` | TIMESTAMP | — |

**Seed data:** usuario `superadmin` (id=1, rol=1, password: `password` — bcrypt de Laravel default `$2y$10$92IXU...`)  
**⚠️ ADR-013 PENDIENTE:** Falta columna `debe_cambiar_password TINYINT(1) DEFAULT 0`

---

### `roles` — Roles del sistema

| id | nombre | descripción |
|----|--------|-------------|
| 1 | super_admin | Administrador global |
| 2 | admin | Administrador de institución |
| 3 | profesor | Docente |
| 4 | padre | Padre o tutor |
| 5 | estudiante | Estudiante |

**Nota:** Columna `permisos JSON` existe pero todos los roles tienen `NULL` — sistema de permisos granulares no implementado aún.

---

### `configuracion_sistema` — Configuración key-value

**PK:** `clave` (VARCHAR 100)  
**Grupos y claves seed:**

| Grupo | Clave | Tipo | Valor seed |
|-------|-------|------|------------|
| empresa | `empresa_nombre` | text | EduSaaS RD |
| empresa | `empresa_email` | email | soporte@edusaas.do |
| empresa | `empresa_sitio_web` | url | https://edusaas.do |
| empresa | `empresa_direccion` | textarea | Santo Domingo, RD |
| empresa | `empresa_razon_social` | text | EduSaaS RD SRL |
| empresa | `empresa_rnc` | text | (vacío) |
| empresa | `empresa_telefono` | text | (vacío) |
| facturacion | `factura_moneda` | text | RD$ |
| facturacion | `factura_moneda_nombre` | text | Peso Dominicano |
| facturacion | `factura_itbis` | number | 18 |
| facturacion | `factura_prefijo` | text | EduSaaS |
| facturacion | `factura_nota_pie` | textarea | Gracias por su preferencia... |
| marca | `marca_nombre_sistema` | text | EduSaaS RD |
| marca | `marca_slogan` | text | Sistema Educativo RD 🇩🇴 |
| marca | `marca_color_primario` | color | #db5d1a |
| marca | `marca_color_acento` | color | #10b981 |
| marca | `marca_logo_url` | image | (vacío) |
| sistema | `sistema_dias_aviso` | number | 7 |
| sistema | `sistema_max_intentos` | number | 5 |
| sistema | `sistema_modo_mantenimiento` | boolean | 0 |
| sistema | `sistema_registro_publico` | boolean | 1 |

---

### `anos_escolares` — Años escolares por colegio

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `nombre` | VARCHAR(50) | Ej: `2024-2025` |
| `fecha_inicio` | DATE | — |
| `fecha_fin` | DATE | — |
| `activo` | TINYINT(1) | Default: 0 |
| `created_at` | TIMESTAMP | — |

**⚠️ BUG-4:** Falta `UNIQUE KEY (institucion_id, nombre)` — un colegio podría tener dos años con el mismo nombre.

---

### `grados` — Grados académicos

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `nombre` | VARCHAR(60) | Ej: `1er Grado`, `2do Bachillerato` |
| `nivel` | ENUM('inicial','primario','secundario') | Default: 'primario' |
| `orden` | TINYINT(4) | Para ordenar en UI |
| `activo` | TINYINT(1) | Default: 1 |

**Nota:** `nivel` es un ENUM directo en lugar de FK a `niveles`. La tabla `niveles` existe como seed global pero `grados` no la referencia. Ver hallazgo de auditoría.

---

### `secciones` — Secciones por grado y año

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `ano_escolar_id` | INT UNSIGNED | FK → anos_escolares CASCADE |
| `grado_id` | INT UNSIGNED | FK → grados (sin CASCADE) |
| `nombre` | VARCHAR(10) | `A`, `B`, `C` |
| `capacidad` | SMALLINT(6) | Default: **40** |
| `capacidad_maxima` | INT | Default: **30** |
| `activo` | TINYINT(1) | Default: 1 |

**⚠️ HALLAZGO:** Dos columnas de capacidad con defaults inconsistentes (`capacidad=40` vs `capacidad_maxima=30`). Clarificar cuál usa el sistema. Ver hallazgo #5.

---

### `periodos` — Períodos de evaluación

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `ano_escolar_id` | INT UNSIGNED | FK → anos_escolares CASCADE |
| `nombre` | VARCHAR(50) | `1er Período`, etc. |
| `numero` | INT | 1, 2, 3, 4, 5 |
| `fecha_inicio` | DATE | — |
| `fecha_fin` | DATE | — |
| `activo` | TINYINT(1) | Default: 0 |

**Nota:** Sin `institucion_id` directo — el tenant se obtiene via `ano_escolar_id → anos_escolares.institucion_id`.

---

### `estudiantes` — Registro de alumnos

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `usuario_id` | INT UNSIGNED | Sí/NULL · FK → usuarios SET NULL |
| `codigo_estudiante` | VARCHAR(20) | UNIQUE por institución |
| `nombres` / `apellidos` | VARCHAR(100) | IDX(`apellidos, nombres`) |
| `fecha_nacimiento` | DATE | — |
| `sexo` | ENUM('M','F') | — |
| `cedula` | VARCHAR(20) | UNIQUE por institución |
| `nie` | VARCHAR(20) | Número MINERD |
| `lugar_nacimiento`, `nacionalidad` | VARCHAR | Default: 'Dominicana' |
| `direccion`, `municipio`, `provincia` | TEXT/VARCHAR | — |
| `telefono`, `email` | VARCHAR | — |
| `foto` | VARCHAR(255) | Path al archivo |
| `tipo_sangre` | VARCHAR(5) | — |
| `alergias`, `condiciones_medicas` | TEXT | — |
| `activo` | TINYINT(1) | Default: 1 |

**Índices:** UNIQUE(`institucion_id, codigo_estudiante`), UNIQUE(`institucion_id, cedula`)

---

### `tutores` — Tutor directo del estudiante

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `estudiante_id` | INT UNSIGNED | FK → estudiantes CASCADE |
| `parentesco` | ENUM('padre','madre','tutor','abuelo','abuela','tio','otro') | Default: 'tutor' |
| `nombres` / `apellidos` | VARCHAR(80) | — |
| `cedula` | VARCHAR(15) | — |
| `telefono` / `telefono_trabajo` | VARCHAR(20) | — |
| `email` | VARCHAR(150) | — |
| `ocupacion` | VARCHAR(80) | — |
| `es_responsable` | TINYINT(1) | 1 = responsable de pagos |

**⚠️ ADR-010 PENDIENTE:** Esta tabla coexiste con `padres` + `estudiante_padre`. Ver sección de hallazgos.

---

### `padres` — Padres con acceso potencial al sistema

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `usuario_id` | INT UNSIGNED | Sí/NULL · FK → usuarios SET NULL |
| `nombres` / `apellidos` | VARCHAR(100) | — |
| `cedula` | VARCHAR(20) | — |
| `parentesco` | VARCHAR(50) | Padre, Madre, Tutor |
| `telefono` / `celular` | VARCHAR(20) | — |
| `email` | VARCHAR(100) | — |
| `ocupacion`, `lugar_trabajo` | VARCHAR | — |
| `direccion` | TEXT | — |

---

### `estudiante_padre` — Pivot estudiante ↔ padre

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `estudiante_id` | INT UNSIGNED | FK → estudiantes CASCADE |
| `padre_id` | INT UNSIGNED | FK → padres CASCADE |
| `es_principal` | TINYINT(1) | Contacto principal |

**UNIQUE:** (`estudiante_id, padre_id`)

---

### `matriculas` — Matrícula por año escolar

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `institucion_id` | INT UNSIGNED | FK → instituciones CASCADE |
| `estudiante_id` | INT UNSIGNED | FK → estudiantes CASCADE |
| `ano_escolar_id` | INT UNSIGNED | FK → anos_escolares CASCADE |
| `seccion_id` | INT UNSIGNED | FK → secciones CASCADE |
| `fecha_matricula` | DATE | — |
| `numero_matricula` | VARCHAR(20) | — |
| `estado` | ENUM('activa','retirada','graduado','trasladado') | Default: 'activa' |
| `repitente` | TINYINT(1) | Default: 0 |
| `observaciones` | TEXT | — |

**UNIQUE:** (`institucion_id, estudiante_id, ano_escolar_id`) — un estudiante solo puede estar matriculado una vez por año.

---

### `preinscripciones` — Solicitudes públicas de inscripción

Tabla de 40+ columnas que almacena la solicitud completa: datos del estudiante, datos del tutor, documentos requeridos y estado del proceso.

**Campos clave:**

| Columna | Tipo | Notas |
|---------|------|-------|
| `codigo_solicitud` | VARCHAR(20) | UNIQUE — `PRE-2026-0001` |
| `estado` | ENUM('pendiente','en_revision','aprobada','rechazada','convertida') | — |
| `grado_id` | INT UNSIGNED | FK → grados SET NULL |
| `doc_foto`, `doc_acta_nacimiento`, `doc_cedula_tutor`, `doc_cert_medico`, `doc_tarjeta_vacuna` | VARCHAR(255) | OBLIGATORIOS |
| `doc_notas_anteriores`, `doc_carta_saldo`, `doc_sigerd` | VARCHAR(255) | Opcionales |
| `revisado_por` | INT UNSIGNED | FK → usuarios SET NULL |
| `estudiante_id` | INT UNSIGNED | FK → estudiantes SET NULL — enlace al conv |

---

### `preregistro_colegios` — Solicitudes de nuevos colegios

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `nombre`, `tipo`, `email`, `telefono` | Varios | Datos del colegio |
| `nombre_director`, `cargo_contacto` | VARCHAR | Contacto principal |
| `cant_estudiantes` | SMALLINT | Estimado |
| `plan_interes` | INT UNSIGNED | FK → planes SET NULL |
| `estado` | ENUM('pendiente','aprobado','rechazado','contactado') | Default: 'pendiente' |
| `institucion_id` | INT UNSIGNED | FK → instituciones SET NULL — enlace al aprobar |
| `revisado_por`, `revisado_en` | INT / DATETIME | — |

---

### `calificaciones` — Notas académicas

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT UNSIGNED PK | — |
| `matricula_id` | INT UNSIGNED | FK → matriculas CASCADE |
| `asignatura_id` | INT UNSIGNED | FK → asignaturas CASCADE |
| `periodo_id` | INT UNSIGNED | FK → periodos CASCADE |
| `calificacion` | DECIMAL(5,2) | Escala 0-100 |
| `observaciones` | TEXT | — |
| `registrado_por` | INT UNSIGNED | FK → profesores SET NULL |

**UNIQUE:** (`matricula_id, asignatura_id, periodo_id`)  
**⚠️ HALLAZGO CRÍTICO:** Esta tabla no tiene `ENGINE=InnoDB` explícito — única tabla del schema sin ENGINE definido. Puede usar MyISAM u otro motor por defecto. Ver hallazgos.

---

### `asistencias` — Asistencia diaria

| Columna | Tipo | Notas |
|---------|------|-------|
| `matricula_id` | INT UNSIGNED | FK → matriculas CASCADE |
| `fecha` | DATE | — |
| `estado` | ENUM('presente','ausente','tardanza','justificada') | — |
| `registrado_por` | INT UNSIGNED | FK → profesores SET NULL |

**UNIQUE:** (`matricula_id, fecha`) — un registro de asistencia por alumno por día.

---

### `comunicados` — Mensajes a la comunidad

| Columna | Tipo | Notas |
|---------|------|-------|
| `tipo` | ENUM('general','curso','individual') | — |
| `prioridad` | ENUM('normal','alta','urgente') | Default: 'normal' |
| `enviado_por` | INT UNSIGNED | FK → usuarios CASCADE |

### `comunicado_destinatarios` — Estado de lectura

**UNIQUE:** (`comunicado_id, usuario_id`)  
Campos: `leido TINYINT(1)`, `fecha_lectura TIMESTAMP NULL`

---

### `asignaturas` — Materias del colegio

| Columna | Tipo | Notas |
|---------|------|-------|
| `color` | VARCHAR(7) | Hex para UI |
| `codigo` | VARCHAR(20) | Código de la materia |

### `horarios` — Carga horaria

Cruza `seccion_id + asignatura_id + profesor_id + dia_semana + hora_inicio/fin + aula`.

### `seccion_asignatura` — Pivot sección ↔ asignatura ↔ profesor

**UNIQUE:** (`seccion_id, asignatura_id`)

---

### `conceptos_pago` — Tipos de cobro del colegio

Ej: Matrícula, Mensualidad, Uniforme. Campo `recurrente` para pagos mensuales.

### `cuotas` — Cuotas generadas por matrícula

Vincula `matricula_id + concepto_pago_id`. Estados: `pendiente/pagada/vencida/cancelada`.

### `pagos` — Pagos registrados contra cuotas

**UNIQUE:** (`institucion_id, numero_recibo`)

---

### `log_actividad` — Auditoría de acciones

Tabla de `bigint` (no `int`) — espera alto volumen. Snapshots de `usuario_nombre` para sobrevivir borrados. Campos `entidad_tipo + entidad_id` como referencia polimórfica.

### `log_estado_instituciones` — Historial de cambios de estado

Acciones: `activada/suspendida/cancelada/reactivada/plan_cambiado`.

### `cron_log` — Log de tareas programadas

Resultados: `ok/error/sin_trabajo`. Contadores `enviados` y `errores`.

### `notificaciones_email` — Log de emails enviados

11 tipos de email. Estado: `enviado/error/pendiente`. `enviado_por NULL` = envío automático por cron.

---

### `secuencias_codigo` y `secuencias_preinscripcion`

Tablas de contador por institución. `PK = institucion_id` (no autoincrement) — solo hay un registro por colegio con el último número usado. Estrategia para evitar gaps en códigos sin race conditions a nivel de aplicación.

**Nota BUG-M-02:** Esta misma estrategia debería aplicarse a `pagos_saas.numero_factura`.

---

### `niveles` — Niveles educativos globales (seed)

| id | nombre | orden |
|----|--------|-------|
| 1 | Inicial | 1 |
| 2 | Primaria | 2 |
| 3 | Secundaria | 3 |

**⚠️ HALLAZGO:** `grados.nivel` es ENUM directo, no FK a esta tabla. Redundancia no referenciada. Ver hallazgos.

---

## 🔗 Diagrama de relaciones (texto)

```
instituciones (1) ─── (N) suscripciones ─── (N) pagos_saas
     │
     ├── (N) usuarios ─── (1) roles
     ├── (N) anos_escolares ─── (N) periodos
     │        └── (N) secciones ─── (N) horarios
     │                   │                └── (1) asignaturas
     │                   └── (N) matriculas ─── (N) calificaciones
     │                              │         └── (N) asistencias
     │                              └── (N) cuotas ─── (N) pagos
     ├── (N) grados
     ├── (N) estudiantes ─── (N) tutores [embedded, sin usuario]
     │        └── (pivot) estudiante_padre ─── (N) padres ─── usuario?
     ├── (N) preinscripciones
     ├── (N) asignaturas
     ├── (N) comunicados ─── (N) comunicado_destinatarios
     ├── (N) conceptos_pago
     ├── (N) log_estado_instituciones
     └── (N) notificaciones_email

[Global / sin tenant]
roles, planes, niveles, configuracion_sistema, log_actividad, cron_log
secuencias_codigo (por institución pero separada)
secuencias_preinscripcion (por institución pero separada)
```

---

## 🚨 Hallazgos de auditoría del schema

### Hallazgo #1 — Conteo de tablas incorrecto en la bitácora
**Impacto:** Documentación  
La bitácora y `ARQUITECTURA.md` indicaban 33 tablas. El schema real tiene **36 tablas**:
- Las 3 tablas no contadas: `padres`, `estudiante_padre`, `niveles`
- Acción: actualizar referencias en bitácora y `ARQUITECTURA.md`.

### Hallazgo #2 — Tabla `calificaciones` sin ENGINE=InnoDB ⚠️
**Impacto:** Integridad referencial  
Es la **única tabla sin `ENGINE=InnoDB` explícito**. Si el servidor tiene otro motor por defecto (MyISAM), las Foreign Keys no se aplicarán — `calificaciones` podría tener referencias huérfanas a `matriculas`, `asignaturas` y `periodos` sin que MariaDB lo impida.  
**Fix:** `ALTER TABLE calificaciones ENGINE=InnoDB;`  
**ID nuevo:** BUG-BD-01

### Hallazgo #3 — Coexistencia de `tutores` y `padres` (ADR-010 confirmado) ⚠️
**Impacto:** Diseño / ADR pendiente  
Existen dos sistemas paralelos para representar adultos relacionados con un estudiante:
- `tutores`: FK directo a `estudiantes`, sin `usuario_id` (no pueden hacer login)
- `padres` + `estudiante_padre`: tiene `usuario_id`, puede tener cuenta en el sistema

El módulo de preinscripciones guarda datos del tutor en `preinscripciones` directamente (no en `tutores`). El módulo de estudiantes usa `tutores`.  
**ADR-010 debe resolverse antes del Sprint del Portal Padres (Fase 5).**

### Hallazgo #4 — `grados.nivel` ENUM no referencia tabla `niveles` ⚠️
**Impacto:** Redundancia de datos  
`niveles` existe como tabla con seed data, pero `grados` tiene `nivel ENUM('inicial','primario','secundario')` directo. No hay FK. Si se agrega un nivel nuevo, hay que modificar el ENUM.  
**Recomendación:** Para el Sprint 2.2 (Profesores) decidir si se migra a FK o se documenta como intencional.  
**ID nuevo:** MEJ-BD-01

### Hallazgo #5 — `secciones` tiene dos columnas de capacidad con defaults inconsistentes ⚠️
**Impacto:** Lógica de negocio ambigua  
`capacidad SMALLINT DEFAULT 40` y `capacidad_maxima INT DEFAULT 30`. Los defaults son contradictorios (máximo < capacidad). Además, ambas columnas parecen representar lo mismo.  
**Acción:** Definir cuál se usa, eliminar la redundante.  
**ID nuevo:** MEJ-BD-02

### Hallazgo #6 — `roles.permisos` JSON siempre NULL
**Impacto:** Funcionalidad no implementada  
El sistema de permisos granulares está anticipado en el schema pero todos los roles tienen `permisos = NULL`. El control de acceso actual se basa solo en `rol_id`. Documentar como Fase futura.

### Hallazgo #7 — Estrategia de secuencias correcta en `secuencias_codigo`
**Positivo** — Las tablas `secuencias_codigo` y `secuencias_preinscripcion` usan `PK = institucion_id` para generar números correlativos sin gaps. Esta misma estrategia debería aplicarse a `pagos_saas.numero_factura` (BUG-M-02).

### Hallazgo #8 — `suscripciones` sin `fecha_baja` (confirma ADR-012)
Ya documentado. Migración pendiente antes del deploy.

### Hallazgo #9 — `anos_escolares` sin UNIQUE constraint (confirma BUG-4)
Ya documentado. Migración pendiente.

### Hallazgo #10 — `usuarios` sin `debe_cambiar_password` (confirma ADR-013)
Ya documentado. Migración pendiente.

---

## 📋 Migraciones pendientes

```sql
-- 1. BUG-BD-01: Tabla calificaciones sin InnoDB
ALTER TABLE calificaciones ENGINE=InnoDB;

-- 2. BUG-4: UNIQUE en anos_escolares
ALTER TABLE anos_escolares
  ADD UNIQUE KEY uq_colegio_nombre (institucion_id, nombre);

-- 3. ADR-012: fecha_baja en suscripciones
ALTER TABLE suscripciones
  ADD COLUMN fecha_baja DATETIME NULL DEFAULT NULL
  COMMENT 'Fecha de cancelación o baja — para Churn Rate preciso';

-- 4. ADR-013: debe_cambiar_password en usuarios
ALTER TABLE usuarios
  ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'Forzar cambio de contraseña en próximo login';
```

> ⚠️ Ejecutar siempre con backup previo. Documentar cada ejecución en `database/migrations/`.

---

## 📊 Resumen del schema

| Métrica | Valor |
|---------|-------|
| Total tablas | 36 |
| Tablas con datos seed | 5 (configuracion_sistema, planes, roles, niveles, usuarios) |
| Tablas de pivote | 3 (estudiante_padre, seccion_asignatura, comunicado_destinatarios) |
| Tablas de secuencia | 2 (secuencias_codigo, secuencias_preinscripcion) |
| Tablas de log/auditoría | 3 (log_actividad, log_estado_instituciones, cron_log) |
| Tablas implementadas | ~22 |
| Tablas en schema pero vacías (Fases futuras) | ~14 |
| Motor BD | InnoDB (excepto calificaciones — BUG-BD-01) |
| Charset global | utf8mb4_unicode_ci |
| Collation excepciones | secuencias_* usan utf8mb4_general_ci |

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Basado en: `database/edusaas_rd.sql` — phpMyAdmin dump 5.2.1*  
*Ver también: `DEUDA_TECNICA.md` · `ADR.md` · `PLAN_CORRECCIONES.md`*
