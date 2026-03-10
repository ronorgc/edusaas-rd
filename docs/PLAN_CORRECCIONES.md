# 📋 PLAN DE CORRECCIONES — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Basado en:** Code Review completo + Auditoría de schema — 117 archivos · 11 críticos · 53 medios · 80 mejoras  
**Estado:** ⏳ Pendiente ejecución

---

## 🧭 Principio de ejecución

Las correcciones se agrupan en **Oleadas** para maximizar el impacto por sesión y respetar dependencias entre fixes. Nunca corregir un síntoma antes que su causa raíz.

**Regla de oro:** Una oleada debe poder desplegarse y probarse de forma independiente.

---

## 🗺️ Mapa de dependencias críticas

```
BUG-CRÍTICO-2 (.env credencial)         → Acción manual, no tiene dependientes
BUG-CRÍTICO-1 (nombre archivo Linux)    → Bloquea toda carga de AnosEscolares
ADR-016 (APP_URL global)                → Resuelve: BUG-B9-01, BUG-B9-02*,
                                           BUG-B8c-01, BUG-B8d-F01, MEJ-B9-04
ADR-013 (debe_cambiar_password)         → Requiere migración BD
ADR-014 (password dinámica)             → Depende de ADR-013
ADR-012 (fecha_baja en suscripciones)   → Requiere migración BD
BUG-V-01 (CSRF partial)                 → Fix standalone en cobros
BUG-B9-03 (:root duplicado)             → Fix standalone en layout
BUG-B9-04 (query en sidebar)            → Depende de patrón BaseController
```

---

## 🔴 OLEADA 0 — Acciones inmediatas (ANTES de cualquier commit)

> Estas acciones son externas al código. No requieren sesión de desarrollo.

| # | Acción | Responsable | Tiempo |
|---|--------|-------------|--------|
| 0.1 | Ir a `myaccount.google.com → Seguridad → Contraseñas de aplicaciones` y **revocar** la App Password expuesta en `.env` | Developer | 5 min |
| 0.2 | Generar nueva App Password y actualizar `.env` local | Developer | 5 min |
| 0.3 | Verificar que `.env` está en `.gitignore` antes del primer commit | Developer | 2 min |
| 0.4 | Hacer backup/commit del estado actual con tag `pre-fixes` | Developer | 5 min |

**Criterio de salida:** `.env` sin credenciales reales en el repositorio.

---

## 🔴 OLEADA 1 — Fixes críticos de infraestructura (1 sesión)

> Corrige los bugs que causan errores fatales o comprometen credenciales.  
> Prerequisito: Oleada 0 completada.

### 1.1 Renombrar modelo con nombre incorrecto (BUG-CRÍTICO-1)

**Archivo:** `app/Models/AnoescolarMode.php`  
**Acción:** Renombrar a `AnoEscolarModel.php`  
**Impacto:** Fatal en Linux (case-sensitive). Todo lo relacionado con años escolares falla.  
**Pasos:**
```
1. Renombrar archivo: AnoescolarMode.php → AnoEscolarModel.php
2. Buscar todas las referencias en el proyecto:
   grep -r "AnoescolarMode\|AnoEscolarMode\|anoescolar" app/ views/ routes/
3. Actualizar cada require/use encontrado
4. Verificar que AdminController carga el modelo correctamente
```

### 1.2 Definir APP_URL global — resuelve 5 bugs de un golpe (ADR-016)

**Archivo:** `config/constants.php`  
**Acción:** Agregar `define('APP_URL', ...)` basado en el valor ya calculado en `app.php`

```php
// config/constants.php — agregar:
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('APP_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/edusaas-rd/public');
}
```

**Archivos a actualizar** (eliminar `require app.php` en cada uno):
- `views/layouts/main.php` — usar `APP_URL`
- `views/auth/login.php` — usar `APP_URL`
- `views/emails/bienvenida.php` — pasar `$appUrl` desde `EmailService`
- `public/forms/preinscripcion/formulario.php` — usar `APP_URL`
- `public/forms/preregistro/formulario.php` — usar `APP_URL`

**Bugs resueltos por este fix:** BUG-B9-01, BUG-B8c-01, BUG-B8d-F01, MEJ-B9-04

### 1.3 Corregir CSRF en partial de cobros (BUG-V-01)

**Archivo:** `views/superadmin/cobros/formulario_partial.php`  
**Acción:** Reemplazar `$this->generateCsrfToken()` por `<?= htmlspecialchars($csrf_token) ?>`  
**Nota:** Verificar que el controller pasa `$csrf_token` como variable.

### 1.4 Eliminar contraseña SMTP del DOM (BUG-V-15)

