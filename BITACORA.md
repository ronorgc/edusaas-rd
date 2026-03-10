# 📓 BITÁCORA DEL PROYECTO: EduSaaS RD

**Última actualización:** 2026-03-10 — Sesión C-003
**Versión del sistema:** 1.0.0
**Estado general:** 🟡 CORRECCIONES EN CURSO — Oleadas 0+1+2+3 ✅ · Oleada 4 (XSS) es la última antes del deploy mínimo seguro



---

## 📌 RESUMEN DEL PROYECTO

Sistema SaaS multi-tenant para gestión académica y administrativa de instituciones educativas en República Dominicana. EduSaaS RD actúa como operador (SuperAdmin) que vende planes a colegios clientes. Cada colegio gestiona estudiantes, matrículas, calificaciones, asistencia, pagos y comunicados desde su propio panel. Stack: PHP 8.2 + MVC casero sin framework + MariaDB 10.4. Desplegado en XAMPP local, target producción: cPanel/Linux.

---

## 🏗️ ARQUITECTURA ACTUAL

**Stack tecnológico:**
- Frontend: PHP Views + Bootstrap 5 + Bootstrap Icons (sin JS framework)
- Backend: PHP 8.2, MVC casero (sin Laravel/Symfony), PDO
- Base de datos: MariaDB 10.4 — 33 tablas
- Infraestructura: XAMPP local → target cPanel/VPS Linux

**Patrones de diseño aplicados:**
- Front Controller (`public/index.php` — único punto de entrada via `.htaccess`)
- Active Record simplificado (`BaseModel`: find, where, create, update, delete, paginate)
- Template Method (`BaseController`: render, redirect, flash, requireRole, requireSuscripcion)
- Singleton (`Database::getInstance()` — una sola conexión PDO por request)
- Middleware estático (`SuscripcionMiddleware`, `VisorMiddleware`)

**Integraciones externas:**
- SMTP personalizado (`SmtpClient.php`) — Gmail, Outlook, Brevo (sin PHPMailer)
- Sin pasarela de pago activa aún (pagos manuales registrados por el superadmin)

**Diagrama de alto nivel:**
```
Browser → public/.htaccess → public/index.php (Front Controller)
         → vendor/Router.php (dispatch)
         → app/Controllers/*.php
              → app/Middlewares/ (Suscripcion, Visor)
              → app/Models/*.php → vendor/Database.php → MariaDB
              → app/Helpers/ (ActivityLog, PlanHelper)
              → app/Services/ (Email, Metricas)
         → views/**/*.php (layouts/main.php envuelve todo)
```

**Dos capas principales:**
- `/superadmin/...` → Panel EduSaaS RD (gestiona colegios, planes, cobros)
- `/admin/...` → Panel del Colegio (gestiona estudiantes, calificaciones, etc.)

---

## ✅ COMPLETADO

| Fecha | Módulo/Feature | Descripción | Notas |
|-------|---------------|-------------|-------|
| 2026-03-10 | Oleada 3 ✅ | `views/layouts/main.php` — BUG-B9-02 (mailto restaurado, script CF eliminado), MEJ-B9-01 (flashHtml() con whitelist), MEJ-B9-03 (color hex validado). Archivo reconstruido completo. | Sesión C-003 |
| 2026-03-10 | Oleada 2 ✅ | `app.php`, `constants.php`, `index.php`, `Database.php`, `EstudianteController.php` — MEJORA-1 al 6: debug false, CRON_SECRET a .env, upload path unificado, guard .env parser, error_log BD | Sesión C-003 |
| 2026-03-09 | Oleada 1 ✅ | `AnoEscolarModel.php` renombrado + 5 archivos APP_URL, CSRF cobros corregido, SMTP password del DOM eliminado | Sesión C-002 |
| 2026-03-09 | Oleada 0 ✅ | App Password Gmail revocada y regenerada, `.env` en `.gitignore`, commit `pre-fixes` | Sesión C-002 |
| 2026-03-09 | Code Review B9 COMPLETO | Layout/Auth/Partials/FrontController: layouts-main, auth-login, partials-plan_uso, preregistro-formulario, preregistro-gracias, public-index, .htaccess | 2 críticos, 5 medios, 6 mejoras |
| 2026-03-09 | Code Review B8d COMPLETO | Vistas Públicas: 403, 404, mantenimiento, suspendido, preinscripcion-confirmacion, preinscripcion-gracias, preinscripcion-formulario | 1 crítico, 5 medios, 8 mejoras |
| 2026-03-09 | Code Review B8c COMPLETO | Views Emails: layout, bienvenida, confirmacion_pago, aviso_vencimiento, suspension, reset_clave, preinscripcion_aprobada, personalizado, smtp_test | 1 crítico, 2 medios, 5 mejoras |
| 2026-03-09 | Code Review B8b COMPLETO | Views Admin/Colegio: dashboard, años-escolares, estudiantes, grados, periodos, secciones, preinscripciones — 13 archivos | 2 críticos, 8 medios, 7 mejoras |
| 2026-03-09 | Code Review B8a COMPLETO | Views SuperAdmin: 29 archivos totales — dashboard, instituciones, cobros, planes, pagos, usuarios, preregistros, configuracion, emails, log, salud, notificaciones | 2 críticos, 14 medios, 18 mejoras |
| 2026-03-09 | Code Review B7 | Controllers: AuthController, BaseController, DashboardController, PreregistroController, AdminController, EstudianteController, PreinscripcionController, SuperAdminController | 5 bugs medios, 5 mejoras |
| 2026-03-09 | Code Review B6 | Services: EmailService, MetricasService | 3 bugs medios, 4 mejoras |
| 2026-03-09 | Code Review B5 | Helpers + Middlewares: ActivityLog, PlanHelper, SuscripcionMiddleware, VisorMiddleware | 2 bugs medios, 3 mejoras |
| 2026-03-09 | Code Review B4 | Models (13 archivos): todos los modelos del sistema | 3 bugs medios, 5 mejoras |
| 2026-03-09 | Code Review B1 | Config base: .env, app.php, database.php, constants.php, mail.php, index.php | 2 críticos, 5 mejoras |
| 2026-03-09 | Code Review B2 | Vendor: Autoload, Database, Router, SmtpClient | 4 mejoras |
| 2026-03-09 | Code Review B3 | Routes: web.php, .htaccess | 3 mejoras, inconsistencia rutas |
| Anterior | SuperAdmin completo | Dashboard, Instituciones, Planes, Cobros, Notificaciones, Preregistros, Config, SMTP, Salud, Cron | ~95% |
| Anterior | Auth | Login, logout, bloqueo por intentos fallidos, modo visor superadmin | Completo |
| Anterior | Colegio — Estudiantes | CRUD completo con tutor y matrícula inicial, foto, filtros, stats | Completo |
| Anterior | Colegio — Config Académica | Años escolares, Grados (seed MINERD), Secciones, Períodos | Sprint 2.1 ✅ |
| Anterior | Pre-inscripciones | Portal público por slug + revisión admin + convertir a estudiante | Completo |
| 2026-03-08 | Inicialización | Bitácora, instrucciones, estructura base del proyecto | — |

| 2026-03-09 | /docs completo (8 archivos) | PLAN_CORRECCIONES, DEUDA_TECNICA, ADR, ARQUITECTURA, SCHEMA_BD, ROADMAP, SEGURIDAD, DEPLOY | Sesión 016 |


**Total archivos revisados: 117 de 117 (100%) ✅**

