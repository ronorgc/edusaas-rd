# 🚫 DEUDA TÉCNICA — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Fuente:** Code Review completo (117 archivos · Sesiones 003–015)  
**Estado del tracker:** Activo

> Este archivo es el **registro centralizado** de toda la deuda técnica del sistema.  
> La Bitácora (`BITACORA.md`) referencia este doc en lugar de duplicar el detalle.  
> Actualizar este archivo al corregir cada ítem.

---

## 📖 Leyenda

| Símbolo | Significado |
|---------|-------------|
| 🔴 | Crítico — bloquea deploy o compromete seguridad |
| 🟡 | Medio — degradación funcional o de seguridad |
| 🔵 | Mejora — calidad, consistencia, mantenibilidad |
| ✅ | Corregido |
| ⏳ | Pendiente |
| 🔄 | En progreso |

---

## 🔴 CRÍTICOS (10 total)

### BUG-CRÍTICO-1
- **Estado:** ⏳ Pendiente — Oleada 1
- **Archivo:** `app/Models/AnoescolarMode.php`
- **Descripción:** Nombre de archivo incorrecto. En Linux (case-sensitive) el autoloader no lo encuentra.
- **Impacto:** Fatal Error en todo lo relacionado con años escolares.
- **Fix:** Renombrar a `AnoEscolarModel.php` y actualizar todas las referencias.

### BUG-CRÍTICO-2
- **Estado:** ⏳ Pendiente — Oleada 0 (INMEDIATA)
- **Archivo:** `.env`
- **Descripción:** Google App Password real expuesta en texto plano.
- **Impacto:** Credencial comprometida si el repo se hace público.
- **Fix:** Revocar en `myaccount.google.com → Seguridad → Contraseñas de aplicaciones`, regenerar, actualizar `.env`. Verificar `.gitignore`.

### BUG-V-01
- **Estado:** ⏳ Pendiente — Oleada 1
- **Archivo:** `views/superadmin/cobros/formulario_partial.php`
- **Descripción:** `$this->generateCsrfToken()` usado en un `include`. `$this` no existe en ese contexto.
- **Impacto:** Fatal Error al cargar el formulario de cobros.
- **Fix:** Reemplazar con `<?= htmlspecialchars($csrf_token) ?>`. El controller ya debe pasar `$csrf_token`.

### BUG-V-05
- **Estado:** ⏳ Pendiente — Oleada 5
- **Archivo:** `views/superadmin/planes/index.php`
- **Descripción:** `Database::getInstance()` + raw SQL ejecutados dentro de un `foreach` en la vista.
- **Impacto:** N+1 queries + lógica de negocio en capa de presentación.
- **Fix:** Mover a `PlanModel::getAllConConteo()` con JOIN. Pasar `$plan['colegios_activos']` desde controller.

### BUG-V-15
- **Estado:** ⏳ Pendiente — Oleada 1
- **Archivo:** `views/superadmin/notificaciones/index.php`
- **Descripción:** Contraseña SMTP renderizada en atributo `value=""` de input HTML.
- **Impacto:** App Password visible en DevTools para cualquier usuario con acceso al panel.
- **Fix:** Eliminar `value`. Usar `placeholder="(guardada — dejar vacío para no cambiar)"`. Ver ADR-015.

### BUG-B9-01
- **Estado:** ⏳ Pendiente — Oleada 1 (resuelto por ADR-016)
- **Archivo:** `views/layouts/main.php`
- **Descripción:** `require app.php` para obtener URL base. Es la 5ª y más impactante ocurrencia del patrón sistémico.
- **Impacto:** Si el path relativo falla, toda la aplicación se rompe.
- **Fix:** ADR-016 — usar `APP_URL` desde `constants.php`.

### BUG-B9-02
- **Estado:** ⏳ Pendiente — Oleada 1
- **Archivo:** `views/layouts/main.php`
- **Descripción:** Botón "Contratar plan" apunta a una URL Cloudflare obfuscada (`/cdn-cgi/...`) hardcodeada, en lugar del `mailto:soporte@edusaas.do` original.
- **Impacto:** Botón completamente roto en cualquier servidor sin Cloudflare.
- **Fix:** Restaurar URL real, eliminar `<script data-cfasync>`.

### BUG-B8b-04
- **Estado:** ⏳ Pendiente — Oleada 6
- **Archivo:** `views/colegio/admin/estudiantes/index.php` y `ver.php`
- **Descripción:** Acción "Desactivar estudiante" ejecutada via GET sin token CSRF.
- **Impacto:** Acción destructiva vulnerable a CSRF.
- **Fix:** Cambiar a POST + token CSRF.

