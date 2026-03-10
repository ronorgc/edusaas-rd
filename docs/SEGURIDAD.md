# 🔐 SEGURIDAD — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Basado en:** Code Review completo (117 archivos) + Auditoría de schema  
**Estado:** 🔴 NO LISTO PARA PRODUCCIÓN — 11 vulnerabilidades críticas/altas pendientes

---

## 📊 Resumen ejecutivo

| Categoría | Total | Resuelto | Pendiente |
|-----------|-------|----------|-----------|
| 🔴 Crítico / Alto | 11 | 0 | 11 |
| 🟡 Medio | 18 | 0 | 18 |
| 🔵 Bajo / Mejora | 12 | 0 | 12 |
| **TOTAL** | **41** | **0** | **41** |

> Los ítems de seguridad son un subconjunto de `DEUDA_TECNICA.md`.  
> Este archivo los agrupa por **tipo de vulnerabilidad** para facilitar auditorías y revisiones de seguridad.

---

## 🔴 VULNERABILIDADES CRÍTICAS / ALTAS

### SEC-01 — Credencial expuesta en control de versiones
**Tipo:** Credential Leak  
**OWASP:** A02:2021 – Cryptographic Failures  
**Archivo:** `.env`  
**ID deuda:** BUG-CRÍTICO-2  
**Ref. Oleada:** 0 (inmediata)

Google App Password real almacenada en texto plano en `.env`. Si este archivo llega a un repositorio Git (incluso privado), la credencial queda comprometida permanentemente en el historial.

**Estado actual:** ⚠️ Credencial activa y expuesta  
**Fix:**
1. Revocar en `myaccount.google.com → Seguridad → Contraseñas de aplicaciones`
2. Regenerar nueva App Password
3. Verificar que `.env` esté en `.gitignore` **antes** del primer commit
4. Si ya hay commits con `.env`, limpiar el historial con `git filter-branch` o `BFG Repo-Cleaner`

---

### SEC-02 — Contraseña SMTP visible en el DOM
**Tipo:** Sensitive Data Exposure  
**OWASP:** A02:2021 – Cryptographic Failures  
**Archivo:** `views/superadmin/notificaciones/index.php`  
**ID deuda:** BUG-V-15  
**Ref. Oleada:** 1  

App Password SMTP renderizada en `value=""` del input HTML. Cualquier usuario con acceso al panel que abra DevTools puede verla en texto plano, independientemente de que el campo sea `type="password"`.

**Fix:** Eliminar el atributo `value`. Usar solo `placeholder="(guardada — dejar vacío para no cambiar)"`. Ver ADR-015.  
**Extender a:** Auditar con `grep -rn 'value.*password\|value.*pass\|value.*secret\|value.*token' views/` y corregir cualquier ocurrencia adicional.

---

### SEC-03 — Inyección SQL por interpolación directa (3 puntos)
**Tipo:** SQL Injection  
**OWASP:** A03:2021 – Injection  
**Archivos:**
- `app/Models/EstudianteModel::generarCodigo()` — `$instId` interpolado (BUG-M-03)
- `app/Controllers/SuperAdminController::togglePlan()` — `$id` interpolado (BUG-M-10)
- `app/Controllers/SuperAdminController::exportarInstituciones()` — JOIN heredado (MEJ-B7-01)

**Ref. Oleada:** 7

Aunque `$instId` e `$id` provienen de la sesión (reducen riesgo), la interpolación directa en SQL es una práctica que no debe existir en el código. Si alguna vez el origen del valor cambia, se abre una inyección sin ninguna barrera.

**Fix:** Reemplazar interpolación con `bindValue()` o pasar como parámetro al prepared statement.

---

### SEC-04 — Acciones destructivas via GET sin CSRF (2 puntos)
**Tipo:** Cross-Site Request Forgery  
**OWASP:** A01:2021 – Broken Access Control  
**Archivos:**
- `views/colegio/admin/estudiantes/index.php` y `ver.php` — "Desactivar estudiante" via GET (BUG-B8b-04)
- `app/Controllers/EstudianteController::eliminar()` — eliminar via GET (BUG-2)