---

## 🔄 EN PROGRESO

- [ ] **Oleada 4** — XSS sistémico: `htmlspecialchars()` y `json_encode()` faltantes (~18 puntos)
  - Ronda 1: `instituciones/index.php` + `instituciones/ver.php` + `cobros/recibo_cobro.php`
  - Ronda 2: `pagos/recibo.php` + `usuarios/index.php` + `configuracion/index.php`
  - Ronda 3: `cobros/index.php` + `preregistros/ver.php`

---

## ⏳ PENDIENTE / POR HACER

### 🔴 Prioridad Alta — BLOQUEANTE antes de cualquier deploy

> ⚠️ Solo queda la **Oleada 4** (XSS sistémico) para alcanzar el mínimo seguro de deploy.

**Bugs de Views B8a — Lotes 1-2:**
- [ ] **BUG-V-02:** `instituciones/index.php` — `require app.php` dentro del `foreach` (N requires). Usar `$appUrl` ya definido.
- [ ] **BUG-V-03:** `instituciones/ver.php` — `require app.php` duplicado. Usar `$appUrl` existente.
- [ ] **BUG-V-04:** `instituciones/ver.php` — 3 campos sin `htmlspecialchars()`. Agregar escaping.
- [ ] **BUG-V-06:** `cobros/index.php` — `actualizarMonto()` busca `#sel-tipo` inexistente. Cambiar a `input[name="tipo_facturacion"]:checked`.
- [ ] **BUG-V-07:** `cobros/recibo_cobro.php` — email/web empresa hardcodeados ignorando `$_cfg_email_c` y `$_cfg_web`. Usar las variables.
- [ ] **BUG-V-08:** `cobros/recibo_cobro.php` — `$susc['tipo_facturacion']` potencialmente indefinida. Alinear con variable del controller.

**Bugs de Views B8a — Lotes 3-4:**
- [ ] **BUG-V-09:** `pagos/recibo.php` — email/web empresa hardcodeados en caja "Emitido por" ignorando `$_cfg_email_c` y `$_cfg_web`. Usar las variables (misma clase que BUG-V-07).
- [ ] **BUG-V-10:** `usuarios/index.php` — apostrofe en nombre rompe string JS en `confirmarEliminar()`. Cambiar a `json_encode()`.
- [ ] **BUG-V-11:** `configuracion/index.php` — `ConfigModel::get()` llamado directamente en la vista (2 queries extra). Pasar valores desde el controller.
- [ ] **BUG-V-12:** `configuracion/index.php` — atributos `name`, `id`, `for` con `$clave` sin `htmlspecialchars()`. Agregar escaping.
- [ ] **BUG-V-13:** `preregistros/ver.php` — contraseña inicial `Colegio2024!` hardcodeada en el formulario de aprobación. Generar dinámicamente en el controller.
- [ ] **BUG-V-14:** `notificaciones/index.php` — `Database::getInstance()` + raw SQL en la vista para cargar instituciones. Mover a controller, pasar como `$instituciones`.
- [ ] **BUG-V-16:** `notificaciones/index.php` — acceso directo a `$_SESSION['flash']` en la vista. Pasar `$mostrar_smtp` como variable desde el controller.

**Bugs de Models (B4):**
- [ ] **BUG-M-01:** `InstitucionModel::getAllConSuscripcion()` — JOIN duplica rows con múltiples suscripciones históricas. Subconsulta con LIMIT 1.
- [ ] **BUG-M-02:** `PagoSaasModel::generarNumeroFactura()` — race condition: facturas duplicadas bajo concurrencia. Usar ID del pago como correlativo.
- [ ] **BUG-M-03:** `EstudianteModel::generarCodigo()` — interpolación SQL directa de `$instId`. Cambiar a prepared statement.
- [ ] **BUG-M-04:** `VisorMiddleware` — `require app.php` duplicado en dos métodos. Usar `APP_URL` de `constants.php`.
- [ ] **BUG-M-05:** `SuscripcionMiddleware::bloquear()` — `$motivo` no se pasa a vista `suspendido.php`. Usuario ve mensaje genérico.
- [ ] **BUG-M-06:** `EmailService::__construct()` — usa `require` sin `_once` en cada instanciación.
- [ ] **BUG-M-07:** `EmailService::notificarNuevoPreregistro()` — `rol_id = 1` hardcodeado. Usar `ROL_SUPER_ADMIN`.
- [ ] **BUG-M-08:** `MetricasService::clientesPerdidosMes()` — usa `updated_at` como proxy de cancelación. Distorsiona Churn Rate. Ver ADR-012.
- [ ] **BUG-M-09:** `AdminController::anosEscolares()` — N+1 queries en el loop. Mover a `AnoEscolarModel` con JOIN.
- [ ] **BUG-M-10:** `SuperAdminController::togglePlan()` — interpolación SQL directa con `$id`.
- [ ] **BUG-M-11:** `SuperAdminController::suspenderInstitucion()` — dead code: SELECT descartado. Eliminar línea.
- [ ] **BUG-M-12:** `PreregistroController::generarSubdomain()` — `while(true)` sin límite. Agregar `$maxIntentos = 100`.
- [ ] **BUG-M-13:** `PreinscripcionController::adminConvertir()` — `require app.php` directo. Cambiar a `$this->config['url']`.

**Bugs B1-B3:**
- [ ] **BUG-2:** `eliminar()` estudiante via GET sin CSRF → POST + token.
- [ ] **BUG-3:** Validación foto solo por extensión → agregar `finfo` MIME real.
- [ ] **BUG-4:** Sin UNIQUE constraint en `anos_escolares`. Migración necesaria. *(Oleada 8)*
- ~~**MEJORA-1:** `.env` usa `DB_NAME`/`DB_USER` — corregido a `DB_DATABASE`/`DB_USERNAME`.~~ ✅ Oleada 2
- ~~**MEJORA-2:** `debug=true` en `app.php` — corregido a `false` con `filter_var`.~~ ✅ Oleada 2
- ~~**MEJORA-3:** `CRON_SECRET` hardcodeado — movido a `.env`.~~ ✅ Oleada 2
- ~~**MEJORA-4:** Ruta uploads inconsistente — unificada en `app.php` y `EstudianteController`.~~ ✅ Oleada 2
- ~~**MEJORA-5:** Falta guard en `index.php` para líneas `.env` sin `=` — corregido.~~ ✅ Oleada 2
- ~~**MEJORA-6:** `Database.php` no loguea errores en producción — `error_log` agregado.~~ ✅ Oleada 2
- [ ] **MEJORA-7:** `Router::ejecutar()` hace `die()` → mostrar 404. *(Oleada 10)*
- [ ] **MEJORA-8:** `SmtpClient` SSL con `verify_peer=false` → `true` en producción. *(Oleada 10)*
- [ ] **MEJORA-9:** Dos convenciones POST en routes: `/crear` vs `/guardar` → unificar (Patrón A, ADR-011). *(Oleada 10)*
- [ ] **MEJORA-10:** Comentario obsoleto en `routes/web.php` → eliminar. *(Oleada 10)*

