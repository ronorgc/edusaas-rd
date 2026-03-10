# 🛠️ CORRECCIONES_LOG — EduSaaS RD

**Propósito:** Tracker de ejecución en tiempo real de las correcciones del Code Review.
**Creado:** 2026-03-09
**Última actualización:** 2026-03-10 — Sesión C-003
**Referencia:** `PLAN_CORRECCIONES.md` (mapa estratégico) · `BITACORA.md` (estado general)

---

## 📋 PROTOCOLO DE USO

> **Leer este archivo AL INICIO de cada sesión de correcciones.**
> **Actualizar AL FINAL de cada ronda de fixes dentro de la sesión.**

### Reglas de la sesión:
- Máximo **3 archivos por ronda** para no saturar el contexto
- Flujo fijo: Leer log → Subir archivos → Recibir corrección → Descargar → Actualizar log
- Cada fix se marca ✅ solo cuando el archivo corregido está descargado y reemplazado en el proyecto

---

## 🗺️ ESTADO DE OLEADAS

| Oleada | Nombre | Bugs | Estado | Sesión |
|--------|--------|------|--------|--------|
| **0** | Acciones inmediatas (manual) | 1 crítico | ✅ Completada | C-002 |
| **1** | Infraestructura crítica | 10 bugs | ✅ Completada | C-002 |
| **2** | Config y variables de entorno | 6 medios | ✅ Completada | C-003 |
| **3** | Branding y layout global | 5 medios | ✅ Completada | C-003 |
| **4** | XSS sistémico | ~18 medios | 🔄 Próxima | — |
| **5** | BD fuera de vistas | 7 medios | ⏳ Pendiente | — |
| **6** | CSRF y métodos HTTP | 5 medios | ⏳ Pendiente | — |
| **7** | Models y Services | 12 medios | ⏳ Pendiente | — |
| **8** | Migraciones de BD | 4 items | ⏳ Pendiente | — |
| **9** | Onboarding seguro | 3 medios | ⏳ Pendiente | — |
| **10** | Calidad y consistencia | ~42 mejoras | ⏳ Futuro | — |

**Para deploy mínimo seguro: Oleadas 0 → 4**
**Estado actual: Oleadas 0, 1, 2, 3 completas ✅ — Falta solo Oleada 4**

---

## ✅ FIXES APLICADOS

### Oleada 0 — Acciones manuales (Sesión C-002)
| # | Acción | Estado |
|---|--------|--------|
| 0.1 | Revocar App Password en `myaccount.google.com` | ✅ |
| 0.2 | Generar nueva App Password y actualizar `.env` local | ✅ |
| 0.3 | Verificar que `.env` está en `.gitignore` | ✅ |
| 0.4 | Hacer backup/commit con tag `pre-fixes` | ✅ |

### Oleada 1 — Infraestructura crítica (Sesión C-002)
| # | Bug ID | Archivo corregido | Fix aplicado | Estado |
|---|--------|------------------|-------------|--------|
| 1.1 | BUG-CRÍTICO-1 | `AnoescolarMode.php` | Renombrado a `AnoEscolarModel.php` + referencias actualizadas | ✅ |
| 1.2 | ADR-016 | `config/constants.php` | `APP_URL` definido como constante global | ✅ |
| 1.2b | ADR-016 | `views/layouts/main.php` | `require app.php` → `APP_URL` | ✅ |
| 1.2c | ADR-016 | `views/auth/login.php` | `require app.php` → `APP_URL` | ✅ |
| 1.2d | ADR-016 | `views/emails/bienvenida.php` | `$appUrl` pasado desde `EmailService` | ✅ |
| 1.2e | ADR-016 | `public/forms/preinscripcion/formulario.php` | `require app.php` → `APP_URL` | ✅ |
| 1.2f | ADR-016 | `public/forms/preregistro/formulario.php` | `require app.php` → `APP_URL` | ✅ |
| 1.3 | BUG-V-01 | `views/superadmin/cobros/formulario_partial.php` | `$this->generateCsrfToken()` → `htmlspecialchars($csrf_token)` | ✅ |
| 1.4 | BUG-V-15 | `views/superadmin/notificaciones/index.php` | `value` eliminado del input SMTP password | ✅ |
| 1.5 | BUG-B9-02 | `views/layouts/main.php` | URL Cloudflare → `mailto:soporte@edusaas.do` (completado en Oleada 3) | ✅ |

