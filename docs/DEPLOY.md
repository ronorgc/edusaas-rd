# 🚀 DEPLOY — EduSaaS RD

**Versión:** 1.0  
**Fecha:** 2026-03-09  
**Stack:** PHP 8.2 + MariaDB 10.4 + Apache (mod_rewrite)  
**Entorno local:** XAMPP (Windows)  
**Entorno producción:** cPanel / VPS Linux

---

## 🧭 Ambientes definidos

| Ambiente | URL | Base de datos | Debug | Propósito |
|----------|-----|---------------|-------|-----------|
| Local | `http://localhost/edusaas-rd/public` | `edusaas_rd` local | `true` | Desarrollo activo |
| Staging | `https://staging.edusaas.do` | `edusaas_rd_staging` | `false` | Pruebas pre-deploy |
| Producción | `https://app.edusaas.do` | `edusaas_rd_prod` | `false` | Usuarios reales |

> Staging es opcional pero altamente recomendado antes del primer deploy a producción.

---

## 📁 Archivos que NUNCA van al servidor de producción

```
.env                    ← Credenciales — SIEMPRE en .gitignore
.git/                   ← Control de versiones
docs/                   ← Documentación interna
storage/logs/*.log      ← Logs locales
public/uploads/         ← Los uploads van a backup aparte, no deploy
database/*.sql          ← Schema y dumps — no en webroot
*.md                    ← README y docs
```

**Verificar antes de subir:**
```bash
# Confirmar que .env está ignorado
cat .gitignore | grep .env

# Listar archivos que irían al servidor (excluir los de arriba)
git ls-files
```

---

## 🛠️ PARTE 1 — Configuración local (XAMPP)

### Requisitos
- XAMPP con PHP 8.2 y MariaDB 10.4
- `mod_rewrite` habilitado en Apache
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`

### Pasos de instalación local

**1. Clonar o copiar el proyecto**
```
C:\xampp\htdocs\edusaas-rd\
```

**2. Crear el `.env` a partir del ejemplo**
```bash
copy .env.example .env
```

Editar `.env` con los valores locales:
```ini
# Base de datos
DB_HOST=127.0.0.1
DB_DATABASE=edusaas_rd
DB_USERNAME=root
DB_PASSWORD=

# Aplicación
APP_URL=http://localhost/edusaas-rd/public
APP_DEBUG=true
APP_ENV=local

# SMTP (usar Mailtrap o Gmail para desarrollo)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_FROM=noreply@edusaas.do
MAIL_FROM_NAME=EduSaaS RD

# Seguridad
CRON_SECRET=cadena_aleatoria_larga_aqui
```

**3. Crear la base de datos**
```sql
CREATE DATABASE edusaas_rd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**4. Importar el schema**
```
phpMyAdmin → edusaas_rd → Importar → database/edusaas_rd.sql
```

O desde línea de comandos:
```bash
mysql -u root -p edusaas_rd < database/edusaas_rd.sql
```

**5. Aplicar migraciones pendientes** (en orden)
```sql
-- BUG-BD-01: InnoDB en calificaciones
ALTER TABLE calificaciones ENGINE=InnoDB;

-- BUG-4: UNIQUE en anos_escolares
ALTER TABLE anos_escolares
  ADD UNIQUE KEY uq_colegio_nombre (institucion_id, nombre);

-- ADR-012: fecha_baja en suscripciones
ALTER TABLE suscripciones
  ADD COLUMN fecha_baja DATETIME NULL DEFAULT NULL
  COMMENT 'Fecha de cancelación — para Churn Rate preciso';

-- ADR-013: debe_cambiar_password en usuarios
ALTER TABLE usuarios
  ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'Forzar cambio de contraseña en próximo login';
```

**6. Verificar `.htaccess`**

En XAMPP, `mod_rewrite` puede estar desactivado. Verificar en `C:\xampp\apache\conf\httpd.conf`:
```apache
# Debe estar sin comentar:
LoadModule rewrite_module modules/mod_rewrite.so

# Y en el bloque <Directory "C:/xampp/htdocs">:
AllowOverride All
```

**7. Verificar acceso**

Abrir: `http://localhost/edusaas-rd/public`  
Login: `superadmin` / `password`

> ⚠️ La contraseña seed `password` es el hash de Laravel por defecto (`$2y$10$92IXU...`). Cambiarla inmediatamente desde el panel de usuarios.

---

## 🌐 PARTE 2 — Deploy a cPanel/Linux