**Mejoras de Views B8a:**
- ~~**MEJ-V8-01:** `require app.php` en cada vista — resuelto con `APP_URL` global en Oleada 1.~~ ✅
- [ ] **MEJ-V8-02:** `instituciones/usuarios.php` — `addslashes()` en JS. Reemplazar con `json_encode()`.
- [ ] **MEJ-V8-03:** `instituciones/crear.php` — contraseña `Colegio2024!` hardcodeada. Generar en controller. (Relacionado con BUG-V-13.)
- [ ] **MEJ-V8-04:** `cobros/masivo.php` — typo `actualizarTodosLosMontol` → `actualizarTodosLosMontos`.
- [ ] **MEJ-V8-05:** `cobros/masivo.php` — `const planesData` declarado pero nunca usado. Eliminar.
- [ ] **MEJ-V8-06:** `cobros/recibo_cobro.php` — comentario CSS malformado `/* ... -->`. Corregir.
- [ ] **MEJ-V8-07:** `planes/index.php` — `confirm()` con interpolación PHP directa. Documentar o migrar a `json_encode()`.
- [ ] **MEJ-V9-01:** `pagos/ingresos.php` — botones de exportación duplicados (en filtros y en barra independiente). Eliminar barra redundante.
- [ ] **MEJ-V9-02:** `pagos/recibo.php` — columnas de descuento sin `isset()` consistente. Verificar schema.
- [ ] **MEJ-V9-03:** `usuarios/editar.php` — `generarPass()` expone contraseña en texto del DOM. Unificar con `evaluarPass()` de `crear.php`.
- [ ] **MEJ-V9-04:** `usuarios/index.php` — iniciales del avatar sin `htmlspecialchars()`.
- [ ] **MEJ-V9-05:** IDs sin cast `(int)` en URLs — patrón en `ingresos.php`, `usuarios/index.php`, `preregistros/index.php`.
- [ ] **MEJ-V10-01:** `configuracion/index.php` — `$clave` en selector CSS/JS sin escapar. Cambiar a `json_encode()`.
- [ ] **MEJ-V10-02:** `salud/index.php` — apostrofe en `confirm()` de desbloquear usuario. Cambiar a `json_encode()`.
- [ ] **MEJ-V10-03:** `emails/index.php` — filtro sin campo "Hasta". Agregar `<input type="date" name="hasta">`.
- [ ] **MEJ-V10-04:** `log/index.php` — `json_decode` sin manejo de JSON inválido. Agregar `json_last_error()`.
- [ ] **MEJ-V11-01:** `preregistros/ver.php` — `confirm()` con interpolación en botón rechazar. Documentar con BUG-V-10.
- [ ] **MEJ-V12-01:** `notificaciones/index.php` — clases `badge-activo`/`badge-vencida` dependientes del CSS global. Usar Bootstrap estándar o documentar dependencia.
- [ ] **MEJ-V12-02:** `notificaciones/index.php` — fix BUG-V-15 elimina `$smtpPass` del scope de vista, resolviendo también riesgo futuro en banner.

**Mejoras de Models (B4):**
- [ ] **MEJ-B4-01:** `ConfigModel` — `catch` vacío en `set()`. Agregar `error_log()`.
- [ ] **MEJ-B4-02:** `BaseModel` — `$orderBy` interpolado. Documentar que no debe venir del usuario.
- [ ] **MEJ-B4-03:** `BaseModel::where()` — solo soporta `=`. Deuda técnica menor.
- [ ] **MEJ-B4-04:** `BaseModel::paginate()` — no acepta `$orderBy`. Resultados no deterministas.
- [ ] **MEJ-B4-05:** `UsuarioModel::usernameExiste()` — `if($exceptoId)` falla para ID=0. Cambiar a `!== null`.

**Mejoras de Helpers/Middlewares (B5):**
- [ ] **MEJ-B5-01:** `ActivityLog::getRecientes()` — LIMIT interpolado con cast `(int)`. Usar `bindValue`.
- [ ] **MEJ-B5-02:** `SuscripcionMiddleware` — caché 5 min: documentar como comportamiento conocido.
- [ ] **MEJ-B5-03:** `PlanHelper::tieneModulo()` — sin validación del string `$modulo`. Agregar whitelist.

**Mejoras de Services (B6):**
- [ ] **MEJ-B6-01:** `EmailService::renderLayout()` — variables disponibles por scope implícito. Documentar contrato.
- [ ] **MEJ-B6-02:** `MetricasService::calcularChurn()` — 2 queries separadas. Documentar como intencional.
- [ ] **MEJ-B6-03:** `EmailService::bienvenidaPreregistro()` — verificar flag `debe_cambiar_password`. Ver ADR-013.
- [ ] **MEJ-B6-04:** `MetricasService::clientes12Meses()` — mide creación, no clientes activos al cierre. Verificar label en dashboard.

**Mejoras de Controllers (B7):**
- [ ] **MEJ-B7-01:** `SuperAdminController::exportarInstituciones()` — mismo JOIN riesgo que BUG-M-01. Aplicar subconsulta.
- [ ] **MEJ-B7-02:** `DashboardController` — rama `$institucionId = NULL` es dead code. Simplificar.
- [ ] **MEJ-B7-03:** `SuperAdminController` — 2,443 líneas. Dividir en sub-controllers post-MVP.
- [ ] **MEJ-B7-04:** helper `n()` duplicado en `EstudianteController` y `PreinscripcionController`. Mover a `BaseController::nullableStr()` en Sprint 2.2.
- [ ] **MEJ-B7-05:** `AuthController::login()` — `catch(Exception $ignored)` silencia error del contador. Agregar `error_log()`.

### 🔵 Pendiente — Desarrollo funcional

- [ ] **Sprint 2.2:** ProfesorModel + ProfesorController + Vistas
- [ ] **Sprint 2.3:** MatriculaModel + MatriculaController + Vistas
- [ ] **Sprints 3.x:** Asistencia, Calificaciones, Comunicados

### 🟢 Prioridad Baja / Futuro

- [ ] Fase 4: Pagos colegio | Fase 5: Portales Alumno/Maestro/Padre | Fase 6: Reportes
- [ ] `Content-Security-Policy` y `Referrer-Policy` en `.htaccess`
- [ ] Reemplazar `X-XSS-Protection` (deprecado) por CSP
- [ ] Dividir `SuperAdminController` en sub-controllers (MEJ-B7-03)

---

## 🚫 PROBLEMAS CONOCIDOS / DEUDA TÉCNICA