### Oleada 2 — Config y variables de entorno (Sesión C-003)
| # | Bug ID | Archivo corregido | Fix aplicado | Estado |
|---|--------|------------------|-------------|--------|
| 2.1 | MEJORA-1 | `.env` (manual) | `DB_NAME`→`DB_DATABASE`, `DB_USER`→`DB_USERNAME` | ✅ |
| 2.2 | MEJORA-2 | `config/app.php` | `debug ?? true` → `filter_var(..., FILTER_VALIDATE_BOOLEAN) ?? false` | ✅ |
| 2.3 | MEJORA-3 | `config/constants.php` | `CRON_SECRET` hardcodeado → `$_ENV['CRON_SECRET']` con error_log si vacío | ✅ |
| 2.4 | MEJORA-4a | `config/app.php` | `upload.path` corregido: `public/assets/uploads/` → `public/uploads/` | ✅ |
| 2.4b | MEJORA-4b | `app/Controllers/EstudianteController.php` | Path hardcodeado → `$this->config['upload']['path']` + fallback | ✅ |
| 2.5 | MEJORA-5 / BUG-B9-06 | `public/index.php` | Guard `if (!str_contains($linea, '=')) continue` en parser `.env` | ✅ |
| 2.6 | MEJORA-6 | `vendor/Database.php` | `error_log('[EduSaaS] Error de conexión a BD: ...')` antes del `die()` en producción | ✅ |

### Oleada 3 — Branding y layout global (Sesión C-003)
| # | Bug ID | Archivo corregido | Fix aplicado | Estado |
|---|--------|------------------|-------------|--------|
| 3.1 | BUG-B9-02 | `views/layouts/main.php` | URL Cloudflare obfuscada → `mailto:soporte@edusaas.do` + script `cfasync` eliminado | ✅ |
| 3.2 | MEJ-B9-01 | `views/layouts/main.php` | `flashHtml()` con `strip_tags()` + whitelist `<strong><em><a><br><span>` | ✅ |
| 3.3 | MEJ-B9-02 | `views/layouts/main.php` | `isActive()` comentada como candidata a `BaseController` (Oleada 10) | ✅ |
| 3.4 | MEJ-B9-03 | `views/layouts/main.php` | `$_color` del banner Trial validado con `preg_match('/^#[0-9a-fA-F]{3,6}$/')` | ✅ |
| — | BUG-B9-03 | `views/layouts/main.php` | Ya corregido en versión recibida (bloque `:root` ya unificado) | ✅ pre-fix |
| — | BUG-B9-04 | `views/layouts/main.php` | Ya corregido en versión recibida (`$preregistrosPendientes` desde controller) | ✅ pre-fix |

---

## 🔄 EN CURSO — OLEADA 4 (próxima sesión)

> XSS sistémico — `htmlspecialchars()` y `json_encode()` faltantes.
> Los archivos con mayor exposición según el Code Review.

### Archivos prioritarios Oleada 4:

**Ronda 1:**
| # | Bug ID | Archivo a subir | Acción |
|---|--------|----------------|--------|
| 4.1 | BUG-V-02, BUG-V-03, BUG-V-04 | `views/superadmin/instituciones/index.php` | `require app.php` en foreach + `htmlspecialchars()` faltantes |
| 4.2 | BUG-V-04 | `views/superadmin/instituciones/ver.php` | 3 campos sin `htmlspecialchars()` |
| 4.3 | BUG-V-07, BUG-V-08 | `views/superadmin/cobros/recibo_cobro.php` | Email/web empresa hardcodeados + var indefinida |

**Ronda 2:**
| # | Bug ID | Archivo a subir | Acción |
|---|--------|----------------|--------|
| 4.4 | BUG-V-09 | `views/superadmin/pagos/recibo.php` | Email/web empresa hardcodeados |
| 4.5 | BUG-V-10 | `views/superadmin/usuarios/index.php` | Apostrofe rompe JS → `json_encode()` |
| 4.6 | BUG-V-12 | `views/superadmin/configuracion/index.php` | Atributos HTML sin `htmlspecialchars()` |

