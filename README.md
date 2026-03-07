# 🎓 EduSaaS RD - Sistema de Gestión Educativa
## Adaptado al Sistema Educativo Dominicano

### 📋 Descripción
SaaS multi-tenant para gestión integral de centros educativos públicos y privados en República Dominicana, cumpliendo con las normativas del MINERD.

### 🎯 Módulos Implementados

1. **Gestión de Alumnos y Matrículas**
   - Registro completo de estudiantes
   - Proceso de matrícula por año escolar
   - Historial académico
   - Documentación y expedientes

2. **Sistema de Calificaciones MINERD**
   - Escala 0-100
   - 4 períodos académicos + evaluación final
   - Registro por asignatura
   - Cálculo automático de promedios
   - Boletas de calificaciones

3. **Control de Asistencia**
   - Registro diario por estudiante
   - Justificaciones de ausencias
   - Reportes de asistencia mensual
   - Alertas de ausentismo

4. **Gestión de Profesores y Horarios**
   - Registro de docentes
   - Asignación de asignaturas
   - Creación de horarios de clases
   - Carga académica

5. **Pagos y Cuotas**
   - Gestión de mensualidades (colegios privados)
   - Conceptos de pago (matrícula, uniformes, etc.)
   - Estados de cuenta
   - Recibos y facturación
   - Reportes de morosidad

6. **Comunicados a Padres**
   - Envío de notificaciones
   - Circulares y avisos
   - Mensajes individuales
   - Historial de comunicaciones

7. **Reportes y Estadísticas**
   - Dashboard administrativo
   - Reportes de rendimiento académico
   - Estadísticas de asistencia
   - Reportes financieros
   - Exportación a PDF/Excel

### 🏗️ Arquitectura Técnica

**Stack:**
- PHP 8.1+ (Puro, sin framework)
- MySQL 8.0+
- Apache/Nginx
- HTML5, CSS3, JavaScript (Vanilla)

**Patrón de Diseño:**
- MVC (Model-View-Controller)
- Repository Pattern
- Dependency Injection básica
- RESTful API

**Multi-Tenancy:**
- Base de datos compartida con tenant_id
- Separación lógica por institución
- Subdominio opcional: `{colegio}.edusaas.com`

### 📁 Estructura del Proyecto

```
edusaas-rd/
├── config/
│   ├── database.php
│   ├── app.php
│   └── constants.php
├── app/
│   ├── Models/
│   ├── Controllers/
│   ├── Repositories/
│   ├── Services/
│   └── Middlewares/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── .htaccess
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── dashboard/
│   ├── students/
│   ├── teachers/
│   ├── grades/
│   ├── attendance/
│   ├── payments/
│   └── reports/
├── routes/
│   └── web.php
├── vendor/ (autoloader)
└── README.md
```

### 🇩🇴 Adaptaciones al Sistema Educativo Dominicano

**Niveles Educativos:**
- Nivel Inicial (3-5 años)
- Nivel Primario (1ro-6to grado)
- Nivel Secundario (1ro-6to de secundaria)

**Períodos Académicos:**
- 1er Período
- 2do Período
- 3er Período
- 4to Período
- Evaluación Final

**Sistema de Calificación:**
- Escala: 0-100 puntos
- Aprobado: ≥ 70 puntos
- Reprobado: < 70 puntos

**Año Escolar:**
- Inicio: Agosto/Septiembre
- Fin: Junio/Julio

### 👥 Roles y Permisos

1. **Super Admin**: Gestión de instituciones y configuración global
2. **Administrador**: Gestión completa de su institución
3. **Profesor**: Calificaciones, asistencia, comunicados
4. **Padre/Tutor**: Consulta de calificaciones, pagos, comunicados
5. **Estudiante**: Consulta de calificaciones y horarios

### 🔐 Seguridad

- Autenticación con sesiones PHP
- Contraseñas hasheadas (password_hash)
- Validación de inputs (XSS, SQL Injection)
- CSRF tokens
- Control de acceso basado en roles (RBAC)
- Logs de auditoría

### 📊 Base de Datos

La base de datos incluye:
- 25+ tablas relacionales
- Índices optimizados
- Constraints de integridad referencial
- Soft deletes
- Timestamps automáticos

### 🚀 Instalación

1. Requisitos previos
2. Configuración de base de datos
3. Importar estructura SQL
4. Configurar archivo `.env`
5. Configurar virtual host
6. Datos de prueba

### 📝 Licencia

Proyecto educativo - Uso libre

---

**Versión:** 1.0.0  
**Fecha:** Marzo 2026  
**Desarrollado para:** República Dominicana 🇩🇴