**Archivo:** `views/superadmin/notificaciones/index.php`  
**Acción:** En el input de contraseña SMTP, eliminar `value="<?= ... ?>"`.  
**Reemplazar por:** `placeholder="(guardada — dejar vacío para no cambiar)"`  
**Aplicar también a:** Cualquier otro campo de contraseña/credencial en el sistema (auditar con `grep -r "value.*password\|value.*pass\|value.*secret" views/`).

### 1.5 Corregir URL Cloudflare en layout (BUG-B9-02)

**Archivo:** `views/layouts/main.php`  
**Acción:** Localizar el botón "Contratar plan" con URL `/cdn-cgi/...` y restaurar `mailto:soporte@edusaas.do`. Eliminar el `<script data-cfasync>` asociado.

---

**Criterio de salida Oleada 1:** La aplicación carga sin errores fatales en Linux. Ninguna credencial visible en el DOM.

---

## 🟡 OLEADA 2 — Correcciones de configuración y variables de entorno (1 sesión)

> Fixes que afectan la portabilidad y correcto funcionamiento en producción.

| ID | Archivo | Acción |
|----|---------|--------|
| MEJORA-1 | `.env` | Alinear nombres: `DB_NAME` → `DB_DATABASE`, `DB_USER` → `DB_USERNAME` |
| MEJORA-2 | `config/app.php` | Cambiar `debug=true` → `debug=false` por defecto |
| MEJORA-3 | `config/constants.php` | Mover `CRON_SECRET` de constants a `.env` |
| MEJORA-4 | Auditar rutas de uploads | Unificar `$uploadPath` entre `app.php` y `EstudianteController` |
| MEJORA-5 | `public/index.php` | Agregar guardia en parser `.env` para líneas sin `=` |
| MEJORA-6 | `vendor/Database.php` | Loguear errores de conexión en producción (`error_log`) |
| BUG-B9-06 | `public/index.php` | Confirma MEJORA-5 — misma corrección |

**Criterio de salida:** `php -c php.ini public/index.php` sin warnings. Variables de entorno correctamente nombradas.

---

## 🟡 OLEADA 3 — Branding y layout global (1 sesión)

> Fixes en el layout principal que afectan a todos los usuarios.

| ID | Archivo | Acción |
|----|---------|--------|
| BUG-B9-03 | `views/layouts/main.php` | Fusionar el segundo bloque `:root` hardcodeado con el bloque dinámico de ConfigModel |
| BUG-B9-04 | `views/layouts/main.php` | Mover `SELECT COUNT(*) FROM preregistro_colegios` al `SuperAdminController`. Pasar `$preregistrosPendientes` |
| MEJ-B9-01 | `views/layouts/main.php` | Crear helper `flashHtml()` con whitelist de tags permitidos |
| MEJ-B9-02 | `views/layouts/main.php` | Mover `isActive()` a `BaseController::isActive()` |
| MEJ-B9-03 | `views/layouts/main.php` | Validar colores CSS inline del banner Trial con regex hex |

**Criterio de salida:** Branding dinámico funcionando. Sidebar no ejecuta queries propias.

---

## 🟡 OLEADA 4 — Seguridad XSS: `htmlspecialchars()` y `json_encode()` (1-2 sesiones)

> Corrección sistemática de output escaping. Muchos son cambios de una línea.

### Grupo A — `json_encode()` en JavaScript (evitar romper strings)

| ID | Archivo | Línea problemática |
|----|---------|-------------------|
| BUG-V-10 | `usuarios/index.php` | `confirmarEliminar()` con nombre interpolado |
| MEJ-V8-02 | `instituciones/usuarios.php` | `addslashes()` → `json_encode()` |
| MEJ-V8-07 | `planes/index.php` | `confirm()` con interpolación PHP |
| MEJ-V10-02 | `salud/index.php` | `confirm()` apostrofe desbloquear |
| MEJ-V11-01 | `preregistros/ver.php` | `confirm()` en botón rechazar |

### Grupo B — `htmlspecialchars()` faltante en atributos HTML

| ID | Archivo | Campo |
|----|---------|-------|
| BUG-V-04 | `instituciones/ver.php` | 3 campos de datos de la institución |
| BUG-V-12 | `configuracion/index.php` | Atributos `name`, `id`, `for` con `$clave` |
| MEJ-V9-04 | `usuarios/index.php` | Iniciales del avatar |
| BUG-B9-05 | `preregistro-formulario.php` | Flash messages |
| BUG-B9-06 | `partials/plan_uso.php` | `$_item['icono']` y `$_item['label']` |
| BUG-B8d-B01 | `preinscripcion-gracias.php` | `$inst['telefono']` |
| BUG-B8c-02 | `confirmacion_pago.php` | `$fechaPago`, `$fechaHasta` |
| BUG-B8c-03 | `confirmacion_pago.php` | `ucfirst($pago['metodo_pago'])` |
| BUG-B8d-F02 | `preinscripcion-formulario.php` | `$errors['general']` |
| BUG-B8d-F03 | `preinscripcion-formulario.php` | Closure `$err()` |

