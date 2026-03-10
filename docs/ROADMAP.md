# 🗺️ ROADMAP — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Estado general:** 🟡 Fase 1 casi completa — iniciando correcciones antes de Fase 2.2

---

## 🧭 Visión del producto

EduSaaS RD será la plataforma SaaS de gestión académica de referencia para instituciones educativas en República Dominicana, cubriendo el ciclo completo: desde la preinscripción pública hasta los reportes finales de calificaciones, con portales independientes para administradores, profesores, alumnos y padres.

---

## 📊 Estado actual por fase

```
Fase 1   — SaaS / SuperAdmin         ████████████████████  ~95% ✅
Fase 2.1 — Config académica colegio   ████████████████████  100% ✅
Fase 2.2 — Profesores                 ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 2.3 — Matrículas                 ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 3.1 — Asignaturas                ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 3.2 — Horarios                   ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 3.3 — Asistencia                 ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 3.4 — Calificaciones             ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 3.5 — Comunicados                ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 4   — Pagos del colegio          ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 5   — Portales externos          ░░░░░░░░░░░░░░░░░░░░    0% ⏳
Fase 6   — Reportes y exportaciones   ░░░░░░░░░░░░░░░░░░░░    0% ⏳
```

---

## 🔧 PRERREQUISITO — Corrección de deuda técnica

> **Antes de continuar con cualquier fase de desarrollo**, completar las Oleadas 0–4 del `PLAN_CORRECCIONES.md`. Construir sobre bugs críticos activos multiplica el costo de corrección.

| Oleada | Contenido | Sesiones est. |
|--------|-----------|---------------|
| 0 | Revocar credencial expuesta | Inmediata |
| 1 | Infraestructura crítica (5 bugs fatales) | 1 |
| 2 | Config y variables de entorno | 1 |
| 3 | Layout global y branding | 1 |
| 4 | XSS sistémico (htmlspecialchars / json_encode) | 1-2 |

**Total estimado antes de retomar features:** ~4-5 sesiones

---

## ✅ FASE 1 — Infraestructura SaaS y Panel SuperAdmin

**Estado:** ~95% completo  
**Objetivo:** EduSaaS RD puede operar como operador SaaS — vender planes, gestionar colegios, cobrar suscripciones.

### Completado
- [x] Autenticación — login, logout, bloqueo por intentos
- [x] Modo Visor — superadmin ve panel de cualquier colegio
- [x] Dashboard SuperAdmin — KPIs SaaS (MRR, ARR, Churn, retención)
- [x] Gestión de Instituciones — CRUD completo con estados y notas
- [x] Gestión de Planes — 4 planes con módulos configurables
- [x] Gestión de Suscripciones y Cobros — manual, con recibos
- [x] Gestión de Usuarios SuperAdmin
- [x] Pre-registros de Colegios — portal público + revisión + aprobación
- [x] Notificaciones y email SMTP personalizable
- [x] Configuración del sistema — marca, empresa, facturación
- [x] Panel de Salud — cron logs, KPIs técnicos, usuarios bloqueados
- [x] Log de Actividad — auditoría de acciones
- [x] Cron jobs — avisos de vencimiento, chequeos automáticos
- [x] SmtpClient propio — Gmail, Outlook, Brevo

### Pendiente (5% restante)
- [ ] Correcciones de la deuda técnica identificada (Oleadas 0–7)
- [ ] Migraciones de BD pendientes (Oleada 8)
- [ ] Verificar que `numero_factura` sea realmente único bajo carga (BUG-M-02)

---

## ✅ FASE 2.1 — Configuración Académica del Colegio

**Estado:** 100% completo  
**Objetivo:** El admin del colegio puede configurar la estructura académica.

### Completado
- [x] Años Escolares — CRUD con activación
- [x] Grados — seed MINERD + gestión por colegio
- [x] Secciones — por grado y año escolar
- [x] Períodos de evaluación — por año escolar

---

## ⏳ FASE 2.2 — Módulo de Profesores

**Estado:** 0% — Próximo sprint  
**Prerequisito:** Oleadas 0–4 completadas + MEJ-BD-01 resuelto (decidir `grados.nivel` vs `niveles`)  
**Tablas involucradas:** `profesores` (existe), `usuarios` (ya existe), `secuencias_codigo`

### Alcance
- [ ] `ProfesorModel` — CRUD, generación de código, búsqueda
- [ ] `ProfesorController` — listar, crear, editar, activar/desactivar
- [ ] Vistas: `profesores/index.php`, `profesores/form.php`, `profesores/ver.php`
- [ ] Asignación de profesor a secciones/asignaturas (tabla `seccion_asignatura`)
- [ ] Límite por plan (`max_profesores` de `planes`)
- [ ] Usuario automático al crear profesor (rol=3 `profesor`)
- [ ] Email de bienvenida al profesor

### Decisiones previas necesarias
- ⚠️ `MEJ-BD-01`: ¿`grados.nivel` migra a FK en `niveles` o se mantiene ENUM?
- ⚠️ Convención de rutas POST (ADR-011): `POST /admin/profesores/crear`

### Estimación
2-3 sesiones de desarrollo