### BUG-B8b-11
- **Estado:** ⏳ Pendiente — Oleada 5
- **Archivo:** `views/colegio/admin/preinscripciones/index.php`
- **Descripción:** `Database::getInstance()` + query con `$_SESSION` ejecutados directamente en la vista.
- **Impacto:** Lógica de BD en capa de presentación, acoplamiento frágil.
- **Fix:** Mover al controller. Pasar datos como variables.

### BUG-B8c-01
- **Estado:** ⏳ Pendiente — Oleada 1 (resuelto por ADR-016)
- **Archivo:** `views/emails/bienvenida.php`
- **Descripción:** `require __DIR__/../../config/app.php` dentro del template de email.
- **Impacto:** 4ª ocurrencia del patrón sistémico de path frágil.
- **Fix:** Pasar `$appUrl` desde `EmailService`.

### BUG-BD-01
- **Estado:** ⏳ Pendiente — Oleada 8
- **Archivo:** `database/edusaas_rd.sql` → tabla `calificaciones`
- **Descripción:** Única tabla del schema sin `ENGINE=InnoDB` explícito. Si el servidor tiene otro motor por defecto (MyISAM), todas las Foreign Keys de `calificaciones` son silenciosamente ignoradas.
- **Impacto:** Integridad referencial inexistente — posibles calificaciones huérfanas sin que la BD proteste. Afecta `matriculas`, `asignaturas`, `periodos`, `profesores`.
- **Fix:** `ALTER TABLE calificaciones ENGINE=InnoDB;`

---

## 🟡 MEDIOS (53 total)

### Grupo: Inyección SQL / Prepared Statements

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-M-03 | `EstudianteModel::generarCodigo()` | `$instId` interpolado directamente en SQL | 7 |
| BUG-M-10 | `SuperAdminController::togglePlan()` | `$id` interpolado directamente en SQL | 7 |

### Grupo: Seguridad CSRF

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-2 | `EstudianteController` | `eliminar()` via GET sin CSRF | 6 |
| BUG-B8b-06 | `views/.../estudiantes/form.php` | `name="_csrf_token"` (underscore) — token no reconocido | 6 |
| BUG-B8b-13 | `views/.../preinscripciones/ver.php` | `name="_csrf_token"` (underscore) | 6 |

### Grupo: Seguridad XSS — output sin escapar

| ID | Archivo | Campo | Oleada |
|----|---------|-------|--------|
| BUG-V-04 | `instituciones/ver.php` | 3 campos de institución | 4 |
| BUG-V-12 | `configuracion/index.php` | `$clave` en `name`, `id`, `for` | 4 |
| BUG-V-10 | `usuarios/index.php` | Nombre en JS `confirmarEliminar()` | 4 |
| BUG-B9-05 | `preregistro-formulario.php` | Flash messages | 4 |
| BUG-B9-06 | `partials/plan_uso.php` | `$_item['icono']` y `$_item['label']` | 4 |
| BUG-B8d-B01 | `preinscripcion-gracias.php` | `$inst['telefono']` | 4 |
| BUG-B8c-02 | `confirmacion_pago.php` | `$fechaPago`, `$fechaHasta` | 4 |
| BUG-B8c-03 | `confirmacion_pago.php` | `ucfirst($pago['metodo_pago'])` | 4 |
| BUG-B8d-F02 | `preinscripcion-formulario.php` | `$errors['general']` | 4 |
| BUG-B8d-F03 | `preinscripcion-formulario.php` | Closure `$err()` | 4 |

### Grupo: Lógica de BD en vistas

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-V-11 | `configuracion/index.php` | `ConfigModel::get()` en vista | 5 |
| BUG-V-14 | `notificaciones/index.php` | Query BD para selector de instituciones | 5 |
| BUG-V-16 | `notificaciones/index.php` | `$_SESSION['flash']` accedido en vista | 5 |
| BUG-B9-04 | `layouts/main.php` | `SELECT COUNT(*)` en sidebar de cada request | 3 |

### Grupo: Hardcoding de datos de empresa

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-V-07 | `cobros/recibo_cobro.php` | Email/web empresa hardcodeados | 4 |
| BUG-V-08 | `cobros/recibo_cobro.php` | `$susc['tipo_facturacion']` potencialmente indefinida | 4 |
| BUG-V-09 | `pagos/recibo.php` | Email/web empresa hardcodeados | 4 |
| BUG-V-13 | `preregistros/ver.php` | `Colegio2024!` hardcodeada | 9 |
| MEJ-V8-03 | `instituciones/crear.php` | `Colegio2024!` hardcodeada | 9 |