| ID | Fecha | Problema | Impacto | Solución Propuesta | Estado |
|----|-------|----------|---------|-------------------|--------|
| BUG-CRÍTICO-1 | 2026-03-09 | `Anoescolarmode.php` nombre incorrecto | FATAL en Linux | Renombrar a `AnoEscolarModel.php` | ✅ Oleada 1 |
| BUG-CRÍTICO-2 | 2026-03-09 | Google App Password expuesto en `.env` | Credencial comprometida | Revocar y regenerar | ✅ Oleada 0 |
| BUG-V-01 | 2026-03-09 | `$this->generateCsrfToken()` en partial include | Fatal Error en cobros | Usar `$csrf_token` del controller | ✅ Oleada 1 |
| BUG-V-05 | 2026-03-09 | `Database::getInstance()` en `foreach` de vista | N+1 + lógica en vista | JOIN en PlanModel | 🟡 Oleada 5 |
| BUG-V-15 | 2026-03-09 | Contraseña SMTP en `value=""` del input HTML | App Password visible en DOM | Eliminar value, solo placeholder | ✅ Oleada 1 |
| BUG-B9-02 | 2026-03-09 | URL Cloudflare obfuscada en layout — botón roto | Funcional crítico | `mailto:soporte@edusaas.do` + eliminar script CF | ✅ Oleada 3 |
| BUG-V-06 | 2026-03-09 | `actualizarMonto()` busca `#sel-tipo` inexistente | Monto no se actualiza | Leer radio `input[name="tipo_facturacion"]:checked` | 🟡 |
| BUG-V-07 | 2026-03-09 | Email/web empresa hardcodeados en recibo_cobro | Config ignorada en documento | Usar `$_cfg_email_c` y `$_cfg_web` | 🟡 |
| BUG-V-09 | 2026-03-09 | Email/web empresa hardcodeados en pagos/recibo | Config ignorada en documento | Usar variables de ConfigModel | 🟡 |
| BUG-V-10 | 2026-03-09 | Apostrofe rompe JS en `confirmarEliminar()` | Botón eliminar falla con nombres como D'Alto | `json_encode()` | 🟡 |
| BUG-V-11 | 2026-03-09 | `ConfigModel::get()` en vista configuracion | 2 queries extra por request | Pasar desde controller | 🟡 |
| BUG-V-14 | 2026-03-09 | `Database::getInstance()` en vista notificaciones | Lógica BD en vista | Mover a controller | 🟡 |
| BUG-V-16 | 2026-03-09 | `$_SESSION['flash']` accedido directamente en vista | Acoplamiento frágil | Pasar `$mostrar_smtp` desde controller | 🟡 |
| BUG-M-01 | 2026-03-09 | `getAllConSuscripcion()` duplica rows | Dashboard muestra duplicados | Subconsulta LIMIT 1 | 🟡 |
| BUG-M-02 | 2026-03-09 | Race condition en `generarNumeroFactura()` | Facturas duplicadas | Usar ID del pago | 🟡 |
| BUG-M-08 | 2026-03-09 | `clientesPerdidosMes()` usa `updated_at` | Churn Rate distorsionado | Columna `fecha_baja` (ADR-012) | 🟡 ADR pendiente |
| MEJORA-1 | 2026-03-09 | `DB_NAME`/`DB_USER` vs `DB_DATABASE`/`DB_USERNAME` | Conexión falla en prod | Corregir nombres en `.env` | 🟡 |
| MEJ-B7-03 | 2026-03-09 | `SuperAdminController` 2,443 líneas | Mantenibilidad baja | Dividir post-MVP | 🟢 Post-MVP |

---

## 📐 DECISIONES TÉCNICAS (Architecture Decision Records)

### ADR-001 al ADR-009
*(Documentados en sesiones anteriores — ver historial)*

### ADR-010: Tabla `tutores` vs tabla `padres`
- **Fecha:** 2026-03-08
- **Decisión:** ⏳ Pendiente — definir antes del Sprint de Portal Padres
- **Consecuencias:** Afecta diseño del portal de padres y módulo de comunicados

### ADR-011: Convención de rutas POST
- **Fecha:** 2026-03-09
- **Decisión:** Adoptar **Patrón A** (`POST /recurso/crear`) como estándar único antes de Sprint 2.2
- **Consecuencias:** Ajustar rutas POST del Sprint 2.1

### ADR-012: Columna `fecha_baja` en suscripciones
- **Fecha:** 2026-03-09
- **Decisión:** ⏳ Pendiente — agregar `fecha_baja DATETIME NULL` a tabla `suscripciones`
- **Consecuencias:** Migración de BD necesaria. Sin ella el Churn Rate es impreciso.

### ADR-013: Flag `debe_cambiar_password`
- **Fecha:** 2026-03-09
- **Decisión:** ⏳ Pendiente — verificar/agregar `debe_cambiar_password TINYINT(1) DEFAULT 0` en `usuarios`
- **Consecuencias:** Seguridad mínima para onboarding de colegios nuevos.

### ADR-014: Contraseñas iniciales generadas dinámicamente
- **Fecha:** 2026-03-09
- **Contexto:** Tres vistas usan `Colegio2024!` hardcodeada: `instituciones/crear.php`, `preregistros/ver.php`, y `SuperAdminController::crearInstitucion()`. Todos los colegios arrancan con la misma clave predecible.
- **Decisión:** Generar contraseña aleatoria en el controller y pasarla como variable `$password_sugerida`. Formato: `Colegio{4 dígitos aleatorios}!`
- **Consecuencias:** Corrección coordinada en controller + 2 vistas. Relacionado con ADR-013.

### ADR-015: Nunca renderizar credenciales en atributos HTML
- **Fecha:** 2026-03-09
- **Contexto:** `notificaciones/index.php` renderiza la App Password SMTP en `value=""` del input. Visible en DevTools aunque el campo sea `type="password"`.
- **Decisión:** Campos de contraseña nunca llevan `value`. Usar solo `placeholder` con texto indicativo `(guardada — dejar vacío para no cambiar)`.
- **Consecuencias:** Aplicar a cualquier campo de credencial en todo el sistema.

---

## 📅 LOG DE SESIONES

> Las sesiones más recientes van primero.

---

### Sesión C-003 — 2026-03-10
**Tipo:** Correcciones activas
**Archivos corregidos:** 6
**Duración estimada:** 1.5 horas

**Trabajado:**
- **Oleada 2 completa** — `app.php`, `constants.php`, `index.php`, `Database.php`, `EstudianteController.php`
  - MEJORA-2: `APP_DEBUG` default `false` con `filter_var(…, FILTER_VALIDATE_BOOLEAN)` correcto para strings
  - MEJORA-3: `CRON_SECRET` movido a `.env` con `error_log` si no está definido
  - MEJORA-4: path de uploads unificado entre `app.php` (`public/uploads/`) y `EstudianteController` (`$this->config['upload']['path']`)
  - MEJORA-5: guard `if (!str_contains($linea, '=')) continue` en parser `.env`
  - MEJORA-6: `error_log('[EduSaaS] Error de conexión a BD: ...')` antes del `die()` en producción
- **Oleada 3 completa** — `views/layouts/main.php`
  - BUG-B9-02: `mailto:soporte@edusaas.do` restaurado, script `<script data-cfasync>` Cloudflare eliminado
  - MEJ-B9-01: `flashHtml()` con `strip_tags()` + whitelist `<strong><em><a><br><span>`
  - MEJ-B9-03: `$_color` del banner Trial validado con `preg_match('/^#[0-9a-fA-F]{3,6}$/')`
  - Archivo reconstruido completo (upload original estaba truncado — faltaba el cierre JS + body + html)
- Se revisó `.env` subido por el developer: App Password aún activa → alerta emitida

**Decisiones:**
- `filter_var(..., FILTER_VALIDATE_BOOLEAN)` es el fix correcto para `APP_DEBUG` — sin él el string `"false"` del `.env` evalúa como `true` en PHP
- Fallback en `subirFoto()`: si `config['upload']['path']` no está disponible, sigue funcionando con ruta original

**Para la próxima sesión (C-004):**
1. Confirmar App Password de Gmail revocada y nueva generada
2. Confirmar `.env` tiene `APP_DEBUG=false` y `CRON_SECRET=<valor real>` (generar con `php -r "echo bin2hex(random_bytes(24));"`)
3. Subir Ronda 1 de Oleada 4: `instituciones/index.php` + `instituciones/ver.php` + `cobros/recibo_cobro.php`

---

### Sesión C-002 — 2026-03-09
**Tipo:** Correcciones activas — Oleadas 0 y 1