---

## ⏳ FASE 2.3 — Módulo de Matrículas

**Estado:** 0%  
**Prerequisito:** Fase 2.2 completa (las matrículas asignan sección, que tiene profesores)  
**Tablas involucradas:** `matriculas` (existe), `secuencias_codigo`

### Alcance
- [ ] `MatriculaModel` — CRUD, número de matrícula, estados
- [ ] `MatriculaController` — nueva matrícula, cambio de sección, retiro
- [ ] Vistas: lista de matriculados por sección, ficha de matrícula
- [ ] Validación de capacidad de sección (`MEJ-BD-02` debe estar resuelto)
- [ ] Restricción de matrícula duplicada por año (UNIQUE ya está en BD)
- [ ] Matrícula inicial al crear estudiante (ya existe flujo parcial)
- [ ] Historial de matrículas por estudiante

### Decisiones previas necesarias
- ⚠️ `MEJ-BD-02`: Resolver `capacidad` vs `capacidad_maxima` en `secciones`

### Estimación
2-3 sesiones de desarrollo

---

## ⏳ FASE 3 — Módulo Académico Central

> Las sub-fases 3.1–3.5 tienen dependencias encadenadas. Respetar el orden.

### FASE 3.1 — Asignaturas

**Prerequisito:** Fase 2.2 (los profesores dictan asignaturas)  
**Tablas:** `asignaturas`, `seccion_asignatura`

- [ ] `AsignaturaModel` + `AsignaturaController`
- [ ] CRUD de materias por colegio con color e ícono
- [ ] Asignación de asignaturas a secciones con profesor responsable
- [ ] Vista de carga horaria por sección

**Estimación:** 1-2 sesiones

---

### FASE 3.2 — Horarios

**Prerequisito:** Fase 3.1 (necesita sección + asignatura + profesor)  
**Tablas:** `horarios`

- [ ] `HorarioModel` + `HorarioController`
- [ ] Grilla visual por sección (lunes–viernes × bloques horarios)
- [ ] Validación de conflictos (mismo profesor en dos lugares a la vez)
- [ ] Vista de horario para el profesor

**Estimación:** 2-3 sesiones (la grilla visual es compleja)

---

### FASE 3.3 — Asistencia

**Prerequisito:** Fase 2.3 (necesita `matricula_id`)  
**Tablas:** `asistencias`

- [ ] `AsistenciaModel` + `AsistenciaController`
- [ ] Registro diario por sección (lista de alumnos + estado)
- [ ] UNIQUE `(matricula_id, fecha)` ya garantiza un registro por día
- [ ] Reporte de asistencia por alumno y por período
- [ ] Justificación de ausencias

**Estimación:** 2 sesiones

---

### FASE 3.4 — Calificaciones

**Prerequisito:** Fases 3.1 + 2.3 (necesita asignatura + matrícula + período)  
**Tablas:** `calificaciones` (⚠️ BUG-BD-01 debe estar corregido antes)

- [ ] `CalificacionModel` + `CalificacionController`
- [ ] Ingreso de notas por período (grilla alumno × asignatura)
- [ ] UNIQUE `(matricula_id, asignatura_id, periodo_id)` ya en BD
- [ ] Cálculo de promedio por período y anual
- [ ] Boletín de calificaciones individual (PDF)
- [ ] Validación escala 0–100

**Estimación:** 3-4 sesiones (el boletín PDF es trabajo extra)

---

### FASE 3.5 — Comunicados

**Prerequisito:** Fase 2.2 (requiere usuarios de tipo profesor/padre/alumno)  
**Tablas:** `comunicados`, `comunicado_destinatarios`

- [ ] `ComunicadoModel` + `ComunicadoController`
- [ ] Tipos: general (toda la institución), curso (una sección), individual
- [ ] Prioridades: normal, alta, urgente
- [ ] Estado de lectura por destinatario
- [ ] Módulo protegido por plan (`incluye_comunicados` en `planes`)

**Estimación:** 2 sesiones

---

## ⏳ FASE 4 — Pagos del Colegio (Alumnos)

**Prerequisito:** Fase 2.3 (matrículas generan cuotas)  
**Tablas:** `conceptos_pago`, `cuotas`, `pagos`

### Alcance
- [ ] `ConceptoPagoModel` + `CuotaModel` + `PagoColegioModel`
- [ ] Configuración de conceptos de pago por colegio (matrícula, mensualidad, etc.)
- [ ] Generación automática de cuotas al matricular
- [ ] Registro de pagos con recibo imprimible
- [ ] Estado de cuenta por alumno
- [ ] Reporte de morosos
- [ ] Módulo protegido por plan (`incluye_pagos` en `planes`)

### Decisión pendiente (ADR-010)
La relación entre `tutores` y `padres` debe estar resuelta antes — el responsable de pagos (`es_responsable=1` en `tutores`) debe poder recibir notificaciones de cuotas vencidas.

**Estimación:** 3-4 sesiones

---

## ⏳ FASE 5 — Portales Externos

**Prerequisito:** Fases 3 y 4 completas (sin datos no hay portal útil)  
**ADR-010 DEBE estar resuelto antes de esta fase**