### Grupo C — IDs sin cast `(int)` en URLs (patrón sistémico)

Ejecutar búsqueda: `grep -rn "?id=<?=" views/` y agregar `(int)` en cada ocurrencia.  
Archivos confirmados: `ingresos.php`, `usuarios/index.php`, `preregistros/index.php` + B8b.

**Criterio de salida:** `grep -rn "echo \$" views/` produce 0 resultados sin `htmlspecialchars` o `json_encode` alrededor.

---

## 🟡 OLEADA 5 — Lógica de BD fuera de vistas (1 sesión)

> Mover queries y lógica de negocio que está en las vistas hacia controllers/models.

| ID | Vista afectada | Acción |
|----|---------------|--------|
| BUG-V-05 | `planes/index.php` | Mover JOIN a `PlanModel::getAllConConteo()`. Pasar `$plan['colegios_activos']` desde controller |
| BUG-V-11 | `configuracion/index.php` | Pasar valores de `ConfigModel::get()` desde el controller |
| BUG-V-14 | `notificaciones/index.php` | Mover `Database::getInstance()` + query de instituciones al controller |
| BUG-V-16 | `notificaciones/index.php` | Pasar `$mostrar_smtp` como variable desde controller |
| BUG-B8b-11 | `preinscripciones/index.php` (admin) | `Database::getInstance()` + `$_SESSION` en vista → controller |
| BUG-B9-04 | `layouts/main.php` | Ya cubierto en Oleada 3 |
| MEJ-B7-01 | `SuperAdminController::exportarInstituciones()` | Aplicar subconsulta del BUG-M-01 |

**Criterio de salida:** `grep -rn "Database::getInstance\|new.*Model\|SELECT\|INSERT\|UPDATE" views/` → 0 resultados.

---

## 🟡 OLEADA 6 — Seguridad: CSRF y métodos HTTP (1 sesión)

| ID | Descripción | Acción |
|----|-------------|--------|
| BUG-2 | Eliminar estudiante via GET | Cambiar a POST + token CSRF |
| BUG-B8b-04 | Desactivar estudiante via GET | Cambiar a POST + token CSRF |
| BUG-B8b-06 | `name="_csrf_token"` (underscore) en form estudiantes | Cambiar a `name="csrf_token"` y alinear con `BaseController::validateCsrf()` |
| BUG-B8b-13 | `name="_csrf_token"` en preinscripciones | Mismo fix |
| BUG-B8b-05 | Links dropdown sin prefijo `/admin/` | Agregar prefijo correcto a 3 links |

**Criterio de salida:** Ninguna acción destructiva (delete, toggle, suspend) accesible via GET.

---

## 🟡 OLEADA 7 — Correcciones en Models y Services (1 sesión)

| ID | Archivo | Acción |
|----|---------|--------|
| BUG-M-01 | `InstitucionModel::getAllConSuscripcion()` | Subconsulta con LIMIT 1 para evitar duplicados |
| BUG-M-02 | `PagoSaasModel::generarNumeroFactura()` | Usar ID del pago como correlativo |
| BUG-M-03 | `EstudianteModel::generarCodigo()` | Cambiar interpolación `$instId` a prepared statement |
| BUG-M-10 | `SuperAdminController::togglePlan()` | Cambiar interpolación `$id` a prepared statement |
| BUG-M-12 | `PreregistroController::generarSubdomain()` | Agregar `$maxIntentos = 100` al `while(true)` |
| BUG-M-05 | `SuscripcionMiddleware::bloquear()` | Pasar `$motivo` a vista `suspendido.php` |
| BUG-M-06 | `EmailService::__construct()` | Cambiar `require` sin `_once` → `require_once` |
| BUG-M-07 | `EmailService::notificarNuevoPreregistro()` | Reemplazar `rol_id = 1` por `ROL_SUPER_ADMIN` |
| BUG-M-09 | `AdminController::anosEscolares()` | Resolver N+1 con JOIN en `AnoEscolarModel` |
| BUG-M-11 | `SuperAdminController::suspenderInstitucion()` | Eliminar dead code (SELECT descartado) |
| BUG-M-13 | `PreinscripcionController::adminConvertir()` | Cambiar `require app.php` por `$this->config['url']` |
| MEJ-B7-05 | `AuthController::login()` | Agregar `error_log()` en `catch(Exception $ignored)` |