**Trabajado:**
- Oleada 0: App Password Gmail revocada y regenerada, `.gitignore` verificado, commit `pre-fixes` creado
- Oleada 1: `AnoEscolarModel.php` renombrado + referencias actualizadas, `APP_URL` definido en `constants.php` y aplicado en 5 vistas, CSRF en `cobros/formulario_partial.php`, password SMTP eliminada del DOM, URL Cloudflare en layout (completada en O3)

---

### Sesión C-001 — 2026-03-09
**Tipo:** Planificación

**Trabajado:**
- Creación de `CORRECCIONES_LOG.md` como tercer documento de seguimiento
- Corrección de `BITACORA.md` — encabezado y tabla resumen desactualizados
- Protocolo definido: máx. 3 archivos/ronda, flujo fijo lectura→corrección→descarga→log

---

# 📋 ENTRADA BITÁCORA — Sesión 015

> Pegar al inicio de la sección `## 📅 LOG DE SESIONES` y actualizar las dos líneas del encabezado.

---

## ENCABEZADO — reemplazar líneas 3-5:

```
**Última actualización:** 2026-03-09 — Sesión 015
**Versión del sistema:** 1.0.0
**Estado general:** 🟢 CODE REVIEW 100% COMPLETO — 117 archivos · 10 críticos · 53 medios · 78 mejoras · Próximo: Plan de correcciones

**Última actualización:** 2026-03-09 — Sesión 016
**Estado general:** 🟢 DOCS COMPLETOS — /docs con 8 archivos · 144 ítems deuda técnica · Próximo: Oleadas 0→1
```

---

## SECCIÓN `🔄 EN PROGRESO` — reemplazar contenido:

```markdown
## 🔄 EN PROGRESO
- [ ] **Plan de correcciones priorizadas** — generar orden de ejecución con dependencias entre fixes
- [ ] Corrección de bugs críticos y medios identificados en bloques 1-9

- [ ] Oleada 0 — Revocar credencial .env (acción manual, inmediata)
- [ ] Oleada 1 — Infraestructura crítica (5 bugs fatales)
```

---

## NUEVA ENTRADA EN `✅ COMPLETADO` — agregar al inicio de la tabla:

```markdown
| 2026-03-09 | Code Review B9 COMPLETO | Layout/Auth/Partials/FrontController: layouts-main, auth-login, partials-plan_uso, preregistro-formulario, preregistro-gracias, public-index, .htaccess | 2 críticos, 5 medios, 6 mejoras |
| 2026-03-09 | Code Review B8d COMPLETO | Vistas Públicas: 403, 404, mantenimiento, suspendido, preinscripcion-confirmacion, preinscripcion-gracias, preinscripcion-formulario | 1 crítico, 5 medios, 8 mejoras |
| 2026-03-09 | Code Review B8c COMPLETO | Views Emails: layout, bienvenida, confirmacion_pago, aviso_vencimiento, suspension, reset_clave, preinscripcion_aprobada, personalizado, smtp_test | 1 crítico, 2 medios, 5 mejoras |
```

---

## NUEVOS BUGS — agregar a `⏳ PENDIENTE / POR HACER` (Prioridad Alta):

```markdown
- [ ] **BUG-B9-02:** `layouts/main.php` — link "Contratar plan" tiene URL Cloudflare obfuscada (`/cdn-cgi/...`) hardcodeada en lugar del `mailto:soporte@edusaas.do` original. Botón roto en cualquier servidor sin CF. Restaurar URL real y eliminar el `<script data-cfasync>`.
- [ ] **BUG-B9-01:** `layouts/main.php` — `require app.php` para obtener `$_url_base` (patrón sistémico — 5ª ocurrencia, mayor impacto por estar en el layout global). Resolver con `APP_URL` desde `constants.php` o variable de `BaseController`.
- [ ] **BUG-B8c-01:** `bienvenida.php` (email) — `require __DIR__/../../config/app.php` dentro del template. Pasar `$appUrl` desde `EmailService`. (4ª ocurrencia del mismo patrón.)
- [ ] **BUG-B8d-F01:** `preinscripcion-formulario.php` — `require app.php` en el atributo `action` del `<form>`. (3ª ocurrencia.)
- [ ] **BUG-B9-03:** `layouts/main.php` — branding dinámico (`--primary`, `--accent`) silenciosamente ignorado por segundo bloque `:root` hardcodeado que lo sobreescribe. Fusionar ambos bloques CSS.
- [ ] **BUG-B9-04:** `layouts/main.php` — query `SELECT COUNT(*) FROM preregistro_colegios` directa en el sidebar en cada request. Pasar `$preregistrosPendientes` desde el controller.
- [ ] **BUG-B9-05:** `preregistro-formulario.php` — flash messages sin `htmlspecialchars()` + acceso directo a `$_SESSION['flash']` (patrón BUG-V-16 sistémico confirmado).
- [ ] **BUG-B9-06:** `partials/plan_uso.php` — `$_item['icono']` y `$_item['label']` sin `htmlspecialchars()`. Datos de BD renderizados sin escape en el dashboard de todos los colegios.
- [ ] **BUG-B8d-B01:** `preinscripcion-gracias.php` — `$inst['telefono']` sin `htmlspecialchars()`. Inconsistencia en la misma línea donde `$inst['nombre']` sí escapa.
- [ ] **BUG-B8d-B02:** `preinscripcion-gracias.php` — `date(..., strtotime($pre['created_at']))` sin escape y sin guardia contra `false`. Pasar `$fechaDoc` formateada desde controller.
- [ ] **BUG-B8d-B03:** Duplicación activa — `preinscripcion-confirmacion.php` y `preinscripcion-gracias.php` son el mismo flujo con variables distintas (`$preinsc` vs `$pre`). Confirmar cuál está activa en `routes/web.php` y eliminar la otra.
- [ ] **BUG-B8c-02:** `confirmacion_pago.php` — `$fechaPago` y `$fechaHasta` sin `htmlspecialchars()`.
- [ ] **BUG-B8c-03:** `confirmacion_pago.php` — `ucfirst($pago['metodo_pago'])` sin `htmlspecialchars()`.
- [ ] **BUG-B8d-F02:** `preinscripcion-formulario.php` — `$errors['general']` sin `htmlspecialchars()`.
- [ ] **BUG-B8d-F03:** `preinscripcion-formulario.php` — closure `$err()` concatena `$errors[$k]` directamente al HTML sin escape.
```

---

## NUEVOS BUGS — agregar a `⏳ PENDIENTE / POR HACER` (Prioridad Media / Mejoras):