### 5.1 — Portal del Profesor

- [ ] Dashboard con mis secciones y horario del día
- [ ] Registro de asistencia desde su vista
- [ ] Ingreso de calificaciones de sus asignaturas
- [ ] Visualización de comunicados
- [ ] Vistas: `views/colegio/maestro/` (carpeta vacía actualmente)

**Estimación:** 3-4 sesiones

---

### 5.2 — Portal del Alumno

- [ ] Dashboard con horario, calificaciones recientes, comunicados
- [ ] Historial de asistencia
- [ ] Boletín de notas
- [ ] Vistas: `views/colegio/alumno/` (carpeta vacía actualmente)

**Estimación:** 2-3 sesiones

---

### 5.3 — Portal del Padre/Tutor

- [ ] Vista de cada hijo matriculado
- [ ] Calificaciones y asistencia de sus hijos
- [ ] Estado de cuenta (pagos pendientes)
- [ ] Comunicados recibidos con confirmación de lectura
- [ ] Vistas: `views/colegio/padre/` (carpeta vacía actualmente)

**Decisión de arquitectura requerida (ADR-010):**
- ¿Los padres usan la tabla `padres` (tiene `usuario_id`) o la tabla `tutores` (sin login)?
- Definir relación entre `padres` y `tutores` — ¿son lo mismo o entidades distintas?

**Estimación:** 3-4 sesiones (después de ADR-010)

---

## ⏳ FASE 6 — Reportes y Exportaciones

**Prerequisito:** Fases 3 y 4 completas  
**Módulo protegido por plan:** `incluye_reportes` en `planes`

### Alcance
- [ ] Boletín de calificaciones PDF por alumno
- [ ] Nómina de estudiantes matriculados (Excel/PDF)
- [ ] Reporte de asistencia mensual por sección
- [ ] Reporte de morosos (pagos vencidos)
- [ ] Reporte de rendimiento académico por grado/sección
- [ ] Exportación de datos (CSV) para MINERD
- [ ] Carpeta `views/colegio/admin/reportes/` (vacía actualmente)

**Estimación:** 4-6 sesiones (PDF y Excel requieren librerías o implementación propia)

---

## 🔮 FASE 7 — Futuro / Post-MVP

Estas funcionalidades no están en el schema actual y requerirían diseño nuevo:

- **API REST** — acceso programático para integraciones externas (`incluye_api` ya está en planes)
- **App móvil** — consumiría la API de Fase 7
- **Integración SIGERD/MINERD** — reportes oficiales en formato ministerial
- **Pasarela de pagos** — CardNet, PayPal, Stripe para pagos online de cuotas
- **Multi-idioma** — soporte para haití/inglés en zonas turísticas
- **Notificaciones push** — WhatsApp Business API para comunicados urgentes
- **CSP y headers de seguridad avanzados** — `Content-Security-Policy`, `Referrer-Policy`
- **Dividir SuperAdminController** — en sub-controllers por dominio (MEJ-B7-03)

---

## 📅 Estimación de tiempos (referencial)

> Basado en el ritmo actual de ~2-3 sesiones por sprint pequeño.  
> Una "sesión" = ~2-3 horas de trabajo enfocado.

| Hito | Sesiones est. | Acumulado |
|------|--------------|-----------|
| Deuda técnica Oleadas 0–4 | 5-6 | 5-6 |
| Deuda técnica Oleadas 5–8 | 4-5 | ~11 |
| Fase 2.2 Profesores | 2-3 | ~14 |
| Fase 2.3 Matrículas | 2-3 | ~17 |
| Fase 3.1–3.2 Asignaturas + Horarios | 3-5 | ~22 |
| Fase 3.3–3.5 Asistencia + Calificaciones + Comunicados | 7-9 | ~31 |
| Fase 4 Pagos colegio | 3-4 | ~35 |
| Fase 5 Portales | 8-11 | ~46 |
| Fase 6 Reportes | 4-6 | ~52 |

**Para un sistema completamente funcional (Fases 1–6): ~50 sesiones de trabajo**  
**Para un MVP desplegable con valor real (Fases 1–3.4): ~30 sesiones**

---

## 🔑 Decisiones de arquitectura bloqueantes por fase

| Fase | ADR bloqueante | Estado |
|------|---------------|--------|
| 2.2+ | MEJ-BD-01: `grados.nivel` vs FK a `niveles` | ⏳ Pendiente |
| 2.3+ | MEJ-BD-02: `capacidad` vs `capacidad_maxima` en secciones | ⏳ Pendiente |
| 4+ | ADR-010: `tutores` vs `padres` — estructura del Portal Padres | ⏳ Pendiente |
| 5.3 | ADR-010 | ⏳ Pendiente (crítico aquí) |
| 8 | ADR-012: `fecha_baja` en suscripciones | ⏳ Migración pendiente |
| 8 | ADR-013: `debe_cambiar_password` en usuarios | ⏳ Migración pendiente |

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `PLAN_CORRECCIONES.md` · `DEUDA_TECNICA.md` · `ADR.md` · `SCHEMA_BD.md`*