---

## 🔵 OLEADA 8 — Migraciones de base de datos (sesión dedicada + backup)

> ⚠️ Requiere backup completo de BD antes de ejecutar.  
> Definir ventana de mantenimiento si hay datos en producción.

| ID | Migración | SQL |
|----|-----------|-----|
| BUG-BD-01 | `calificaciones` sin InnoDB — FKs ignoradas | `ALTER TABLE calificaciones ENGINE=InnoDB;` |
| BUG-4 | `UNIQUE` en `anos_escolares` | `ALTER TABLE anos_escolares ADD UNIQUE KEY uq_colegio_nombre (institucion_id, nombre);` |
| ADR-012 | `fecha_baja` en `suscripciones` | `ALTER TABLE suscripciones ADD COLUMN fecha_baja DATETIME NULL DEFAULT NULL COMMENT 'Fecha de cancelación — para Churn Rate preciso';` |
| ADR-013 | `debe_cambiar_password` en `usuarios` | `ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Forzar cambio de contraseña en próximo login';` |

> **Nota:** BUG-BD-01 debe ejecutarse **primero** — no depende de datos existentes y corrige integridad referencial activa.

**Criterio de salida:** Migraciones documentadas en `database/migrations/` con timestamp.

---

## 🔵 OLEADA 9 — Contraseñas dinámicas y onboarding seguro (ADR-014)

> Depende de Oleada 8 (ADR-013 debe existir).

| Archivo | Acción |
|---------|--------|
| `SuperAdminController::crearInstitucion()` | Generar `$password_sugerida = 'Colegio' . rand(1000,9999) . '!'`. Pasar a vista. Setear `debe_cambiar_password = 1`. |
| `views/superadmin/instituciones/crear.php` | Usar `$password_sugerida` en lugar de `Colegio2024!` |
| `views/superadmin/preregistros/ver.php` | Usar `$password_sugerida` en formulario de aprobación |

---

## 🔵 OLEADA 10 — Mejoras de calidad y consistencia (sesiones futuras)

> No bloquean el lanzamiento. Reducen deuda técnica.

- `MEJ-B4-01` al `MEJ-B4-05` — BaseModel y ConfigModel
- `MEJ-B5-01` al `MEJ-B5-03` — Helpers y Middlewares
- `MEJ-B6-01` al `MEJ-B6-04` — EmailService y MetricasService
- `MEJ-B8c-01` al `MEJ-B8c-05` — Templates de email
- `MEJ-B8d-01` al `MEJ-B8d-F05` — Vistas públicas
- `MEJ-B9-01` al `MEJ-B9-06` — Layout principal
- `MEJ-B7-03` — Dividir `SuperAdminController` (2,443 líneas) en sub-controllers
- `MEJ-B7-04` — Mover helper `n()` a `BaseController::nullableStr()`
- `MEJORA-7` — Router 404 en lugar de `die()`
- `MEJORA-8` — SSL `verify_peer=true` en producción
- `MEJORA-9` — Unificar convención POST (`/crear` estándar — ADR-011)
- `BUG-B8d-B03` — Eliminar vista duplicada de confirmación preinscripción

---

## 📊 Resumen de oleadas

| Oleada | Nombre | Bugs resueltos | Sesiones est. | Bloquea deploy |
|--------|--------|---------------|---------------|----------------|
| 0 | Acciones inmediatas | 1 crítico | Inmediata | ✅ Sí |
| 1 | Infraestructura crítica | 4 críticos + 5 medios | 1 | ✅ Sí |
| 2 | Config y variables | 6 medios | 1 | ✅ Sí |
| 3 | Layout global | 5 medios | 1 | ⚠️ Parcial |
| 4 | XSS sistémico | ~18 medios | 1-2 | ⚠️ Parcial |
| 5 | BD fuera de vistas | 7 medios | 1 | ⚠️ Parcial |
| 6 | CSRF y HTTP | 5 medios | 1 | ⚠️ Parcial |
| 7 | Models y Services | 12 medios | 1 | 🔵 No |
| 8 | Migraciones BD | 1 crítico + 3 mejoras estructurales | 1 | 🔵 No |
| 9 | Onboarding seguro | 3 medios | 0.5 | 🔵 No |
| 10 | Calidad y consistencia | ~42 mejoras | Varias | 🔵 No |

**Para un deploy mínimo seguro: completar Oleadas 0 → 4** (aprox. 5-6 sesiones)

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `DEUDA_TECNICA.md` para tracker completo · `ADR.md` para decisiones técnicas*