**Ref. Oleada:** 6

Un atacante puede construir una URL como `<img src="https://app.com/admin/estudiante/desactivar/42">` y, si el admin tiene sesión activa y visita una página que cargue esa imagen, la acción se ejecuta sin su conocimiento.

**Fix:** Cambiar a `POST` con token CSRF validado por `BaseController::validateCsrf()`.

---

### SEC-05 — Token CSRF con nombre incorrecto en formularios (2 puntos)
**Tipo:** CSRF Protection Bypass  
**OWASP:** A01:2021 – Broken Access Control  
**Archivos:**
- `views/colegio/admin/estudiantes/form.php` — `name="_csrf_token"` (BUG-B8b-06)
- `views/colegio/admin/preinscripciones/ver.php` — `name="_csrf_token"` (BUG-B8b-13)

**Ref. Oleada:** 6

El campo tiene `name="_csrf_token"` (con underscore), pero `BaseController::validateCsrf()` espera `name="csrf_token"` (sin underscore). El formulario envía el token con el nombre incorrecto — la validación del lado servidor nunca lo encuentra, dejando las acciones desprotegidas o causando falsos rechazos.

**Fix:** Cambiar `name="_csrf_token"` → `name="csrf_token"` en ambos formularios.

---

### SEC-06 — Fatal Error: `$this` en partial include (crash de aplicación)
**Tipo:** Application Error / DoS parcial  
**OWASP:** A05:2021 – Security Misconfiguration  
**Archivo:** `views/superadmin/cobros/formulario_partial.php`  
**ID deuda:** BUG-V-01  
**Ref. Oleada:** 1

`$this->generateCsrfToken()` en un archivo incluido via `include`. `$this` no existe en ese contexto — PHP lanza Fatal Error y el formulario de cobros es inaccesible. Cualquier usuario puede triggerearlo con solo navegar a esa sección.

**Fix:** Usar `<?= htmlspecialchars($csrf_token) ?>` — el controller ya pasa `$csrf_token` como variable.

---

### SEC-07 — XSS: Output sin escapar en vistas (patrón sistémico)
**Tipo:** Cross-Site Scripting (Reflected / Stored)  
**OWASP:** A03:2021 – Injection  
**Archivos afectados:** 15+ archivos  
**Ref. Oleada:** 4

Múltiples vistas renderizan variables de base de datos o de usuario directamente en HTML sin `htmlspecialchars()`. Si un atacante logra insertar `<script>alert(1)</script>` en un campo de nombre o descripción, se ejecutará en el navegador de cualquier usuario que vea esa vista.

**Puntos de mayor riesgo:**

| Archivo | Campo | Tipo XSS |
|---------|-------|----------|
| `instituciones/ver.php` | 3 campos de institución | Stored |
| `configuracion/index.php` | `$clave` en atributos HTML | Stored |
| `usuarios/index.php` | Nombre en JS `confirmarEliminar()` | Stored |
| `partials/plan_uso.php` | `$_item['icono']`, `$_item['label']` | Stored — dashboard de todos los colegios |
| `preregistro-formulario.php` | Flash messages | Reflected |
| `preinscripcion-gracias.php` | `$inst['telefono']` | Stored |
| `confirmacion_pago.php` | `$fechaPago`, `$fechaHasta`, `ucfirst($metodo)` | Stored |
| `preinscripcion-formulario.php` | `$errors['general']`, closure `$err()` | Reflected |

**Fix:** `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` en todo output. Para contexto JavaScript: `json_encode($var)`.  
**Auditoría rápida:** `grep -rn "echo \$\|<?=" views/ | grep -v "htmlspecialchars\|json_encode\|intval\|(int)"` — todo resultado es candidato a revisión.

---