### Grupo: Navegación rota

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-B8b-05 | `estudiantes/index.php` | 3 links dropdown sin prefijo `/admin/` — 404 | 6 |
| BUG-V-06 | `cobros/index.php` | `actualizarMonto()` busca `#sel-tipo` inexistente | 4 |
| MEJ-V8-04 | `cobros/masivo.php` | Typo: `actualizarTodosLosMontol` | 10 |

### Grupo: Branding y configuración

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-B9-03 | `layouts/main.php` | `:root` duplicado sobreescribe colores de ConfigModel | 3 |
| BUG-B8d-F01 | `preinscripcion-formulario.php` | `require app.php` en atributo `action` | 1 |

### Grupo: Inconsistencias en paths relativos (patrón sistémico)

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-M-04 | `VisorMiddleware` | `require app.php` duplicado en dos métodos | 1 |
| BUG-M-13 | `PreinscripcionController` | `require app.php` → `$this->config['url']` | 7 |
| MEJORA-4 | Varios | Ruta uploads inconsistente | 2 |

### Grupo: Race conditions y concurrencia

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-M-02 | `PagoSaasModel::generarNumeroFactura()` | Race condition bajo concurrencia — facturas duplicadas | 7 |
| BUG-M-12 | `PreregistroController::generarSubdomain()` | `while(true)` sin límite | 7 |

### Grupo: Queries con resultados incorrectos

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-M-01 | `InstitucionModel::getAllConSuscripcion()` | JOIN duplica rows con múltiples suscripciones | 7 |
| BUG-M-08 | `MetricasService::clientesPerdidosMes()` | Usa `updated_at` como proxy de baja — Churn Rate impreciso | 8 |

### Grupo: Configuración de entorno

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| MEJORA-1 | `.env` / `database.php` | `DB_NAME` vs `DB_DATABASE` — conexión falla en producción | 2 |
| MEJORA-2 | `config/app.php` | `debug=true` por defecto | 2 |
| MEJORA-3 | `config/constants.php` | `CRON_SECRET` hardcodeado | 2 |
| MEJORA-5 | `public/index.php` | Parser `.env` sin guardia contra líneas sin `=` | 2 |

### Grupo: Calidad de código

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| BUG-M-05 | `SuscripcionMiddleware::bloquear()` | `$motivo` no llega a vista `suspendido.php` | 7 |
| BUG-M-06 | `EmailService::__construct()` | `require` sin `_once` | 7 |
| BUG-M-07 | `EmailService` | `rol_id = 1` hardcodeado | 7 |
| BUG-M-09 | `AdminController::anosEscolares()` | N+1 queries en loop | 7 |
| BUG-M-11 | `SuperAdminController` | Dead code: SELECT descartado | 7 |
| BUG-B8d-B02 | `preinscripcion-gracias.php` | `date()` sin guardia contra `false` | 4 |
| BUG-B8d-B03 | Vista confirmacion vs gracias | Duplicación activa — dos vistas para mismo flujo | 10 |

### Grupo: Rutas y navegación

| ID | Archivo | Descripción | Oleada |
|----|---------|-------------|--------|
| MEJORA-9 | `routes/web.php` | Dos convenciones POST: `/crear` vs `/guardar` | 10 |
| MEJORA-7 | `vendor/Router.php` | `die()` en 404 en lugar de página de error | 10 |
| BUG-B8b-06/13 | Forms | Token CSRF con nombre incorrecto (`_csrf_token`) | 6 |

---

## 🔵 MEJORAS (78 total — selección de las más impactantes)

### Mejoras de seguridad

| ID | Descripción | Oleada |
|----|-------------|--------|
| MEJ-V8-02 | `addslashes()` → `json_encode()` en JS | 4 |
| MEJ-V9-05 | IDs sin cast `(int)` en URLs (patrón sistémico ~15 ocurrencias) | 4 |
| MEJ-V10-01 | `$clave` en selector CSS/JS sin escapar | 4 |
| MEJ-B9-03 | Colores CSS sin validación hex | 3 |
| MEJ-B5-03 | `PlanHelper::tieneModulo()` sin whitelist del string `$modulo` | 10 |
| MEJORA-8 | SSL `verify_peer=false` en `SmtpClient` — cambiar en producción | 10 |
| ADR-013 | Columna `debe_cambiar_password` en `usuarios` | 8 |

### Mejoras de arquitectura

