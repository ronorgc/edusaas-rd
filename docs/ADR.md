# 📐 ARCHITECTURE DECISION RECORDS — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Total ADRs:** 16 (ADR-001 a ADR-016)

> Este archivo consolida todas las decisiones técnicas del proyecto.  
> Cada ADR documenta el contexto, la decisión tomada y las consecuencias aceptadas.  
> **Nunca borrar un ADR.** Si una decisión cambia, crear un nuevo ADR que lo reemplaza.

---

## 📖 Estado de ADRs

| ID | Título | Estado | Fecha |
|----|--------|--------|-------|
| ADR-001 | MVC casero sin framework | ✅ Vigente | Sesión 001 |
| ADR-002 | PDO como capa de acceso a datos | ✅ Vigente | Sesión 001 |
| ADR-003 | Multi-tenancy por `institucion_id` en cada tabla | ✅ Vigente | Sesión 001 |
| ADR-004 | Front Controller único via `.htaccess` | ✅ Vigente | Sesión 001 |
| ADR-005 | Bootstrap 5 sin framework JS | ✅ Vigente | Sesión 001 |
| ADR-006 | SMTP propio sin PHPMailer | ✅ Vigente | Sesión 001 |
| ADR-007 | Singleton para conexión de base de datos | ✅ Vigente | Sesión 001 |
| ADR-008 | Roles y permisos basados en `rol_id` | ✅ Vigente | Sesión 001 |
| ADR-009 | Target de despliegue: cPanel/Linux | ✅ Vigente | Sesión 001 |
| ADR-010 | Tabla `tutores` vs tabla `padres` | ⏳ Pendiente | 2026-03-08 |
| ADR-011 | Convención de rutas POST | ✅ Vigente | 2026-03-09 |
| ADR-012 | Columna `fecha_baja` en suscripciones | ⏳ Pendiente migración | 2026-03-09 |
| ADR-013 | Flag `debe_cambiar_password` | ⏳ Pendiente migración | 2026-03-09 |
| ADR-014 | Contraseñas iniciales generadas dinámicamente | ⏳ Pendiente implementación | 2026-03-09 |
| ADR-015 | Nunca renderizar credenciales en atributos HTML | ✅ Vigente | 2026-03-09 |
| ADR-016 | Resolución sistémica de `require app.php` en vistas | ⏳ Pendiente implementación | 2026-03-09 |

---

## ADR-001: MVC casero sin framework

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Proyecto dirigido a colegios en República Dominicana donde el hosting típico es cPanel compartido. Los frameworks modernos (Laravel, Symfony) requieren Composer, dependencias complejas y configuraciones que pueden ser difíciles de mantener en ese entorno. El equipo de desarrollo es pequeño.

**Decisión:**  
Implementar MVC artesanal. Front Controller en `public/index.php`, Router propio, BaseModel con Active Record simplificado, BaseController con helpers de renderizado y flash messages.

**Consecuencias:**
- ✅ Cero dependencias externas de framework — fácil de desplegar en cPanel
- ✅ Control total del código — sin magia de framework
- ✅ Curva de aprendizaje mínima para mantener
- ⚠️ Sin ORM: queries manuales con PDO — más código boilerplate
- ⚠️ Sin ecosistema de paquetes — cada utilidad se construye
- ⚠️ `SuperAdminController` ya tiene 2,443 líneas — deuda de tamaño (ver MEJ-B7-03)

---

## ADR-002: PDO como capa de acceso a datos

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Elegir entre MySQLi, PDO, u ORM completo para el acceso a la base de datos.

**Decisión:**  
PDO con prepared statements como estándar en todo el sistema. `Database::getInstance()` como Singleton que retorna la conexión PDO compartida por request.

**Consecuencias:**
- ✅ Prepared statements por defecto — protección contra SQL injection
- ✅ Portabilidad teórica entre motores de BD
- ⚠️ Varios bugs identificados donde se usa interpolación directa en lugar de `bindValue()` — ver BUG-M-03, BUG-M-10 en DEUDA_TECNICA.md
- ⚠️ `Database::getInstance()` siendo llamado desde vistas directamente — anti-patrón (ver BUG-V-05, BUG-V-14, BUG-B8b-11)

---

## ADR-003: Multi-tenancy por `institucion_id` en cada tabla

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Sistema SaaS con múltiples colegios clientes compartiendo la misma base de datos. Opciones: base de datos separada por tenant, schema separado, o columna discriminadora.