### Requisitos del hosting
- PHP 8.2 (verificar en cPanel → Software → Select PHP Version)
- MariaDB 10.4+ o MySQL 8.0+
- Apache con `mod_rewrite` habilitado
- Extensiones PHP activas: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`
- Acceso SSH (recomendado) o FTP
- Espacio mínimo: 500 MB (más uploads de estudiantes)

### Estructura de directorios en cPanel

La estructura del proyecto debe adaptarse al layout de cPanel:

```
/home/usuario/
├── public_html/              ← Document root del dominio principal
│   ├── .htaccess             ← Redirige todo a index.php
│   ├── index.php             ← Front Controller
│   └── assets/               ← CSS, JS, imágenes públicas
│       └── img/
│
└── edusaas-rd/               ← FUERA de public_html (protegido)
    ├── app/
    ├── config/
    ├── routes/
    ├── vendor/
    ├── views/
    ├── storage/
    └── .env                  ← NUNCA dentro de public_html
```

> **Crítico:** `app/`, `config/`, `views/`, `.env` deben estar **fuera** de `public_html`. Solo el contenido de `public/` va dentro de `public_html`.

### Pasos de deploy a cPanel

**1. Preparar el package local**

Crear zip con los archivos a subir (excluir lo que no va):
```bash
# En Windows (PowerShell):
Compress-Archive -Path app,config,routes,vendor,views,storage,public -DestinationPath edusaas-deploy.zip -ExcludeFiles "*.log","*.sql"
```

**2. Subir archivos**

**Opción A — via File Manager de cPanel:**
- Subir `edusaas-deploy.zip` a `/home/usuario/`
- Extraer fuera de `public_html`
- Mover solo el contenido de `public/` a `public_html/`

**Opción B — via SSH (recomendado):**
```bash
# Desde local
scp edusaas-deploy.zip usuario@servidor.com:~/