| ID | Descripción | Oleada |
|----|-------------|--------|
| MEJ-B7-03 | `SuperAdminController` tiene 2,443 líneas — dividir post-MVP | 10 |
| MEJ-B7-04 | Helper `n()` duplicado en 2 controllers — mover a `BaseController` | 10 |
| MEJ-B9-02 | `isActive()` global — mover a `BaseController` | 3 |
| MEJ-B9-01 | Flash messages sin helper de sanitización | 3 |
| MEJ-B4-02 | `$orderBy` en BaseModel no usa prepared statement — documentar | 10 |
| MEJ-B4-04 | `paginate()` no acepta `$orderBy` — resultados no deterministas | 10 |
| MEJ-BD-01 | `grados.nivel` es ENUM que ignora tabla `niveles` — redundancia muerta. Decidir si se migra a FK o se documenta como intencional antes del Sprint 2.2 | 10 |
| MEJ-BD-02 | `secciones` tiene `capacidad DEFAULT 40` y `capacidad_maxima DEFAULT 30` — defaults contradictorios. Definir cuál se usa y eliminar la redundante | 10 |

### Mejoras de consistencia

| ID | Descripción | Oleada |
|----|-------------|--------|
| MEJ-B8c-01 | Email soporte hardcodeado en 6/9 templates — pasar como variable global | 10 |
| MEJ-B9-05 | CDN inconsistente: Bootstrap 5.3.0 vs 5.3.2 en distintas vistas | 10 |
| MEJ-B8d-02 | CDN inconsistente entre vistas de preinscripción | 10 |
| MEJ-B8d-04 | `javascript:history.back()` sin fallback — cambiar a `href="/"` | 10 |
| MEJ-V8-05 | `const planesData` declarado y nunca usado — eliminar | 10 |
| MEJ-V8-04 | Typo `actualizarTodosLosMontol` | 10 |
| MEJORA-10 | Comentario obsoleto en `routes/web.php` | 10 |

### Mejoras de observabilidad

| ID | Descripción | Oleada |
|----|-------------|--------|
| MEJORA-6 | `Database.php` no loguea errores de conexión en producción | 2 |
| MEJ-B4-01 | `ConfigModel::set()` tiene `catch` vacío | 10 |
| MEJ-B6-01 | `EmailService::renderLayout()` — variables por scope implícito | 10 |
| MEJ-B7-05 | `AuthController` silencia error del contador de intentos | 7 |
| MEJ-V10-04 | `log/index.php` — `json_decode` sin `json_last_error()` | 10 |

### Mejoras de UX/funcionalidad

| ID | Descripción | Oleada |
|----|-------------|--------|
| MEJ-V10-03 | Filtro de emails sin campo "Hasta" | 10 |
| MEJ-V9-01 | Botones de exportación duplicados en `ingresos.php` | 10 |
| MEJ-V9-03 | `generarPass()` expone contraseña en DOM en `usuarios/editar.php` | 10 |
| MEJ-B8d-03 | `suspendido.php` tiene email soporte hardcodeado | 10 |
| MEJ-B6-04 | `MetricasService::clientes12Meses()` mide creación, no activos al cierre | 10 |

---

## 📊 Estadísticas

| Categoría | Total | Resueltos | Pendientes |
|-----------|-------|-----------|------------|
| 🔴 Críticos | 11 | 0 | 11 |
| 🟡 Medios | 53 | 0 | 53 |
| 🔵 Mejoras | 80 | 0 | 80 |
| **TOTAL** | **144** | **0** | **144** |

---

## 🏷️ Tags por patrón sistémico

Los siguientes patrones aparecen en múltiples archivos y deben resolverse de forma coordinada:

| Patrón | Ocurrencias | Oleada |
|--------|-------------|--------|
| `require app.php` en vistas para URL base | 5+ archivos | 1 (ADR-016) |
| Contraseña hardcodeada `Colegio2024!` | 3 archivos | 9 (ADR-014) |
| `Database::getInstance()` en vistas | 3 vistas | 5 |
| `htmlspecialchars()` faltante en output | ~15 archivos | 4 |
| IDs sin cast `(int)` en URLs | ~15 ocurrencias | 4 |
| `json_encode()` faltante en JS | 5 archivos | 4 |
| Token CSRF con nombre incorrecto `_csrf_token` | 2 forms | 6 |
| Email soporte hardcodeado en templates | 6/9 emails | 10 |
| CDN inconsistente (jsdelivr vs cdnjs) | 4+ archivos | 10 |

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `PLAN_CORRECCIONES.md` para el orden de ejecución · `ADR.md` para decisiones técnicas*