**Decisión:**  
Columna `institucion_id` en todas las tablas de datos del colegio. Un tenant = una fila en `instituciones` + `suscripciones`.

**Consecuencias:**
- ✅ Una sola base de datos — mantenimiento centralizado
- ✅ Backup y migraciones unificadas
- ✅ Reportes cross-tenant posibles para el superadmin
- ⚠️ Toda query debe filtrar por `institucion_id` — error silencioso si se olvida
- ⚠️ Crecimiento de tablas con muchos tenants — monitorear con índices adecuados
- ⚠️ Tenant isolation debe ser verificado en cada controller — riesgo de data leak entre colegios

---

## ADR-004: Front Controller único via `.htaccess`

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Routing de la aplicación. Todas las peticiones deben pasar por un punto central de control.

**Decisión:**  
`public/.htaccess` redirige todo tráfico a `public/index.php`. El Router interpreta la URL y hace dispatch al controller correspondiente.

**Consecuencias:**
- ✅ Control centralizado de autenticación, middlewares y logging
- ✅ URLs limpias (`/admin/estudiantes` en lugar de `/admin/estudiantes.php`)
- ⚠️ Requiere `mod_rewrite` habilitado en Apache — estándar en cPanel
- ⚠️ `Router::ejecutar()` hace `die()` en 404 en lugar de mostrar página de error — ver MEJORA-7

---

## ADR-005: Bootstrap 5 sin framework JS

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Stack de frontend para el sistema de gestión.

**Decisión:**  
Bootstrap 5 para UI + JavaScript vanilla para interactividad. Sin React, Vue, ni Alpine.js.

**Consecuencias:**
- ✅ Sin build process — PHP puro + HTML
- ✅ Compatible con cPanel sin Node.js
- ✅ Mantenimiento directo — sin transpilación
- ⚠️ CDN inconsistente detectado: algunas vistas usan 5.3.0 (jsdelivr), otras 5.3.2 (cdnjs) — ver MEJ-B9-05

---

## ADR-006: SMTP propio sin PHPMailer

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Envío de emails transaccionales (bienvenida, cobros, vencimientos). La dependencia de PHPMailer requiere Composer.

**Decisión:**  
`SmtpClient.php` propio — cliente SMTP manual via sockets, compatible con Gmail, Outlook y Brevo.

**Consecuencias:**
- ✅ Cero dependencias externas para email
- ✅ Configurable desde el panel de superadmin (ConfigModel)
- ⚠️ `verify_peer=false` en SSL — debe cambiarse a `true` en producción (MEJORA-8)
- ⚠️ Sin retry automático ni cola de emails — emails transaccionales pueden perderse
- ⚠️ Logs en `storage/logs/mail.log` — monitorear tamaño

---

## ADR-007: Singleton para conexión de base de datos

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Gestión de la conexión PDO a través del ciclo de vida de un request.

**Decisión:**  
`Database::getInstance()` retorna siempre la misma instancia PDO por request. Un único `connect()` por ciclo de vida.

**Consecuencias:**
- ✅ Sin múltiples conexiones abiertas por request
- ✅ Transacciones compartidas entre modelos si se necesita
- ⚠️ Patrón es correcto pero su uso desde vistas es un anti-patrón — ver BUG-V-05, BUG-V-14, BUG-B8b-11

---

## ADR-008: Roles y permisos basados en `rol_id`

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
Control de acceso para SuperAdmin, Admin de colegio, Profesor, Alumno, Padre.

**Decisión:**  
Columna `rol_id` en tabla `usuarios`. Constantes `ROL_SUPER_ADMIN`, `ROL_ADMIN_COLEGIO`, etc. en `constants.php`. `BaseController::requireRole()` verifica antes de cada acción.

**Consecuencias:**
- ✅ Verificación centralizada en BaseController
- ⚠️ `rol_id = 1` hardcodeado en `EmailService::notificarNuevoPreregistro()` — debe usar `ROL_SUPER_ADMIN` (BUG-M-07)
- ⚠️ Portales alumno/maestro/padre aún no implementados (Fases 5+)

---

## ADR-009: Target de despliegue: cPanel/Linux

**Fecha:** Sesión 001 (2026-03-08)  
**Estado:** ✅ Vigente

**Contexto:**  
La mayoría de colegios dominicanos usa hosting compartido cPanel. El desarrollo local es en XAMPP (Windows).