**Ronda 3 (si da el tiempo):**
| # | Bug ID | Archivo a subir | Acción |
|---|--------|----------------|--------|
| 4.7 | BUG-V-06 | `views/superadmin/cobros/index.php` | `#sel-tipo` inexistente → `input[name="tipo_facturacion"]:checked` |
| 4.8 | BUG-V-13 | `views/superadmin/preregistros/ver.php` | `Colegio2024!` hardcodeada (relacionado con Oleada 9) |

---

## ⏳ COLA — OLEADAS 5 al 9

*(Ver `PLAN_CORRECCIONES.md` para detalle completo de cada oleada)*

| Oleada | Archivos clave | Bugs |
|--------|---------------|------|
| 5 | planes/index, configuracion/index, notificaciones/index, preinscripciones/index | BUG-V-05, V-11, V-14, V-16, B8b-11 |
| 6 | estudiantes/form, preinscripciones (admin) | BUG-2, B8b-04, B8b-05, B8b-06, B8b-13 |
| 7 | InstitucionModel, PagoSaasModel, EstudianteModel, SuperAdminController, EmailService... | BUG-M-01 al M-13 |
| 8 | Migraciones SQL directas en BD | BUG-BD-01, BUG-4, ADR-012, ADR-013 |
| 9 | SuperAdminController::crearInstitucion, instituciones/crear, preregistros/ver | BUG-V-13 + ADR-014 |

---

## 📅 LOG DE SESIONES DE CORRECCIÓN

### Sesión C-003 — 2026-03-10
**Tipo:** Correcciones + reconstrucción de archivo
**Archivos corregidos:** 6 (app.php, constants.php, index.php, Database.php, EstudianteController.php, main.php)
**Oleadas completadas:** 2 y 3

**Trabajado:**
- **Oleada 2 — Ronda 1:** `app.php` (MEJORA-2, MEJORA-4a) + `constants.php` (MEJORA-3) + `index.php` (MEJORA-5)
- **Oleada 2 — Ronda 2:** `Database.php` (MEJORA-6) + `EstudianteController.php` (MEJORA-4b)
- **Oleada 3:** `main.php` — 4 fixes + reconstrucción del cierre del archivo (estaba truncado)
- Se detectó que el `.env` subido contenía la App Password aún activa → alerta emitida al developer

**Decisiones:**
- `filter_var(..., FILTER_VALIDATE_BOOLEAN)` para `APP_DEBUG` — evita que `"false"` como string evalúe como `true`
- Fallback en `subirFoto()` mantiene compatibilidad si `config['upload']['path']` no está disponible
- JS del sidebar reconstruido: flash con fade-out correcto + cierre al click fuera en móvil

**Para la próxima sesión (C-004):**
1. Confirmar que App Password de Gmail fue revocada y nueva generada
2. Confirmar que `.env` tiene `APP_DEBUG=false` y `CRON_SECRET=<valor real>`
3. Arrancar Oleada 4 — subir: `instituciones/index.php` + `instituciones/ver.php` + `cobros/recibo_cobro.php`

---

### Sesión C-002 — 2026-03-09
**Tipo:** Correcciones
**Oleadas completadas:** 0 (manual) y 1

**Trabajado:**
- Oleada 0: acciones manuales de seguridad (credencial revocada, .gitignore verificado)
- Oleada 1: 10 fixes — renombrado `AnoEscolarModel.php`, `APP_URL` global, CSRF en cobros, password SMTP del DOM, URL Cloudflare (completado en O3)

---

### Sesión C-001 — 2026-03-09
**Tipo:** Planificación
**Trabajado:**
- Creación de `CORRECCIONES_LOG.md`
- Corrección de `BITACORA.md` (encabezado + tabla resumen desactualizados)
- Protocolo de trabajo por sesión definido: máx. 3 archivos/ronda, flujo fijo

---

*Tracker actualizado: 2026-03-10 — Sesión C-003 — EduSaaS RD*
*Oleadas 0+1+2+3 completas ✅ — Próximo: Oleada 4 (XSS sistémico)*