### SEC-08 — XSS en contexto JavaScript: interpolación directa en `confirm()` y strings JS
**Tipo:** Cross-Site Scripting (DOM-based)  
**OWASP:** A03:2021 – Injection  
**Archivos:**
- `usuarios/index.php` — `confirmarEliminar()` con nombre interpolado (BUG-V-10)
- `instituciones/usuarios.php` — `addslashes()` insuficiente (MEJ-V8-02)
- `planes/index.php` — `confirm()` con interpolación (MEJ-V8-07)
- `salud/index.php` — `confirm()` apostrofe (MEJ-V10-02)
- `preregistros/ver.php` — `confirm()` botón rechazar (MEJ-V11-01)
- `configuracion/index.php` — `$clave` en selector CSS/JS (MEJ-V10-01)

**Ref. Oleada:** 4

`addslashes()` no es suficiente para contexto JavaScript — no escapa caracteres Unicode, saltos de línea ni secuencias de cierre de tag. Un nombre como `D'Alto` rompe el string; `</script><script>alert(1)` lo escapa completamente.

**Fix estándar del proyecto:** `json_encode($var, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)`

---

### SEC-09 — Contraseñas iniciales hardcodeadas y predecibles
**Tipo:** Weak Credentials  
**OWASP:** A07:2021 – Identification and Authentication Failures  
**Archivos:**
- `views/superadmin/instituciones/crear.php` (MEJ-V8-03)
- `views/superadmin/preregistros/ver.php` (BUG-V-13)
- `SuperAdminController::crearInstitucion()` (implícito)

**Ref. Oleada:** 9

Todos los colegios nuevos reciben `Colegio2024!` como contraseña inicial. Cualquier persona que sepa el username del admin de un colegio puede intentar este password. Sin mecanismo de cambio forzado (ADR-013 pendiente), muchos admins nunca la cambiarán.

**Fix:** Generar contraseña aleatoria en el controller (`Colegio{rand(1000,9999)}!`). Activar `debe_cambiar_password=1` (ADR-013 + ADR-014).

---

### SEC-10 — Integridad referencial inexistente en tabla `calificaciones`
**Tipo:** Data Integrity  
**OWASP:** A04:2021 – Insecure Design  
**Tabla:** `calificaciones`  
**ID deuda:** BUG-BD-01  
**Ref. Oleada:** 8

La tabla `calificaciones` no tiene `ENGINE=InnoDB` explícito. Si el servidor usa MyISAM por defecto, todas las Foreign Keys (hacia `matriculas`, `asignaturas`, `periodos`, `profesores`) son silenciosamente ignoradas. Se pueden crear y dejar calificaciones huérfanas sin que la BD lo impida.

**Fix:** `ALTER TABLE calificaciones ENGINE=InnoDB;`

---

### SEC-11 — Query de BD directa en el layout principal (cada request)
**Tipo:** Information Disclosure / Performance  
**OWASP:** A04:2021 – Insecure Design  
**Archivo:** `views/layouts/main.php`  
**ID deuda:** BUG-B9-04  
**Ref. Oleada:** 3

`SELECT COUNT(*) FROM preregistro_colegios` ejecutado directamente en el sidebar del layout en cada request del superadmin. Además de ser lógica de BD en la capa de presentación, si la query falla o tarda, el layout completo se ve afectado.

**Fix:** Pasar `$preregistrosPendientes` como variable desde el controller correspondiente.

---

## 🟡 VULNERABILIDADES MEDIAS

### SEC-M-01 — IDs sin cast `(int)` en URLs (patrón sistémico)
**Tipo:** Type Juggling / Injection parcial  
**Archivos:** ~15 ocurrencias en `ingresos.php`, `usuarios/index.php`, `preregistros/index.php` y vistas B8b  
**Ref. Oleada:** 4

IDs de entidades usados en URLs y atributos HTML sin `(int)` cast. Si bien los prepared statements protegen la BD, un ID mal tipado puede causar comportamiento inesperado y en contextos de interpolación directa abre posibilidades de inyección.

**Fix:** `(int)$id` en todo output de ID numérico. `grep -rn "?id=<?=" views/` para encontrar ocurrencias.

---