**Decisión:**  
Desarrollar en XAMPP pero asegurar compatibilidad total con Linux. Nomenclatura de archivos case-sensitive, paths con `/`, sin rutas absolutas de Windows.

**Consecuencias:**
- ✅ Deploy directo por FTP a cPanel
- ⚠️ BUG-CRÍTICO-1: `AnoescolarMode.php` viola esta decisión — fallará en Linux
- ⚠️ Patrón `require __DIR__/../../config/app.php` en vistas es frágil con paths relativos (ADR-016)

---

## ADR-010: Tabla `tutores` vs tabla `padres`

**Fecha:** 2026-03-08  
**Estado:** ⏳ Pendiente decisión

**Contexto:**  
El sistema tiene una tabla `tutores` asociada a `estudiantes`. Cuando se implemente el Portal de Padres (Fase 5), los padres necesitarán login. La pregunta es si usar la misma tabla `tutores` como cuenta de usuario o crear una tabla `padres` separada.

**Decisión:** PENDIENTE — definir antes del Sprint del Portal Padres

**Opciones en consideración:**
- **Opción A:** `tutores` como tabla de datos + `usuarios` con `rol_id = ROL_PADRE` — pivot `tutor_usuario`
- **Opción B:** Tabla `padres` separada con credenciales propias
- **Opción C:** Extender tabla `tutores` con campos de acceso

**Consecuencias del retraso:** No afecta los Sprints 2.x y 3.x. Crítico antes de Fase 5.

---

## ADR-011: Convención de rutas POST

**Fecha:** 2026-03-09  
**Estado:** ✅ Vigente

**Contexto:**  
En `routes/web.php` coexisten dos convenciones para rutas de creación de recursos:
- Patrón A: `POST /recurso/crear`
- Patrón B: `POST /recurso/guardar`

**Decisión:**  
Adoptar **Patrón A** (`POST /recurso/crear`) como estándar único. Unificar antes del Sprint 2.2.

**Consecuencias:**
- ✅ Consistencia en todas las rutas del sistema
- ⚠️ Requiere ajustar rutas POST del Sprint 2.1 que usan Patrón B — ver MEJORA-9

---

## ADR-012: Columna `fecha_baja` en suscripciones

**Fecha:** 2026-03-09  
**Estado:** ⏳ Pendiente migración

**Contexto:**  
`MetricasService::clientesPerdidosMes()` usa `updated_at` como proxy para detectar cuándo se canceló una suscripción. Esto distorsiona el Churn Rate cuando se actualiza la suscripción por cualquier otro motivo.

**Decisión:**  
Agregar columna `fecha_baja DATETIME NULL DEFAULT NULL` a la tabla `suscripciones`. Setearla explícitamente cuando se cancela o suspende una suscripción.

**Consecuencias:**
- ✅ Churn Rate preciso y auditable
- ⚠️ Requiere migración de BD — `ALTER TABLE suscripciones ADD COLUMN fecha_baja DATETIME NULL DEFAULT NULL`
- ⚠️ Requiere actualizar `MetricasService` y cualquier lógica de cancelación en `SuperAdminController`
- ⚠️ Datos históricos no tendrán `fecha_baja` — Churn Rate impreciso para períodos pasados

---

## ADR-013: Flag `debe_cambiar_password`

**Fecha:** 2026-03-09  
**Estado:** ⏳ Pendiente migración

**Contexto:**  
Cuando se crea una institución nueva, el admin del colegio recibe una contraseña generada. Sin un mecanismo de forzado de cambio, muchos admins nunca cambian la contraseña inicial.

**Decisión:**  
Agregar columna `debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0` en tabla `usuarios`. Setear a `1` al crear usuarios de colegios nuevos. `BaseController::requireRole()` verificará este flag y redirigirá a pantalla de cambio de contraseña si está activo.

**Consecuencias:**
- ✅ Seguridad mínima de onboarding
- ⚠️ Requiere migración: `ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0`
- ⚠️ Requiere vista nueva de cambio forzado de contraseña
- ⚠️ Relacionado con ADR-014

---

## ADR-014: Contraseñas iniciales generadas dinámicamente

**Fecha:** 2026-03-09  
**Estado:** ⏳ Pendiente implementación