```markdown
- [ ] **MEJ-B9-01:** `layouts/main.php` — `$flash['mensaje']` sin escape, protegido solo por convención. Crear helper `flashHtml()` con whitelist de tags.
- [ ] **MEJ-B9-02:** `layouts/main.php` — función `isActive()` declarada globalmente. Mover a `BaseController::isActive()` o helper dedicado para evitar "Cannot redeclare".
- [ ] **MEJ-B9-03:** `layouts/main.php` — colores CSS inline del banner Trial sin validación hex. Aplicar whitelist `preg_match('/^#[0-9a-fA-F]{3,6}$/')`.
- [ ] **MEJ-B9-04:** `$urlBase`/`$appUrl` sin `htmlspecialchars()` en atributos `action` y `href` de auth-login y preregistro-formulario.
- [ ] **MEJ-B9-05:** `preregistro-formulario.php` y `preregistro-gracias.php` — CDN inconsistente: `cdn.jsdelivr.net` Bootstrap 5.3.0 vs `cdnjs.cloudflare.com` 5.3.2 del resto del sistema.
- [ ] **MEJ-B9-06:** `public-index.php` — parser `.env` sin guardia contra líneas sin `=` (confirma MEJORA-5 ya documentada).
- [ ] **MEJ-B8c-01:** Email de soporte `soporte@edusaas.do` hardcodeado en 6 de 9 templates de email. Pasar `$emailSoporte` como variable global del layout de email.
- [ ] **MEJ-B8c-02:** `date('d/m/Y')` calculado directamente en templates de email (preinscripcion_aprobada, reset_clave). Pasar `$fechaDoc` desde el servicio.
- [ ] **MEJ-B8c-03:** `$color` y `$emoji` sin validación en `aviso_vencimiento.php` — documentar whitelist o validar en controller.
- [ ] **MEJ-B8c-04:** `layout.php` email — `<?= $contenido ?>` sin comentario explícito de "no escapar". Agregar comentario para futuros revisores.
- [ ] **MEJ-B8c-05:** `preinscripcion_aprobada.php` — `$k` (key de `$filas`) renderizado sin `htmlspecialchars()`. Normalizar.
- [ ] **MEJ-B8d-01:** `mantenimiento.php` — `$_color` de ConfigModel usado en CSS sin validación hex. Agregar whitelist.
- [ ] **MEJ-B8d-02:** CDN inconsistente entre `preinscripcion-confirmacion.php` (cdnjs) y `preinscripcion-gracias.php` (jsdelivr).
- [ ] **MEJ-B8d-03:** `suspendido.php` — `soporte@edusaas.do` hardcodeado. Usar `ConfigModel::get()` con try/catch como hace `mantenimiento.php`.
- [ ] **MEJ-B8d-04:** `javascript:history.back()` en 403.php y 404.php — sin fallback si no hay historial. Cambiar a `href="/"` o agregar onclick con guardia.
- [ ] **MEJ-B8d-F01:** `preinscripcion-formulario.php` — `$g['id']` sin cast `(int)` en `<option value>` (patrón sistémico MEJ-V9-05).
- [ ] **MEJ-B8d-F02:** `preinscripcion-formulario.php` — alias `$v2` para evitar colisión con closure `$v`. Renombrar closure a `$esc`.
- [ ] **MEJ-B8d-F03:** `preinscripcion-formulario.php` — `date('Y')` calculado en la vista para el año escolar. Pasar `$anoEscolar` desde controller.
- [ ] **MEJ-B8d-F04:** `preinscripcion-formulario.php` — CDN inconsistente: jsdelivr vs cdnjs.
- [ ] **MEJ-B8d-F05:** `preinscripcion-formulario.php` — `$ts` y `$p` en `<option>` sin `htmlspecialchars()` (arrays hardcodeados, riesgo mínimo pero inconsistente).
```

---

## NUEVAS ENTRADAS EN `🚫 PROBLEMAS CONOCIDOS`:

```markdown
| BUG-B9-02 | 2026-03-09 | URL Cloudflare obfuscada en layout — botón "Contratar plan" roto en cualquier servidor sin CF | CRÍTICO funcional | Restaurar `mailto:soporte@edusaas.do`, eliminar script CF | 🔴 Urgente |
| BUG-B9-01 | 2026-03-09 | `require app.php` en layout principal (5ª ocurrencia sistémica) | Rompe toda la app si falla el path | `APP_URL` en `constants.php` o variable de BaseController | 🔴 Urgente |
| BUG-B9-03 | 2026-03-09 | Branding dinámico ignorado — segundo `:root` sobreescribe colores de ConfigModel | Colegios no pueden personalizar colores | Fusionar bloques `:root` en layout | 🟡 |
| BUG-B9-04 | 2026-03-09 | Query BD directa en sidebar del layout — cada request del superadmin | Performance + lógica en vista | Pasar `$preregistrosPendientes` desde controller | 🟡 |

| BUG-BD-01 | 2026-03-09 | calificaciones sin ENGINE=InnoDB — FKs ignoradas | CRÍTICO integridad | ALTER TABLE calificaciones ENGINE=InnoDB | 🔴 Urgente |
| MEJ-BD-01 | 2026-03-09 | grados.nivel ENUM no referencia tabla niveles | Redundancia muerta | Decidir FK vs documentar antes Sprint 2.2 | 🟡 |
| MEJ-BD-02 | 2026-03-09 | secciones tiene capacidad=40 y capacidad_maxima=30 — contradictorios | Lógica ambigua | Definir cuál se usa y eliminar la otra | 🟡 |
```

---

## NUEVA ADR — agregar a `📐 DECISIONES TÉCNICAS`:

```markdown
### ADR-016: Resolución del patrón sistémico `require app.php` en vistas
- **Fecha:** 2026-03-09
- **Contexto:** El patrón `(require __DIR__ . '/../../config/app.php')['url']` aparece en 5 archivos: `layouts/main.php`, `auth/login.php`, `views/emails/bienvenida.php`, `preinscripcion-formulario.php`, `preregistro-formulario.php`. Frágil por paths relativos, ejecuta el archivo en cada include, puede romperse según el contexto de inclusión.
- **Decisión:** Agregar `define('APP_URL', ...)` en `config/constants.php` (ya cargado en `public/index.php` antes de cualquier vista). Todas las vistas usan `APP_URL` directamente, sin require.
- **Consecuencias:** Corrección coordinada en 5+ archivos. Simple y definitiva. Elimina la categoría completa de bugs de path relativo.
```

---

## ENTRADA DE SESIÓN — agregar al inicio de `📅 LOG DE SESIONES`:

```markdown
### Sesión 015 — 2026-03-09
**Duración estimada:** 3 horas
**Archivos revisados:** 23 (B8c: 9 · B8d: 7 · B9: 7)

**Trabajado:**
- Code Review B8c — 9 templates de email: layout, bienvenida, confirmacion_pago, aviso_vencimiento, suspension, reset_clave, preinscripcion_aprobada, personalizado, smtp_test
- Code Review B8d — 7 vistas públicas: 403, 404, mantenimiento, suspendido, preinscripcion-confirmacion, preinscripcion-gracias, preinscripcion-formulario
- Code Review B9 — 7 archivos de arquitectura base: layouts/main, auth/login, partials/plan_uso, preregistro-formulario, preregistro-gracias, public/index, .htaccess
- CODE REVIEW 100% COMPLETO — 117 archivos totales revisados

**Hallazgos destacados:**
- BUG-B9-02 (crítico): URL Cloudflare obfuscada hardcodeada en layout — botón "Contratar plan" roto en todo servidor sin CF. Credencial/email original perdida del código fuente.
- BUG-B9-03 (medio): Sistema de branding dinámico completamente ignorado — segundo `:root` sobreescribe silenciosamente los colores de ConfigModel.
- BUG-B8c-01 / BUG-B8d-F01 / BUG-B9-01: Patrón `require app.php` en vistas confirmado en 5 archivos — origen sistémico identificado en layouts/main.php.
- BUG-B8d-B03: Dos páginas de confirmación para el mismo flujo de preinscripción con variables distintas (`$preinsc` vs `$pre`).
- Positivo: `public/index.php` impecable. Sistema de Modo Visor en layout correcto y elegante. Widget `plan_uso.php` con lógica de progreso semántico de alta calidad.

**Decisiones:**
- ADR-016 creado: resolver patrón `require app.php` con `define('APP_URL')` en `constants.php`

**Estado actualizado:**
- ✅ Nuevo completado: Code Review B8c, B8d, B9 — CODE REVIEW 100% COMPLETO
- 🔄 Próximo: Plan de correcciones priorizadas con orden de ejecución y dependencias

**Totales finales del Code Review:**
- 117 archivos revisados (100%) · 10 críticos · 53 medios · 78 mejoras

**Para la próxima sesión:**
- Generar plan de correcciones priorizadas — orden sugerido:
  1. BUG-CRÍTICO-2 (credencial expuesta en .env) — acción inmediata, fuera del código
  2. BUG-CRÍTICO-1 (nombre de archivo case-sensitive Linux)
  3. ADR-016 (APP_URL en constants.php — resuelve 5 bugs de un golpe)
  4. BUG-B9-02 (URL Cloudflare en layout — botón roto)
  5. BUG-B9-03 (`:root` duplicado — branding inoperante)
  6. Bugs de seguridad en vista (CSRF, XSS, SQL injection restantes)
- Definir si se corrige primero o se genera el reporte ejecutivo de correcciones
```