### SEC-M-02 — Race condition en generación de número de factura
**Tipo:** TOCTOU (Time-of-Check to Time-of-Use)  
**Archivo:** `app/Models/PagoSaasModel::generarNumeroFactura()`  
**ID deuda:** BUG-M-02  
**Ref. Oleada:** 7

`SELECT MAX(numero) + 1` sin bloqueo de transacción. Bajo concurrencia (dos cobros simultáneos), dos procesos pueden leer el mismo MAX y generar el mismo número de factura. El UNIQUE en BD lo rechazará, pero el error llega al usuario como un fallo inesperado en lugar de prevenirse.

**Fix:** Usar el `id` del pago como correlativo (`EduSaaS-2026-{$pagoId}`), eliminando la query de secuencia.

---

### SEC-M-03 — `while(true)` sin límite en generación de subdominio
**Tipo:** Potential DoS  
**Archivo:** `app/Controllers/PreregistroController::generarSubdomain()`  
**ID deuda:** BUG-M-12  
**Ref. Oleada:** 7

Loop infinito si todos los subdominios generados están tomados. En producción con muchos colegios podría colgar el proceso.

**Fix:** `$maxIntentos = 100` con fallback a error controlado.

---

### SEC-M-04 — Lógica de BD directa en vistas (3 puntos adicionales)
**Tipo:** Insecure Design / Data Exposure  
**Archivos:**
- `views/superadmin/planes/index.php` — `Database::getInstance()` en `foreach` (BUG-V-05)
- `views/superadmin/notificaciones/index.php` — query con instituciones (BUG-V-14)
- `views/colegio/admin/preinscripciones/index.php` — query con `$_SESSION` (BUG-B8b-11)

**Ref. Oleada:** 5

Acceso directo a BD desde vistas viola la separación de capas y puede exponer lógica de negocio sensible directamente en la capa de presentación.

---

### SEC-M-05 — `$_SESSION` accedido directamente en vistas
**Tipo:** Insecure Design  
**Archivos:**
- `views/superadmin/notificaciones/index.php` — `$_SESSION['flash']` (BUG-V-16)
- `public/forms/preregistro/formulario.php` — flash messages sin escape (BUG-B9-05)

**Ref. Oleada:** 5

La vista accede directamente a `$_SESSION` en lugar de recibir variables preparadas desde el controller. Acoplamiento frágil que puede filtrar datos de sesión no intencionados si la estructura de `$_SESSION` cambia.

---

### SEC-M-06 — `rol_id = 1` hardcodeado en EmailService
**Tipo:** Hardcoded Privilege Escalation Risk  
**Archivo:** `app/Services/EmailService::notificarNuevoPreregistro()`  
**ID deuda:** BUG-M-07  
**Ref. Oleada:** 7

`WHERE rol_id = 1` hardcodeado en lugar de `ROL_SUPER_ADMIN`. Si los IDs de roles cambian (migración, nuevo ambiente), la notificación se envía al rol incorrecto o a nadie.

---

### SEC-M-07 — Flash messages sin whitelist de tags HTML
**Tipo:** XSS potencial  
**Archivo:** `views/layouts/main.php`  
**ID deuda:** MEJ-B9-01  
**Ref. Oleada:** 3

`$flash['mensaje']` renderizado confiando en que el contenido viene del servidor. Si algún path de código construye un flash message con input de usuario sin sanitizar, se convierte en XSS almacenado en sesión.

**Fix:** Crear helper `flashHtml()` con whitelist de tags permitidos (`<strong>`, `<em>` únicamente).

---

### SEC-M-08 — Colores CSS sin validación hex
**Tipo:** CSS Injection  
**Archivos:**
- `views/layouts/main.php` — colores del banner Trial (MEJ-B9-03)
- `views/errors/mantenimiento.php` — `$_color` de ConfigModel (MEJ-B8d-01)

**Ref. Oleada:** 3

Valores de color de ConfigModel renderizados en `style=""` sin validar formato hexadecimal. Un valor como `red; } body { display:none` puede romper el CSS o en contextos más complejos inyectar estilos maliciosos.

**Fix:** `preg_match('/^#[0-9a-fA-F]{3,6}$/', $color)` antes de renderizar. Usar `#1a56db` como fallback.