**Contexto:**  
Tres puntos del sistema usan `Colegio2024!` hardcodeada como contraseña inicial:
1. `views/superadmin/instituciones/crear.php`
2. `views/superadmin/preregistros/ver.php`
3. `SuperAdminController::crearInstitucion()`

Todos los colegios arrancan con la misma contraseña predecible — riesgo de seguridad real.

**Decisión:**  
El controller genera `$password_sugerida = 'Colegio' . rand(1000, 9999) . '!'` y la pasa a la vista. La vista la muestra **una sola vez** (no la almacena en ningún campo oculto). Se guarda hasheada normalmente. Se activa `debe_cambiar_password = 1` (ADR-013).

**Consecuencias:**
- ✅ Cada colegio tiene contraseña inicial única
- ✅ Admin ve la contraseña en pantalla para comunicársela al colegio
- ⚠️ Corrección coordinada en controller + 2 vistas
- ⚠️ Depende de ADR-013 para el mecanismo de cambio forzado

---

## ADR-015: Nunca renderizar credenciales en atributos HTML

**Fecha:** 2026-03-09  
**Estado:** ✅ Vigente — Estándar de todo el sistema

**Contexto:**  
`views/superadmin/notificaciones/index.php` renderizaba la App Password SMTP en `value=""` del input de contraseña. Aunque el campo sea `type="password"` (no visible en pantalla), cualquier persona con acceso a DevTools puede ver el valor en el DOM.

**Decisión:**  
**Regla absoluta:** Ningún campo de contraseña, token, secret o credencial lleva atributo `value` en el HTML renderizado. Solo se usa `placeholder` con texto indicativo: `(guardada — dejar vacío para no cambiar)`.

**Consecuencias:**
- ✅ Credenciales nunca expuestas en DOM
- ✅ Comportamiento UX estándar: campo vacío = no cambiar, campo con valor = nueva contraseña
- ⚠️ Requiere que el controller/service detecte si el campo viene vacío para preservar el valor existente
- ⚠️ Aplicar a **todo** el sistema: buscar con `grep -rn "value.*password\|value.*pass\|value.*secret\|value.*token" views/`

---

## ADR-016: Resolución sistémica del patrón `require app.php` en vistas

**Fecha:** 2026-03-09  
**Estado:** ⏳ Pendiente implementación (Oleada 1)

**Contexto:**  
El patrón `$url = (require __DIR__ . '/../../config/app.php')['url']` aparece en al menos 5 archivos:
1. `views/layouts/main.php`
2. `views/auth/login.php`
3. `views/emails/bienvenida.php`
4. `public/forms/preinscripcion/formulario.php`
5. `public/forms/preregistro/formulario.php`

Es frágil porque: (a) el path relativo puede cambiar según el contexto de inclusión, (b) ejecuta `app.php` completo en cada include en lugar de solo leer un valor, (c) no funciona si la vista es incluida desde un directorio diferente.

**Decisión:**  
Agregar en `config/constants.php`:

```php
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('APP_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/edusaas-rd/public');
}
```

`constants.php` ya es cargado en `public/index.php` antes de cualquier vista, por lo que `APP_URL` estará disponible globalmente. Todas las vistas usan `APP_URL` directamente.

Para `EmailService`, pasar `$appUrl = APP_URL` como variable del layout de email.

**Consecuencias:**
- ✅ Elimina la categoría completa de bugs de path relativo
- ✅ Corrección en un solo archivo (`constants.php`) resuelve 5+ bugs simultáneamente
- ✅ Ninguna vista necesita `require` para obtener la URL base
- ⚠️ La constante asume que la app siempre corre desde `public/` — válido para este proyecto
- ⚠️ En entornos con subfolder diferente, el path debe ajustarse (documentar en `DEPLOY.md`)

---

## 📝 Plantilla para nuevos ADRs

```markdown
## ADR-XXX: [Título descriptivo]

**Fecha:** YYYY-MM-DD  
**Estado:** ⏳ Pendiente | ✅ Vigente | 🔄 Reemplazado por ADR-YYY

**Contexto:**  
[Por qué fue necesario tomar esta decisión. Qué problema resuelve.]

**Decisión:**  
[Qué se eligió hacer exactamente.]

**Consecuencias:**
- ✅ [Beneficio]
- ⚠️ [Trade-off o riesgo aceptado]
```

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `PLAN_CORRECCIONES.md` · `DEUDA_TECNICA.md` · `ARQUITECTURA.md`*