# En el servidor
ssh usuario@servidor.com
cd ~
unzip edusaas-deploy.zip -d edusaas-rd/
cp -r edusaas-rd/public/* public_html/
```

**3. Crear la base de datos en cPanel**

- cPanel → Bases de datos MySQL → Crear base de datos: `usuario_edusaas`
- Crear usuario de BD con contraseña fuerte
- Asignar todos los privilegios al usuario sobre esa BD
- Importar: phpMyAdmin → `usuario_edusaas` → Importar → `edusaas_rd.sql`
- Aplicar las migraciones pendientes (mismas queries de la sección local)

**4. Crear `.env` en el servidor**

Via SSH:
```bash
nano ~/edusaas-rd/.env
```

O via File Manager de cPanel (crear archivo `.env` fuera de `public_html`):

```ini
# Base de datos
DB_HOST=localhost
DB_DATABASE=usuario_edusaas
DB_USERNAME=usuario_dbuser
DB_PASSWORD=contraseña_fuerte_aqui

# Aplicación
APP_URL=https://app.edusaas.do
APP_DEBUG=false
APP_ENV=production

# SMTP
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@edusaas.do
MAIL_PASSWORD=app_password_nueva
MAIL_FROM=noreply@edusaas.do
MAIL_FROM_NAME=EduSaaS RD

# Seguridad
CRON_SECRET=generar_con_openssl_rand_hex_32
```

**5. Ajustar paths en `config/app.php`**

Verificar que `APP_URL` y la ruta de uploads sean correctas para el servidor:
```php
// config/app.php
'url' => getenv('APP_URL') ?: 'https://app.edusaas.do',
'uploads_path' => '/home/usuario/public_html/uploads/',
'uploads_url'  => 'https://app.edusaas.do/uploads/',
```

**6. Ajustar `.htaccess` en `public_html`**

El `.htaccess` de `public/` asume que la app corre en la raíz. Si el `index.php` está en `public_html/` directamente:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

Si hay subfolder (ej: `public_html/app/`), ajustar `RewriteBase /app/`.

**7. Verificar permisos de carpetas**

```bash
# Carpetas que necesitan escritura
chmod 755 ~/edusaas-rd/storage/
chmod 755 ~/edusaas-rd/storage/logs/
chmod 755 ~/public_html/uploads/
chmod 755 ~/public_html/uploads/estudiantes/
chmod 755 ~/public_html/uploads/preinscripciones/

# Archivos de configuración — solo lectura
chmod 640 ~/edusaas-rd/.env
chmod 640 ~/edusaas-rd/config/database.php
```

**8. Verificar SSL**

- cPanel → SSL/TLS → Instalar certificado (Let's Encrypt via AutoSSL es gratuito)
- Verificar que `.htaccess` fuerza HTTPS:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**9. Configurar cron jobs**

cPanel → Cron Jobs → Agregar:

```bash
# Cada hora — avisos de vencimiento y chequeos
0 * * * * curl -s "https://app.edusaas.do/cron/run?secret=TU_CRON_SECRET" > /dev/null 2>&1
```

O via PHP CLI si está disponible:
```bash
0 * * * * /usr/local/bin/php /home/usuario/edusaas-rd/scripts/cron.php >> /home/usuario/edusaas-rd/storage/logs/cron.log 2>&1
```

**10. Verificar el deploy**

```
✓ https://app.edusaas.do → página de login
✓ Login con superadmin
✓ Dashboard carga correctamente
✓ Configuración → verificar datos de empresa
✓ Notificaciones → probar envío SMTP
✓ Crear una institución de prueba
✓ Revisar storage/logs/ en busca de errores
```

---

## 🔄 PARTE 3 — Actualizaciones (deploys subsecuentes)

### Flujo recomendado para cada actualización

```
1. Commit en local con todos los cambios probados
2. Crear tag de versión: git tag v1.1.0
3. Backup de BD en producción (ANTES de cualquier cambio)
4. Subir archivos modificados (no toda la app)
5. Ejecutar migraciones de BD si las hay
6. Verificar funcionalidad crítica
7. Actualizar BITACORA.md con la versión desplegada
```

### Backup de BD antes de cada deploy

```bash
# Via SSH en cPanel
mysqldump -u usuario_dbuser -p usuario_edusaas > backup_$(date +%Y%m%d_%H%M).sql

# Descargar backup a local
scp usuario@servidor.com:~/backup_*.sql ./backups/
```

### Qué archivos actualizar en cada deploy

| Tipo de cambio | Archivos a subir |
|----------------|------------------|
| Solo vistas | `views/**/*.php` |
| Solo controllers | `app/Controllers/*.php` |
| Solo models | `app/Models/*.php` |
| Rutas nuevas | `routes/web.php` |
| Cambios de config | `config/*.php` (no `.env`) |
| Assets | `public/assets/` |
| Migración de BD | Ejecutar SQL manualmente + subir schema actualizado |

> **Nunca sobreescribir `.env` en producción** — tiene las credenciales reales. Actualizar manualmente si cambian variables.

---

## ⚙️ PARTE 4 — Variables de entorno por ambiente

### Diferencias críticas entre local y producción

| Variable | Local | Producción |
|----------|-------|------------|
| `APP_DEBUG` | `true` | `false` ← **CRÍTICO** |
| `APP_ENV` | `local` | `production` |
| `APP_URL` | `http://localhost/edusaas-rd/public` | `https://app.edusaas.do` |
| `DB_HOST` | `127.0.0.1` | `localhost` (cPanel usa socket) |
| `DB_DATABASE` | `edusaas_rd` | `usuario_edusaas` |
| `DB_USERNAME` | `root` | usuario específico de BD |
| `DB_PASSWORD` | (vacía en XAMPP) | contraseña fuerte |
| `MAIL_PASSWORD` | App Password de prueba | App Password de producción |
| SSL en SmtpClient | `verify_peer=false` | `verify_peer=true` ← **CRÍTICO** |

### Plantilla `.env.example`

Mantener este archivo en el repositorio con todas las variables documentadas pero sin valores reales:

```ini
# ─── BASE DE DATOS ───────────────────────────────────────
DB_HOST=127.0.0.1
DB_DATABASE=edusaas_rd
DB_USERNAME=root
DB_PASSWORD=

# ─── APLICACIÓN ──────────────────────────────────────────
APP_URL=http://localhost/edusaas-rd/public
APP_DEBUG=true
APP_ENV=local

# ─── SMTP ────────────────────────────────────────────────
# Proveedores soportados: Gmail, Outlook, Brevo
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=          # App Password (no la contraseña real de Gmail)
MAIL_FROM=noreply@edusaas.do
MAIL_FROM_NAME=EduSaaS RD

# ─── SEGURIDAD ───────────────────────────────────────────
# Generar con: openssl rand -hex 32
CRON_SECRET=
```

---

## 🩺 PARTE 5 — Diagnóstico de problemas comunes

### La app no carga — "404 Not Found"
```
Causa:   mod_rewrite no está activo o AllowOverride está en None
Verificar: phpinfo() → Apache → Loaded Modules → incluye mod_rewrite
Fix:     Activar mod_rewrite + AllowOverride All en httpd.conf (local)
         En cPanel: contactar al hosting para activar mod_rewrite
```

### "Fatal Error: Class not found"
```
Causa A: AnoescolarMode.php con nombre incorrecto (BUG-CRÍTICO-1)
Fix A:   Renombrar a AnoEscolarModel.php

Causa B: Path del Autoload incorrecto después de mover archivos
Fix B:   Verificar rutas en vendor/Autoload.php
```

### Página en blanco sin error
```
Causa:   APP_DEBUG=false oculta los errores
Fix:     Revisar storage/logs/ y el error_log de PHP/Apache
         Activar temporalmente APP_DEBUG=true (solo en diagnóstico)
```

### "Connection refused" o "Access denied" a BD
```
Causa A: Variables .env mal nombradas (DB_NAME vs DB_DATABASE — MEJORA-1)
Fix A:   Alinear nombres en .env y database.php

Causa B: En cPanel, DB_HOST debe ser 'localhost' no '127.0.0.1'
Fix B:   Cambiar DB_HOST=localhost en .env de producción

Causa C: Usuario de BD sin privilegios suficientes
Fix C:   cPanel → BD MySQL → verificar privilegios del usuario
```

### Emails no se envían
```
Causa A: App Password incorrecta o revocada
Fix A:   Regenerar App Password en myaccount.google.com

Causa B: Puerto 587 bloqueado por el hosting (común en shared hosting)
Fix B:   Probar puerto 465 con SSL, o usar Brevo/SendGrid como relay

Causa C: SSL verify_peer=true falla con certificado del servidor SMTP
Fix C:   Verificar que el servidor tiene certificados CA actualizados
         Como último recurso temporal: verify_peer=false (no recomendado en producción)

Revisar: storage/logs/mail.log para el detalle del error exacto
```

### Uploads de fotos no funcionan
```
Causa A: Ruta de uploads inconsistente (MEJORA-4)
Fix A:   Unificar $uploadPath entre config/app.php y EstudianteController

Causa B: Permisos de carpeta incorrectos
Fix B:   chmod 755 public_html/uploads/ y subdirectorios

Causa C: PHP fileinfo no disponible (validación MIME falla silenciosamente)
Fix C:   Activar extensión fileinfo en cPanel → Select PHP Version → Extensions
```

### Cron jobs no se ejecutan
```
Causa A: CRON_SECRET incorrecto en la URL del cron
Fix A:   Verificar que .env y la URL del cron tengan el mismo valor

Causa B: curl no disponible en el servidor
Fix B:   Cambiar a PHP CLI: /usr/local/bin/php scripts/cron.php

Revisar: storage/logs/cron.log para errores de ejecución
```

### Branding / colores no se aplican
```
Causa:   BUG-B9-03 — segundo bloque :root en layouts/main.php sobreescribe ConfigModel
Fix:     Fusionar bloques :root (Oleada 3 de PLAN_CORRECCIONES.md)
```

---

## 📋 Checklist de deploy a producción

### Pre-deploy (local)
- [ ] Todas las Oleadas 0–4 de `PLAN_CORRECCIONES.md` completadas
- [ ] `APP_DEBUG=false` en `.env` de producción
- [ ] `verify_peer=true` en `SmtpClient.php`
- [ ] `.env` en `.gitignore` y sin datos reales en el repo
- [ ] Contraseña de `superadmin` cambiada desde el seed
- [ ] Migraciones pendientes documentadas en `database/migrations/`
- [ ] `CRON_SECRET` generado con `openssl rand -hex 32`
- [ ] SSL activado en el dominio de producción

### Deploy
- [ ] Backup de BD antes de cualquier cambio
- [ ] Archivos subidos fuera de `public_html` (excepto `public/`)
- [ ] `.env` creado manualmente en el servidor (nunca via deploy automático)
- [ ] Migraciones ejecutadas en BD de producción
- [ ] Permisos de carpetas verificados (`755` en uploads y logs)
- [ ] Cron job configurado en cPanel

### Post-deploy
- [ ] Login con superadmin funciona
- [ ] Dashboard carga sin errores
- [ ] Email SMTP probado desde el panel (Notificaciones → Probar SMTP)
- [ ] Crear institución de prueba y verificar flujo completo
- [ ] Preinscripción pública accesible desde URL del colegio
- [ ] `storage/logs/` sin errores críticos
- [ ] Checklist de seguridad de `SEGURIDAD.md` completado

---

## 📌 URLs de referencia por ambiente

| Recurso | Local | Producción |
|---------|-------|------------|
| Panel SuperAdmin | `http://localhost/edusaas-rd/public/superadmin` | `https://app.edusaas.do/superadmin` |
| Panel Colegio | `http://localhost/edusaas-rd/public/admin` | `https://app.edusaas.do/admin` |
| Portal preinscripción | `http://localhost/edusaas-rd/public/forms/preinscripcion/{slug}` | `https://app.edusaas.do/forms/preinscripcion/{slug}` |
| Portal preregistro | `http://localhost/edusaas-rd/public/forms/preregistro` | `https://app.edusaas.do/forms/preregistro` |
| Cron endpoint | `http://localhost/edusaas-rd/public/cron/run?secret=...` | `https://app.edusaas.do/cron/run?secret=...` |
| Health check | `http://localhost/edusaas-rd/public/superadmin/salud` | `https://app.edusaas.do/superadmin/salud` |

---

*Generado automáticamente — Sesión 016 — 2026-03-09*  
*Ver también: `SEGURIDAD.md` checklist pre-deploy · `PLAN_CORRECCIONES.md` · `ARQUITECTURA.md`*