---

### SEC-M-09 — Módulo `PlanHelper::tieneModulo()` sin whitelist
**Tipo:** Logic Bypass  
**Archivo:** `app/Helpers/PlanHelper::tieneModulo()`  
**ID deuda:** MEJ-B5-03  
**Ref. Oleada:** 10

El string `$modulo` no se valida contra una lista de módulos conocidos. Si un atacante controla el valor (via URL o parámetro), podría potencialmente evaluar condiciones no previstas en la lógica de permisos de módulos.

---

### SEC-M-10 — SSL con `verify_peer=false` en SmtpClient
**Tipo:** Man-in-the-Middle (TLS Downgrade)  
**Archivo:** `vendor/SmtpClient.php`  
**ID deuda:** MEJORA-8  
**Ref. Oleada:** 10

`stream_context_create(['ssl' => ['verify_peer' => false]])` desactiva la verificación del certificado SSL del servidor SMTP. Un atacante en la misma red puede interceptar y manipular los emails enviados.

**Aceptable en:** desarrollo local (XAMPP)  
**Inaceptable en:** producción  
**Fix:** Cambiar a `verify_peer => true` y `verify_peer_name => true` antes del deploy.

---

### SEC-M-11 — `require` sin `_once` en EmailService
**Tipo:** Duplicate Execution Risk  
**Archivo:** `app/Services/EmailService::__construct()`  
**ID deuda:** BUG-M-06  
**Ref. Oleada:** 7

`require` sin `_once` ejecuta el archivo en cada instanciación de `EmailService`. Si se crean múltiples instancias en un request (posible en crons), puede redeclarar clases o funciones, causando Fatal Error.

---

### SEC-M-12 — `catch(Exception $ignored)` silencia error del contador de intentos
**Tipo:** Security Control Bypass  
**Archivo:** `app/Controllers/AuthController::login()`  
**ID deuda:** MEJ-B7-05  
**Ref. Oleada:** 7

Si la query del contador de intentos fallidos lanza una excepción, es silenciada. El sistema de bloqueo por intentos podría dejar de funcionar silenciosamente, permitiendo fuerza bruta sin que nadie se entere.

**Fix:** Agregar `error_log($ignored->getMessage())` en el catch.

---

### SEC-M-13 — `debug=true` por defecto en app.php
**Tipo:** Information Disclosure  
**Archivo:** `config/app.php`  
**ID deuda:** MEJORA-2  
**Ref. Oleada:** 2

En modo debug, los errores PHP se muestran al usuario final — stack traces que revelan rutas del servidor, nombres de clases, queries SQL y estructura interna de la aplicación.

**Fix:** `debug = false` por defecto. Solo activar en `.env` de desarrollo.

---

### SEC-M-14 — URL Cloudflare obfuscada hardcodeada en layout
**Tipo:** Broken Functionality / Client-side Manipulation  
**Archivo:** `views/layouts/main.php`  
**ID deuda:** BUG-B9-02  
**Ref. Oleada:** 1

El botón "Contratar plan" apunta a una URL `/cdn-cgi/...` generada por Cloudflare durante un despliegue anterior. En cualquier servidor sin Cloudflare (incluyendo XAMPP local), el botón está completamente roto. Adicionalmente, el script `<script data-cfasync>` es código externo inyectado que no debería existir en el repositorio.

---

### SEC-M-15 — `$orderBy` en BaseModel interpolado sin validar
**Tipo:** SQL Injection (controlada)  
**Archivo:** `app/Models/BaseModel`  
**ID deuda:** MEJ-B4-02  
**Ref. Oleada:** 10

El parámetro `$orderBy` se interpola directamente en la query. Actualmente solo lo usan los propios controllers con valores hardcodeados, pero si algún developer futuro lo conecta a input del usuario, abre una inyección SQL.

**Fix:** Documentar explícitamente con comentario `// NUNCA pasar input del usuario aquí` y agregar whitelist de columnas permitidas.

---