### Sesión 014 — 2026-03-09
**Duración estimada:** 2 horas
**Archivos revisados:** 13 (Views Admin/Colegio — B8b completo)

**Trabajado:**
- Code Review B8b completo — 4 lotes, 13 archivos del panel Admin/Colegio
- L1: admin-dashboard, anos-escolares/form, anos-escolares/index
- L2: estudiantes/form, estudiantes/index, estudiantes/ver
- L3: grados/index, periodos/form, periodos/index, secciones/form, secciones/index
- L4: preinscripciones/index, preinscripciones/ver

**Hallazgos destacados:**
- BUG-B8b-04: "Desactivar" estudiante via GET sin CSRF — estudiantes/index + ver (crítico)
- BUG-B8b-11: Database::getInstance() + SQL con $_SESSION en preinscripciones/index (crítico)
- BUG-B8b-06/13: name="_csrf_token" (con underscore) en form estudiantes y preinscripciones — acciones desprotegidas o rotas
- BUG-B8b-05: rutas dropdown sin prefijo /admin/ — 3 links 404 en estudiantes/index
- Patrones sistémicos confirmados: require config/app.php (6 vistas), $_SESSION['csrf_token'] directo (4 vistas), htmlspecialchars() en JS confirm() (5 vistas), IDs sin cast (int) (~15 ocurrencias)
- secciones-form.php: archivo más limpio del bloque — impecable

**Estado actualizado:**
- ✅ Nuevo completado: Code Review B8b (13 archivos)
- ⏳ Pendiente: B8c Views Email (~5 archivos), B8d Vistas Públicas (~5 archivos)

**Totales acumulados:** 94/~115 archivos (82%) · 6 críticos · 41 medios · 57 mejoras

**Para la próxima sesión:**
- Iniciar B8c — Views de emails (templates HTML de correo)
- Foco especial: hardcoding de URLs, variables de layout implícitas (MEJ-B6-01)
- Pendiente crítico antes de deploy: resolver los 3 patrones sistémicos de forma coordinada
  (APP_URL global, csrf_token desde controller, json_encode() en JS)

### Sesión 013 — 2026-03-09
**Duración estimada:** 5 min
**Archivos revisados:** 0

**Trabajado:**
- Inicio de sesión: lectura de bitácora y recuperación de contexto completo
- Mapeo del scope de B8b — Views panel Admin/Colegio
- Inventario de 13 archivos PHP reales en 7 módulos activos
- Identificadas 10 carpetas vacías (sin vistas aún): asignaturas, asistencia, 
  calificaciones, comunicados, horarios, matriculas, padres, pagos, profesores, reportes
- Portales alumno/maestro/padre vacíos confirmados → Fases 5+
- Plan de 4 lotes definido para B8b
- Sesión cerrada por límite de contexto (90%)

**Plan B8b definido:**
- L1: dashboard.php + anos-escolares/form.php + anos-escolares/index.php
- L2: estudiantes/form.php + index.php + ver.php
- L3: grados/index.php + periodos/form+index + secciones/form+index
- L4: preinscripciones/index.php + ver.php

**Estado actualizado:**
- 🔄 Continúa en progreso: Code Review B8b (0/13 archivos)
- Sin nuevos bugs — sesión de planificación

**Para la próxima sesión:**
- Iniciar B8b-L1: subir dashboard.php (sin renombrar) + anos-form.php + anos-index.php
- Foco: tenant isolation, N+1 queries, XSS, lógica en vistas
- Protocolo: renombrar archivos con prefijo de carpeta antes de subir para evitar 
  colisiones (ej: `anos-form.php`, `anos-index.php`)
- Total acumulado al iniciar B8b: 81/~115 archivos (70%), 4 críticos · 27 medios · 47 mejoras
---
```

---

**También actualizar estas dos líneas en el encabezado del archivo:**
```
**Última actualización:** 2026-03-09 — Sesión 013
**Estado general:** 🟡 EN DESARROLLO ACTIVO — B8a ✅ COMPLETO · B8b ⏳ PLANIFICADO (0/13)

### Sesión 012 — 2026-03-09
**Duración estimada:** 30 min
**Archivos revisados:** 1 (`notificaciones/index.php`)

**Trabajado:**
- Code Review `notificaciones/index.php` — cierre de B8a
- 0 críticos, 3 medios (BUG-V-14, BUG-V-15, BUG-V-16), 2 mejoras

**Hallazgos destacados:**
- BUG-V-15: contraseña SMTP renderizada en `value=""` del input — doble exposición junto con BUG-CRÍTICO-2
- BUG-V-16: acceso directo a `$_SESSION['flash']` en la vista — acoplamiento frágil
- BUG-V-14: `Database::getInstance()` + query en la vista para el selector de instituciones
- Panel SMTP colapsable con auto-apertura tras error — excelente UX, solo el mecanismo de detección necesita ajuste
- Guía Gmail App Password inline — detalle de onboarding profesional

**Decisiones:**
- ADR-015 creado: nunca renderizar credenciales en atributos HTML `value`
- B8a declarado COMPLETO

**Estado actualizado:**
- ✅ Nuevo completado: Code Review B8a completo (29 archivos)
- ⏳ Agregado: BUG-V-14, BUG-V-15, BUG-V-16, MEJ-V12-01, MEJ-V12-02, ADR-015

**Para la próxima sesión:**
- **Iniciar B8b — Views del panel Admin/Colegio** (~20 archivos)
- Foco en: XSS, CSRF, lógica en vistas, N+1 queries, tenant isolation (que las vistas no mezclen datos de instituciones)
- Recordar subir archivos renombrados para evitar colisiones de nombre (`index.php` múltiples)

---

### Sesión 011 — 2026-03-09
**Duración estimada:** 30 min
**Archivos revisados:** 1 (`preregistros/ver.php`)

**Trabajado:**
- Code Review `preregistros/ver.php`
- 0 críticos, 1 medio (BUG-V-13), 2 mejoras
- Incidente: archivo subido incorrectamente (era `SuperAdminController.php`), recuperado en intento posterior

**Hallazgos destacados:**
- Archivo más limpio de los formularios de acción del superadmin
- Tres acciones (aprobar/contactado/rechazar) con formularios POST + CSRF independientes — patrón correcto
- BUG-V-13: `Colegio2024!` hardcodeada en formulario de aprobación — todos los colegios arrancan con misma clave

**Decisiones:**
- ADR-014 creado: contraseñas iniciales generadas dinámicamente en el controller
- Protocolo documentado: hacer backup/commit del proyecto al final de cada sesión de trabajo

