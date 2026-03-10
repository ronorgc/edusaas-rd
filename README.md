# 🎓 EduSaaS RD

Sistema SaaS multi-tenant para gestión académica y administrativa de instituciones educativas en República Dominicana.

---

## 📌 ¿Qué es EduSaaS RD?

**EduSaaS RD** actúa como operador (SuperAdmin) que vende planes de suscripción a colegios. Cada colegio accede a su propio panel aislado para gestionar estudiantes, matrículas, calificaciones, asistencia, pagos y comunicados.

```
EduSaaS RD (SuperAdmin)
    └── Colegio A → Panel independiente
    └── Colegio B → Panel independiente
    └── Colegio N → Panel independiente
```

---

## 🔧 Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2 — MVC propio (sin framework) |
| Base de datos | MariaDB 10.4 — 33 tablas |
| Frontend | Bootstrap 5.3.2 + JS vanilla |
| Servidor local | XAMPP (Apache + mod_rewrite) |
| Servidor producción | cPanel / VPS Linux |
| Email | SmtpClient.php propio (Gmail, Outlook, Brevo) |
| Autenticación | Sessions PHP + bcrypt |

---

## 🚀 Instalación local (XAMPP)

### Requisitos
- XAMPP con PHP 8.2 y MariaDB 10.4
- `mod_rewrite` habilitado en Apache
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`

### Pasos

**1. Clonar el repositorio**
```bash
git clone https://github.com/ronorgc/edusaas-rd.git
# Colocar en: C:\xampp\htdocs\edusaas-rd\
```

**2. Crear el archivo de entorno**
```bash
copy .env.example .env
```

Editar `.env` con los valores locales:
```ini
DB_HOST=127.0.0.1
DB_DATABASE=edusaas_rd
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost/edusaas-rd/public
APP_DEBUG=true
APP_ENV=local

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu@gmail.com
MAIL_PASSWORD=tu_app_password
```

**3. Importar la base de datos**
```
phpMyAdmin → Crear BD: edusaas_rd → Importar: database/edusaas_rd.sql
```

**4. Verificar Apache**
```
Asegurarse que mod_rewrite está activo en httpd.conf:
LoadModule rewrite_module modules/mod_rewrite.so
```

**5. Acceder**
```
http://localhost/edusaas-rd/public
```

---

## 🗂️ Estructura del proyecto

```
edusaas-rd/
├── app/
│   ├── Controllers/     ← Lógica de request/response
│   ├── Models/          ← Active Record simplificado
│   ├── Helpers/         ← ActivityLog, PlanHelper
│   ├── Middlewares/     ← Suscripcion, Visor
│   └── Services/        ← Email, Metricas
├── config/              ← app.php, database.php, constants.php
├── docs/                ← Arquitectura, ADRs, schema, roadmap
├── public/              ← Front Controller (index.php)
├── routes/              ← web.php
├── vendor/              ← Autoload, Database, Router, SmtpClient
├── views/
│   ├── layouts/         ← Template principal
│   ├── superadmin/      ← Panel EduSaaS RD
│   └── colegio/         ← Panel del colegio
├── BITACORA.md          ← Estado del proyecto
├── CORRECCIONES_LOG.md  ← Tracker de fixes activos
└── .env.example         ← Plantilla de variables de entorno
```

---

## 📊 Estado del proyecto

| Módulo | Estado |
|--------|--------|
| Autenticación | ✅ Completo |
| Panel SuperAdmin | ✅ ~95% |
| Config académica (colegio) | ✅ Completo |
| Estudiantes | ✅ Completo |
| Pre-inscripciones | ✅ Completo |
| Profesores | ⏳ Pendiente |
| Matrículas | ⏳ Pendiente |
| Calificaciones | ⏳ Pendiente |
| Asistencia | ⏳ Pendiente |
| Portales externos | ⏳ Futuro |

Ver roadmap completo en `docs/ROADMAP.md`.

---

## 🔐 Seguridad

- **Nunca** subir `.env` al repositorio (está en `.gitignore`)
- Usar **App Passwords** de Gmail para SMTP (no la contraseña principal)
- Ver políticas completas en `docs/SEGURIDAD.md`

---

## 📁 Documentación técnica

| Archivo | Contenido |
|---------|-----------|
| `docs/ARQUITECTURA.md` | Estructura, patrones, flujos |
| `docs/SCHEMA_BD.md` | 33 tablas documentadas |
| `docs/ROADMAP.md` | Fases de desarrollo |
| `docs/PLAN_CORRECCIONES.md` | Oleadas de fixes priorizadas |
| `docs/SEGURIDAD.md` | Políticas y auditoría |
| `docs/DEPLOY.md` | Guía de despliegue local y producción |
| `docs/ADR.md` | Decisiones técnicas documentadas |
| `docs/DEUDA_TECNICA.md` | Tracker de deuda técnica |
| `BITACORA.md` | Log de sesiones y estado general |
| `CORRECCIONES_LOG.md` | Tracker de correcciones activas |

---

## 🌐 Entornos

| Ambiente | URL |
|----------|-----|
| Local | `http://localhost/edusaas-rd/public` |
| Producción | `https://app.edusaas.do` |

---

*EduSaaS RD — República Dominicana · PHP 8.2 · MariaDB 10.4*