### SEC-M-16 — Branding dinámico completamente ignorado
**Tipo:** Logic Error  
**Archivo:** `views/layouts/main.php`  
**ID deuda:** BUG-B9-03  
**Ref. Oleada:** 3

Un segundo bloque `:root` con colores hardcodeados sobreescribe silenciosamente los colores dinámicos cargados desde `ConfigModel`. El sistema de personalización de marca es completamente inoperante aunque los colegios configuran sus colores en el panel.

---

### SEC-M-17 — Variables de entorno con nombres inconsistentes
**Tipo:** Configuration Error  
**Archivos:** `.env` vs `config/database.php`  
**ID deuda:** MEJORA-1  
**Ref. Oleada:** 2

`.env` define `DB_NAME` y `DB_USER`, pero `database.php` lee `DB_DATABASE` y `DB_USERNAME`. La conexión a BD falla en producción — la aplicación no arranca.

---

### SEC-M-18 — `CRON_SECRET` hardcodeado en constants.php
**Tipo:** Hardcoded Secret  
**Archivo:** `config/constants.php`  
**ID deuda:** MEJORA-3  
**Ref. Oleada:** 2

El token que protege los endpoints de cron está hardcodeado en el código fuente. Si el repositorio se comparte o el código se despliega en un hosting compartido, cualquiera puede triggerear los crons manualmente.

**Fix:** Mover a `.env` como `CRON_SECRET=...`

---

## 🔵 BAJO RIESGO / MEJORAS DE SEGURIDAD

| ID | Descripción | Oleada |
|----|-------------|--------|
| SEC-B-01 | `javascript:history.back()` en 403/404 sin fallback — no es XSS pero es mala práctica | 10 |
| SEC-B-02 | CDN inconsistente (jsdelivr vs cdnjs) — misma librería desde dos fuentes distintas, dificulta CSP futuro | 10 |
| SEC-B-03 | `X-XSS-Protection` en `.htaccess` está deprecado — reemplazar por CSP cuando se implemente | Futuro |
| SEC-B-04 | `Content-Security-Policy` no implementado — agregar como header en `.htaccess` | Futuro |
| SEC-B-05 | `Referrer-Policy` no implementado | Futuro |
| SEC-B-06 | Validación de foto solo por extensión — agregar `finfo` MIME real (BUG-3) | 10 |
| SEC-B-07 | `ConfigModel::set()` catch vacío — errores de configuración silenciados (MEJ-B4-01) | 10 |
| SEC-B-08 | `Database.php` no loguea errores de conexión en producción (MEJORA-6) | 2 |
| SEC-B-09 | `Router::ejecutar()` hace `die()` en 404 — mejor mostrar página de error controlada (MEJORA-7) | 10 |
| SEC-B-10 | `json_decode` sin `json_last_error()` en log/index — puede crashear silenciosamente (MEJ-V10-04) | 10 |
| SEC-B-11 | `BaseModel::where()` solo soporta `=` — documentar limitación para evitar workarounds inseguros (MEJ-B4-03) | 10 |
| SEC-B-12 | Roles tabla tiene columna `permisos JSON` siempre NULL — sistema de permisos granulares no implementado, documentar como Fase futura | Futuro |

---

## ✅ Controles de seguridad correctamente implementados

Los siguientes mecanismos están bien implementados y deben mantenerse como referencia:

| Control | Implementación | Notas |
|---------|---------------|-------|
| **Hashing de contraseñas** | `password_hash()` con bcrypt | ✅ Correcto |
| **Protección CSRF base** | `BaseController::generateCsrfToken()` + `validateCsrf()` | ✅ Correcto — ver SEC-05 para excepciones |
| **Bloqueo por intentos fallidos** | `intentos_fallidos` + `bloqueado_hasta` en `usuarios` | ✅ Correcto — ver SEC-M-12 |
| **Prepared statements (mayoría)** | PDO con `bindValue()` en BaseModel y la mayoría de models | ✅ Correcto — ver SEC-03 para excepciones |
| **Verificación de rol** | `BaseController::requireRole()` en cada acción | ✅ Correcto |
| **Verificación de suscripción** | `SuscripcionMiddleware::verificar()` | ✅ Correcto |
| **Modo Visor (solo lectura)** | `VisorMiddleware` bloquea escrituras en modo visor | ✅ Elegante |
| **Session regeneration** | `session_regenerate_id()` en login | ✅ Correcto |
| **Tenant isolation (models)** | Filtro por `institucion_id` en queries | ✅ Correcto — auditar AdminController |
| **UNIQUE en factura** | `numero_factura` UNIQUE en BD como red de seguridad | ✅ Correcto |
| **Cascade en BD** | Foreign Keys con `ON DELETE CASCADE` en tablas de tenant | ✅ Correcto |
| **Log de auditoría** | `log_actividad` con snapshot de `usuario_nombre` | ✅ Robusto |
| **Log de estado** | `log_estado_instituciones` para cambios de estado | ✅ Correcto |

---

## 🔍 Comandos de auditoría rápida

Ejecutar desde la raíz del proyecto para identificar patrones problemáticos:

```bash
# XSS: output sin escapar (candidatos)
grep -rn "echo \$\|<?=" views/ | grep -v "htmlspecialchars\|json_encode\|intval\|(int)\|date\|number_format\|count\|strlen"

# SQL Injection: interpolación directa
grep -rn "\"\s*\.\s*\$\|'\s*\.\s*\$" app/Models/ app/Controllers/

# Credenciales en código fuente
grep -rn "password\|secret\|api_key\|app_password" config/ --include="*.php" | grep -v "\$_ENV\|getenv\|//\|#"

# require app.php en vistas (patrón sistémico)
grep -rn "require.*app\.php" views/ public/

# Database::getInstance() en vistas
grep -rn "Database::getInstance" views/

# Acciones GET destructivas
grep -rn "eliminar\|desactivar\|borrar\|delete\|destroy" routes/web.php | grep "GET"

# Campos de contraseña con value
grep -rn "value.*password\|value.*pass\|value.*secret" views/

# Token CSRF con nombre incorrecto
grep -rn "_csrf_token" views/
```

---

## 📋 Checklist pre-deploy de seguridad

Completar en orden antes de cualquier despliegue a producción:

### Obligatorio (bloquea deploy)
- [ ] SEC-01 — Credencial `.env` revocada y regenerada
- [ ] SEC-02 — Contraseña SMTP eliminada del DOM
- [ ] SEC-03 — Interpolaciones SQL corregidas con `bindValue()`
- [ ] SEC-04 — Acciones destructivas cambiadas a POST + CSRF
- [ ] SEC-05 — Token CSRF con nombre correcto (`csrf_token`)
- [ ] SEC-06 — Fatal Error en formulario de cobros corregido
- [ ] SEC-07 — `htmlspecialchars()` en todos los outputs HTML
- [ ] SEC-08 — `json_encode()` en todos los contextos JavaScript
- [ ] SEC-13 — `debug=false` en producción
- [ ] SEC-M-17 — Variables `.env` con nombres correctos
- [ ] SEC-M-18 — `CRON_SECRET` movido a `.env`
- [ ] SEC-10 — `ALTER TABLE calificaciones ENGINE=InnoDB`
- [ ] `.env` en `.gitignore` verificado

### Recomendado antes del primer usuario real
- [ ] SEC-09 — Contraseñas iniciales dinámicas + cambio forzado
- [ ] SEC-M-10 — SSL `verify_peer=true` en SmtpClient
- [ ] SEC-M-13 — `debug=false` verificado en `.env` de producción
- [ ] SEC-B-06 — Validación MIME real de uploads
- [ ] Backup de BD configurado y probado

### Mejoras post-lanzamiento
- [ ] `Content-Security-Policy` header
- [ ] `Referrer-Policy` header
- [ ] Remover `X-XSS-Protection` (deprecado)
- [ ] Sistema de permisos granulares (`roles.permisos` JSON — Fase futura)

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `DEUDA_TECNICA.md` · `PLAN_CORRECCIONES.md` · `DEPLOY.md`*