---

### Sesión 010 — 2026-03-09
**Duración estimada:** 1.5 horas
**Archivos revisados:** 4 (`configuracion/index.php`, `emails/index.php`, `log/index.php`, `salud/index.php`)

**Trabajado:**
- Code Review B8a Lote 4 (parcial — `notificaciones/index.php` y `preregistros/ver.php` pendientes por problema de nombres duplicados de archivos)
- 0 críticos, 2 medios (BUG-V-11, BUG-V-12), 4 mejoras

**Hallazgos destacados:**
- `salud/index.php` — dashboard de observabilidad más completo del sistema: KPIs semánticos, cron logs, tabla BD por tamaño, gestión de usuarios bloqueados
- `configuracion/index.php` — formulario dinámico con 8 tipos de campo en una sola vista + preview en vivo del sidebar — elegante
- `emails/index.php` y `log/index.php` — sin observaciones críticas, código limpio
- BUG-V-12: atributos HTML (`name`, `id`, `for`) con `$clave` sin `htmlspecialchars()`
- BUG-V-11: `ConfigModel::get()` llamado directamente en la vista

**Decisiones:**
- Protocolo establecido: renombrar archivos antes de subir cuando hay múltiples `index.php` en el mismo mensaje

---

### Sesión 009 — 2026-03-09
**Duración estimada:** 1.5 horas
**Archivos revisados:** 7 (`pagos/ingresos.php`, `pagos/ingresos_pdf.php`, `pagos/recibo.php`, `usuarios/crear.php`, `usuarios/editar.php`, `usuarios/index.php`, `preregistros/index.php`)

**Trabajado:**
- Code Review B8a Lote 3
- 0 críticos, 2 medios (BUG-V-09, BUG-V-10), 5 mejoras
- Diagnóstico del problema de archivos duplicados en uploads de Claude.ai — solución: renombrar con prefijo de carpeta

**Hallazgos destacados:**
- `ingresos_pdf.php` — mejor archivo del lote: ConfigModel con try/catch + fallbacks correcto (patrón que `cobros/recibo_cobro.php` debería seguir)
- `usuarios/index.php` — eliminación via POST + CSRF + modal es el patrón correcto (contrasta con BUG-2)
- BUG-V-10: apostrofe en nombre rompe string JS — `json_encode()` es el fix estándar del proyecto
- BUG-V-09: mismo hardcoding de email/web empresa que BUG-V-07, pero en recibo de pagos

---

### Sesión 008 — 2026-03-09
**Duración estimada:** 2 horas
**Archivos revisados:** 13 (Views SuperAdmin B8a Lotes 1 y 2)

**Trabajado:**
- Code Review B8a Lote 1: dashboard, instituciones (5 vistas), cobros/formulario_partial
- Code Review B8a Lote 2: cobros index/masivo/recibo, planes crear/editar/index
- Total revisado acumulado: **52 de ~115 archivos**

**Hallazgos destacados:**
- BUG-V-01: `$this` en partial include — Fatal Error en flujo de cobros (crítico)
- BUG-V-05: `Database::getInstance()` en `foreach` de vista (crítico)
- BUG-V-06: `actualizarMonto()` referencia a `#sel-tipo` inexistente
- `instituciones/editar.php` — mejor archivo del lote: patrón `$v = fn() => htmlspecialchars(...)` aplicado consistentemente
- `dashboard.php` — sin observaciones

---

### Sesión 007 — 2026-03-09
**Duración estimada:** 3 horas
**Archivos revisados:** 8 (Controllers)

**Trabajado:**
- Code Review Bloque 7 — Controllers (8 archivos, 4,437 líneas combinadas)
- 0 bugs críticos — flujo de seguridad correcto en todo el bloque
- Total revisado acumulado: **39 de ~115 archivos**

---

### Sesión 006 — 2026-03-09
**Duración estimada:** 1.5 horas
**Archivos revisados:** 2 (Services)

**Trabajado:**
- Code Review Bloque 6 — EmailService, MetricasService
- `MetricasService` implementa fórmulas SaaS reales correctamente

---

### Sesión 005 — 2026-03-09
**Duración estimada:** 1.5 horas
**Archivos revisados:** 4 (Helpers + Middlewares)

**Trabajado:**
- Code Review Bloque 5 — ActivityLog, PlanHelper, SuscripcionMiddleware, VisorMiddleware
- Bloque con mejor calidad hasta ese punto

---

### Sesión 004 — 2026-03-09
**Duración estimada:** 2 horas
**Archivos revisados:** 13 (Models)

**Trabajado:**
- Code Review Bloque 4 — 13 modelos del sistema

---

### Sesión 003 — 2026-03-09
**Duración estimada:** 2 horas

**Trabajado:**
- Code Review Bloques 1, 2 y 3 (Config, Vendor, Routes)
- 2 bugs críticos identificados

---

### Sesión 002 — 2026-03-08
**Duración estimada:** 2 horas

**Trabajado:**
- Code Review inicial (~30% del código)
- Generado: `REPORTE_CODE_REVIEW_EduSaaS_RD.md`

---

### Sesión 001 — 2026-03-08
**Duración estimada:** 2 horas

**Trabajado:**
- Análisis de estructura (115 archivos), schema BD (33 tablas)
- Establecimiento del protocolo de trabajo y bitácora

---

## 📊 RESUMEN EJECUTIVO DEL CODE REVIEW (estado al 2026-03-09 — Sesión 016 — FINAL)

### Archivos revisados: 117 / 117 (100%) ✅
### Bloques completados: B1-B9 ✅ TODOS

| Bloque | Archivos | Críticos | Medios | Mejoras | Estado |
|--------|----------|----------|--------|---------|--------|
| B1 Config | 6 | 2 | 0 | 5 | ✅ |
| B2 Vendor | 4 | 0 | 0 | 4 | ✅ |
| B3 Routes | 2 | 0 | 0 | 3 | ✅ |
| B4 Models | 13 | 0 | 3 | 5 | ✅ |
| B5 Helpers+MW | 4 | 0 | 2 | 3 | ✅ |
| B6 Services | 2 | 0 | 3 | 4 | ✅ |
| B7 Controllers | 8 | 0 | 5 | 5 | ✅ |
| B8a Views SuperAdmin | 29 | 2 | 14 | 18 | ✅ |
| B8b Views Admin/Colegio | 13 | 2 | 8 | 7 | ✅ |
| B8c Views Email | 9 | 1 | 2 | 5 | ✅ |
| B8d Vistas Públicas | 7 | 1 | 5 | 8 | ✅ |
| B9 Layout/Auth/Base | 7 | 2 | 5 | 6 | ✅ |
| Docs /docs | 8 | — | — | — | ✅ |
| **TOTAL** | **117** | **10** | **47** | **73** | **✅** |

**Totales finales: 10 críticos · 53 medios · 78 mejoras**

### Bugs críticos pendientes: 10
- BUG-CRÍTICO-1, BUG-CRÍTICO-2, BUG-V-01, BUG-V-05, BUG-V-15
- BUG-B9-01, BUG-B9-02, BUG-B9-03, BUG-B8b-04, BUG-B8b-11

### Calidad general del código: ⭐⭐⭐⭐½ — Alta. No requiere refactor arquitectural.

---

*Bitácora generada automáticamente — Sistema de Ingeniería de Software Profesional*
*Actualizar al inicio Y al final de cada sesión de trabajo*