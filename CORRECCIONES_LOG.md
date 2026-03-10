# 🛠️ CORRECCIONES_LOG — EduSaaS RD

**Propósito:** Tracker de ejecución en tiempo real de las correcciones del Code Review.  
**Creado:** 2026-03-09  
**Última actualización:** 2026-03-09  
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
| **0** | Acciones inmediatas (manual) | 1 crítico | ⏳ Pendiente | — |
| **1** | Infraestructura crítica | 5 críticos | ⏳ Pendiente | — |
| **2** | Config y variables de entorno | 6 medios | ⏳ Pendiente | — |
| **3** | Branding y layout global | 5 medios | ⏳ Pendiente | — |
| **4** | XSS sistémico | ~18 medios | ⏳ Pendiente | — |
| **5** | BD fuera de vistas | 7 medios | ⏳ Pendiente | — |
| **6** | CSRF y métodos HTTP | 5 medios | ⏳ Pendiente | — |
| **7** | Models y Services | 12 medios | ⏳ Pendiente | — |
| **8** | Migraciones de BD | 4 items | ⏳ Pendiente | — |
| **9** | Onboarding seguro | 3 medios | ⏳ Pendiente | — |
| **10** | Calidad y consistencia | ~42 mejoras | ⏳ Futuro | — |

**Para deploy mínimo seguro: Oleadas 0 → 4**

---

## ✅ FIXES APLICADOS

> *Vacío — sin correcciones aplicadas aún.*

---

## 🔄 EN CURSO

> *Ninguno — próxima sesión arranca con Oleada 0 (acciones manuales) + Oleada 1.*

---

## ⏳ COLA — OLEADA 0 (manual, sin código)

> Estas acciones las ejecuta el developer directamente, sin subir archivos.

| # | Acción | Estado |
|---|--------|--------|
| 0.1 | Revocar App Password en `myaccount.google.com → Seguridad` | ⏳ |
| 0.2 | Generar nueva App Password y actualizar `.env` local | ⏳ |
| 0.3 | Verificar que `.env` está en `.gitignore` | ⏳ |
| 0.4 | Hacer backup/commit con tag `pre-fixes` | ⏳ |

---

## ⏳ COLA — OLEADA 1 (próxima sesión)

> Prerequisito: Oleada 0 completada. Subir archivos de a 1-3 por ronda.

| # | Bug ID | Archivo a subir | Acción | Estado |
|---|--------|----------------|--------|--------|
| 1.1 | BUG-CRÍTICO-1 | *(renombrar manualmente)* | `AnoescolarMode.php` → `AnoEscolarModel.php` + grep referencias | ⏳ |
| 1.2 | ADR-016 | `config/constants.php` | Agregar `define('APP_URL', ...)` | ⏳ |
| 1.2b | ADR-016 | `views/layouts/main.php` | Reemplazar `require app.php` con `APP_URL` | ⏳ |
| 1.2c | ADR-016 | `views/auth/login.php` | Reemplazar `require app.php` con `APP_URL` | ⏳ |
| 1.2d | ADR-016 | `views/emails/bienvenida.php` | Pasar `$appUrl` desde `EmailService` | ⏳ |
| 1.2e | ADR-016 | `public/forms/preinscripcion/formulario.php` | Reemplazar `require app.php` con `APP_URL` | ⏳ |
| 1.2f | ADR-016 | `public/forms/preregistro/formulario.php` | Reemplazar `require app.php` con `APP_URL` | ⏳ |
| 1.3 | BUG-V-01 | `views/superadmin/cobros/formulario_partial.php` | `$this->generateCsrfToken()` → `$csrf_token` | ⏳ |
| 1.4 | BUG-V-15 | `views/superadmin/notificaciones/index.php` | Eliminar `value` de input SMTP password | ⏳ |
| 1.5 | BUG-B9-02 | `views/layouts/main.php` | Restaurar URL real, eliminar script Cloudflare | ⏳ |

---

## 📅 LOG DE SESIONES DE CORRECCIÓN

### Sesión C-001 — 2026-03-09
**Tipo:** Planificación  
**Trabajado:**
- Creación de `CORRECCIONES_LOG.md`
- Corrección de `BITACORA.md` (encabezado + tabla resumen desactualizados → estado real 100% Code Review)
- Protocolo de trabajo por sesión definido: máx. 3 archivos/ronda, flujo fijo

**Decisiones:**
- Se crea `CORRECCIONES_LOG.md` como tercer documento de seguimiento (complementa Bitácora + Plan)
- Oleadas 0→4 son prerequisito para cualquier deploy

**Para la próxima sesión (C-002):**
1. Confirmar que Oleada 0 está hecha (acciones manuales del developer)
2. Arrancar Oleada 1 — subir primero: `constants.php` + `formulario_partial.php` + `notificaciones/index.php`
3. Fix 1.1 (renombrar `AnoescolarMode.php`) se hace manualmente antes de la sesión

---

*Tracker generado: 2026-03-09 — EduSaaS RD*  
*Actualizar en cada sesión de correcciones*
