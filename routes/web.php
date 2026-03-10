<?php
// =====================================================
// EduSaaS RD - Definición de Rutas
// =====================================================
// ✅ Activo     = controller existe y funciona
// 🔲 Futuro     = comentado, pendiente de desarrollo
//
// CONVENCIÓN DE PREFIJOS:
//   /auth/...           → autenticación
//   /admin/...          → panel del colegio (ROL_ADMIN o visor)
//   /superadmin/...     → panel global (ROL_SUPER_ADMIN)
//   /preinscripcion/... → formulario público del colegio (sin auth)
//   /registro/...       → preregistro público de colegios (sin auth)
//
// ⚠️ REGLA DE ORDEN: Las rutas ESTÁTICAS deben registrarse
//    SIEMPRE antes que las rutas DINÁMICAS ({id}) del mismo
//    prefijo. El Router resuelve por orden de registro y
//    una ruta dinámica captura cualquier segmento, incluyendo
//    palabras como "exportar", "crear", "editar", etc.
// =====================================================

// --------------------------------------------------
// PÚBLICAS — sin autenticación requerida
// --------------------------------------------------

// Registro público de colegios
// (solo accesible si sistema_registro_publico = 1 en Configuración)
$router->get('/registro',         'PreregistroController', 'formulario');
$router->post('/registro',        'PreregistroController', 'enviar');
$router->get('/registro/gracias', 'PreregistroController', 'gracias');

// Pre-inscripción de estudiantes por colegio (portal público del colegio)
$router->get('/preinscripcion/{slug}',               'PreinscripcionController', 'formulario');
$router->post('/preinscripcion/{slug}/enviar',       'PreinscripcionController', 'enviar');
$router->get('/preinscripcion/{slug}/gracias/{cod}', 'PreinscripcionController', 'gracias');

// --------------------------------------------------
// AUTENTICACIÓN
// --------------------------------------------------
$router->get('/auth/login',       'AuthController', 'loginForm');
$router->post('/auth/login',      'AuthController', 'login');
$router->get('/auth/logout',      'AuthController', 'logout');
$router->get('/auth/salir-visor', 'AuthController', 'salirVisor');

// Ruta raíz → redirige al login o dashboard según sesión
$router->get('/', 'AuthController', 'index');

// --------------------------------------------------
// ✅ PANEL DEL COLEGIO — Dashboard
// --------------------------------------------------
$router->get('/admin/dashboard', 'DashboardController', 'index');

// --------------------------------------------------
// ✅ PANEL DEL COLEGIO — Estudiantes
// --------------------------------------------------
$router->get('/admin/estudiantes',               'EstudianteController', 'index');
$router->get('/admin/estudiantes/crear',         'EstudianteController', 'crear');
$router->post('/admin/estudiantes/crear',        'EstudianteController', 'guardar');
$router->get('/admin/estudiantes/{id}',          'EstudianteController', 'ver');
$router->get('/admin/estudiantes/{id}/editar',   'EstudianteController', 'editar');
$router->post('/admin/estudiantes/{id}/editar',  'EstudianteController', 'actualizar');
$router->get('/admin/estudiantes/{id}/eliminar', 'EstudianteController', 'eliminar');

// --------------------------------------------------
// ✅ PANEL DEL COLEGIO — Pre-inscripciones (revisión admin)
// --------------------------------------------------
$router->get('/admin/preinscripciones',                  'PreinscripcionController', 'adminIndex');
$router->get('/admin/preinscripciones/{id}',             'PreinscripcionController', 'adminVer');
$router->post('/admin/preinscripciones/{id}/actualizar', 'PreinscripcionController', 'adminActualizar');
$router->post('/admin/preinscripciones/{id}/convertir',  'PreinscripcionController', 'adminConvertir');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Profesores (Sprint 2.2)
// --------------------------------------------------
// $router->get('/admin/profesores',               'ProfesorController', 'index');
// $router->get('/admin/profesores/crear',         'ProfesorController', 'crear');
// $router->post('/admin/profesores/crear',        'ProfesorController', 'guardar');
// $router->get('/admin/profesores/{id}',          'ProfesorController', 'ver');
// $router->get('/admin/profesores/{id}/editar',   'ProfesorController', 'editar');
// $router->post('/admin/profesores/{id}/editar',  'ProfesorController', 'actualizar');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Matrículas (Sprint 2.3)
// --------------------------------------------------
// $router->get('/admin/matriculas',        'MatriculaController', 'index');
// $router->get('/admin/matriculas/crear',  'MatriculaController', 'crear');
// $router->post('/admin/matriculas/crear', 'MatriculaController', 'guardar');
// $router->get('/admin/matriculas/{id}',   'MatriculaController', 'ver');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Calificaciones (Sprint 3.2)
// --------------------------------------------------
// $router->get('/admin/calificaciones',                       'CalificacionController', 'index');
// $router->get('/admin/calificaciones/seccion/{id}',          'CalificacionController', 'porSeccion');
// $router->post('/admin/calificaciones/guardar',              'CalificacionController', 'guardar');
// $router->get('/admin/calificaciones/boleta/{matricula_id}', 'CalificacionController', 'boleta');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Asistencia (Sprint 3.1)
// --------------------------------------------------
// $router->get('/admin/asistencia',              'AsistenciaController', 'index');
// $router->get('/admin/asistencia/seccion/{id}', 'AsistenciaController', 'porSeccion');
// $router->post('/admin/asistencia/registrar',   'AsistenciaController', 'registrar');
// $router->get('/admin/asistencia/reporte/{id}', 'AsistenciaController', 'reporte');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Pagos y cuotas (Fase 4)
// --------------------------------------------------
// $router->get('/admin/pagos',             'PagoController', 'index');
// $router->get('/admin/pagos/cuotas',      'PagoController', 'cuotas');
// $router->post('/admin/pagos/registrar',  'PagoController', 'registrar');
// $router->get('/admin/pagos/recibo/{id}', 'PagoController', 'recibo');
// $router->get('/admin/pagos/morosos',     'PagoController', 'morosos');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Comunicados (Sprint 3.3)
// --------------------------------------------------
// $router->get('/admin/comunicados',        'ComunicadoController', 'index');
// $router->get('/admin/comunicados/crear',  'ComunicadoController', 'crear');
// $router->post('/admin/comunicados/crear', 'ComunicadoController', 'guardar');
// $router->get('/admin/comunicados/{id}',   'ComunicadoController', 'ver');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Reportes (Fase 6)
// --------------------------------------------------
// $router->get('/admin/reportes',            'ReporteController', 'index');
// $router->get('/admin/reportes/academico',  'ReporteController', 'academico');
// $router->get('/admin/reportes/asistencia', 'ReporteController', 'asistencia');
// $router->get('/admin/reportes/financiero', 'ReporteController', 'financiero');

// --------------------------------------------------
// 🔲 PANEL DEL COLEGIO — Configuración académica (Sprint 2.1)
// --------------------------------------------------
// ⚠️ ORDEN CRÍTICO: rutas estáticas PRIMERO, dinámicas ({id}) DESPUÉS.
// $router->get('/admin/anos-escolares',                    'AdminController', 'anosEscolares');
// $router->get('/admin/anos-escolares/crear',              'AdminController', 'crearAnoEscolar');
// $router->post('/admin/anos-escolares/crear',             'AdminController', 'guardarAnoEscolar');
// $router->get('/admin/anos-escolares/{id}/editar',        'AdminController', 'editarAnoEscolar');
// $router->post('/admin/anos-escolares/{id}/editar',       'AdminController', 'actualizarAnoEscolar');
// $router->post('/admin/anos-escolares/{id}/activar',      'AdminController', 'activarAnoEscolar');
// $router->get('/admin/grados',                            'AdminController', 'grados');
// $router->get('/admin/secciones',                         'AdminController', 'secciones');
// $router->get('/admin/secciones/crear',                   'AdminController', 'crearSeccion');
// $router->post('/admin/secciones/crear',                  'AdminController', 'guardarSeccion');
// $router->get('/admin/secciones/{id}/editar',             'AdminController', 'editarSeccion');
// $router->post('/admin/secciones/{id}/editar',            'AdminController', 'actualizarSeccion');
// $router->post('/admin/secciones/{id}/eliminar',          'AdminController', 'eliminarSeccion');
// $router->get('/admin/periodos',                          'AdminController', 'periodos');
// $router->get('/admin/periodos/crear',                    'AdminController', 'crearPeriodo');
// $router->post('/admin/periodos/crear',                   'AdminController', 'guardarPeriodo');
// $router->get('/admin/periodos/{id}/editar',              'AdminController', 'editarPeriodo');
// $router->post('/admin/periodos/{id}/editar',             'AdminController', 'actualizarPeriodo');
// $router->post('/admin/periodos/{id}/eliminar',           'AdminController', 'eliminarPeriodo');
// $router->get('/admin/usuarios',                          'AdminController', 'usuarios');

// --------------------------------------------------
// ✅ SUPER ADMIN — Dashboard y métricas
// --------------------------------------------------
$router->get('/superadmin', 'SuperAdminController', 'dashboard');

// --------------------------------------------------
// ✅ SUPER ADMIN — Instituciones
// --------------------------------------------------
// ⚠️ ORDEN CRÍTICO: rutas estáticas PRIMERO, dinámicas ({id}) DESPUÉS.
$router->get('/superadmin/instituciones',        'SuperAdminController', 'instituciones');
$router->get('/superadmin/instituciones/crear',  'SuperAdminController', 'crearInstitucionForm');
$router->post('/superadmin/instituciones/crear', 'SuperAdminController', 'crearInstitucion');

// Exportar CSV (estática — debe ir ANTES de /{id}) ← Bug-1 corregido
$router->get('/superadmin/instituciones/exportar', 'SuperAdminController', 'exportarInstituciones');

// Detalle y edición por {id} (dinámicas — van DESPUÉS de las estáticas)
$router->get('/superadmin/instituciones/{id}',                        'SuperAdminController', 'verInstitucion');
$router->get('/superadmin/instituciones/{id}/editar',                 'SuperAdminController', 'editarInstitucionForm');
$router->post('/superadmin/instituciones/{id}/editar',                'SuperAdminController', 'editarInstitucion');
$router->post('/superadmin/instituciones/{id}/suspender',             'SuperAdminController', 'suspenderInstitucion');
$router->post('/superadmin/instituciones/{id}/reactivar',             'SuperAdminController', 'reactivarInstitucion');
$router->post('/superadmin/instituciones/{id}/eliminar',              'SuperAdminController', 'eliminarInstitucion');
$router->post('/superadmin/instituciones/{id}/notas',                 'SuperAdminController', 'guardarNotas');
$router->post('/superadmin/instituciones/{id}/cambiar-plan',          'SuperAdminController', 'cambiarPlan');
$router->post('/superadmin/instituciones/{id}/pago',                  'SuperAdminController', 'registrarPago');
$router->get('/superadmin/instituciones/{id}/revisar',                'SuperAdminController', 'revisarColegio');
$router->get('/superadmin/instituciones/{id}/usuarios',               'SuperAdminController', 'usuariosColegio');
$router->post('/superadmin/instituciones/{id}/usuarios/{uid}/reset',  'SuperAdminController', 'resetPassword');
$router->post('/superadmin/instituciones/{id}/usuarios/{uid}/toggle', 'SuperAdminController', 'toggleUsuario');

// --------------------------------------------------
// ✅ SUPER ADMIN — Planes
// --------------------------------------------------
$router->get('/superadmin/planes',              'SuperAdminController', 'planes');
$router->get('/superadmin/planes/crear',        'SuperAdminController', 'crearPlanForm');
$router->post('/superadmin/planes/crear',       'SuperAdminController', 'crearPlan');
$router->get('/superadmin/planes/{id}/editar',  'SuperAdminController', 'editarPlanForm');
$router->post('/superadmin/planes/{id}/editar', 'SuperAdminController', 'editarPlan');
$router->post('/superadmin/planes/{id}/toggle', 'SuperAdminController', 'togglePlan');

// --------------------------------------------------
// ✅ SUPER ADMIN — Cobros y facturación
// --------------------------------------------------
$router->get('/superadmin/cobros',                  'SuperAdminController', 'cobros');
$router->get('/superadmin/cobros/masivo',           'SuperAdminController', 'renovacionMasiva');
$router->post('/superadmin/cobros/masivo/procesar', 'SuperAdminController', 'procesarRenovacionMasiva');
$router->get('/superadmin/cobros/formulario',       'SuperAdminController', 'formularioCobro');
$router->post('/superadmin/cobros/procesar',        'SuperAdminController', 'procesarCobro');
$router->get('/superadmin/cobros/recibo/{id}',      'SuperAdminController', 'reciboCobro');

// --------------------------------------------------
// ✅ SUPER ADMIN — Ingresos y reportes
// --------------------------------------------------
// ⚠️ ORDEN CRÍTICO: /exportar va ANTES de /{id}.
$router->get('/superadmin/ingresos/exportar', 'SuperAdminController', 'exportarIngresos');
$router->get('/superadmin/ingresos',          'SuperAdminController', 'ingresos');
$router->get('/superadmin/pagos/{id}/recibo', 'SuperAdminController', 'recibo');

// --------------------------------------------------
// ✅ SUPER ADMIN — Solicitudes de preregistro de colegios
// --------------------------------------------------
$router->get('/superadmin/preregistros',                  'PreregistroController', 'lista');
$router->get('/superadmin/preregistros/{id}',             'PreregistroController', 'ver');
$router->post('/superadmin/preregistros/{id}/aprobar',    'PreregistroController', 'aprobar');
$router->post('/superadmin/preregistros/{id}/rechazar',   'PreregistroController', 'rechazar');
$router->post('/superadmin/preregistros/{id}/contactado', 'PreregistroController', 'marcarContactado');

// --------------------------------------------------
// ✅ SUPER ADMIN — Usuarios superadmin
// --------------------------------------------------
// ⚠️ ORDEN CRÍTICO: /crear va ANTES de /{id}.
$router->get('/superadmin/usuarios',                'SuperAdminController', 'usuariosSuperAdmin');
$router->get('/superadmin/usuarios/crear',          'SuperAdminController', 'crearUsuarioSAForm');
$router->post('/superadmin/usuarios/crear',         'SuperAdminController', 'crearUsuarioSA');
$router->get('/superadmin/usuarios/{id}/editar',    'SuperAdminController', 'editarUsuarioSAForm');
$router->post('/superadmin/usuarios/{id}/editar',   'SuperAdminController', 'editarUsuarioSA');
$router->post('/superadmin/usuarios/{id}/toggle',   'SuperAdminController', 'toggleUsuarioSA');
$router->post('/superadmin/usuarios/{id}/eliminar', 'SuperAdminController', 'eliminarUsuarioSA');

// --------------------------------------------------
// ✅ SUPER ADMIN — Notificaciones y SMTP
// --------------------------------------------------
$router->get('/superadmin/notificaciones',                      'SuperAdminController', 'notificaciones');
$router->post('/superadmin/notificaciones/enviar-vencimientos', 'SuperAdminController', 'enviarAvisosVencimiento');
$router->post('/superadmin/notificaciones/enviar-individual',   'SuperAdminController', 'enviarIndividual');
$router->post('/superadmin/notificaciones/smtp-guardar',        'SuperAdminController', 'smtpGuardar');
$router->post('/superadmin/notificaciones/smtp-test',           'SuperAdminController', 'smtpTest');

// --------------------------------------------------
// ✅ SUPER ADMIN — Salud del sistema
// --------------------------------------------------
$router->get('/superadmin/salud',  'SuperAdminController', 'saludSistema');
$router->post('/superadmin/salud', 'SuperAdminController', 'saludSistema');

// --------------------------------------------------
// ✅ SUPER ADMIN — Cron: recordatorio vencimiento
// --------------------------------------------------
$router->get('/superadmin/cron/avisos-vencimiento',  'SuperAdminController', 'cronAvisosVencimiento');
$router->post('/superadmin/cron/avisos-vencimiento', 'SuperAdminController', 'cronAvisosVencimiento');

// --------------------------------------------------
// ✅ SUPER ADMIN — Utilidades
// --------------------------------------------------
$router->post('/superadmin/desbloquear-usuario', 'SuperAdminController', 'desbloquearUsuario');
$router->get('/superadmin/emails',               'SuperAdminController', 'emailsLog');
$router->get('/superadmin/log',                  'SuperAdminController', 'logActividad');

// --------------------------------------------------
// ✅ SUPER ADMIN — Configuración del sistema
// --------------------------------------------------
$router->get('/superadmin/configuracion',  'SuperAdminController', 'configuracion');
$router->post('/superadmin/configuracion', 'SuperAdminController', 'guardarConfiguracion');

// =====================================================
// FRAGMENTO — routes/web.php
// Sprint 2.1 — Estructura Académica (AdminController)
// =====================================================
// INSTRUCCIÓN: Agregar este bloque en routes/web.php
// dentro de la sección "// ── PANEL ADMIN INSTITUCIÓN ──"
// DESPUÉS de las rutas del dashboard y estudiantes.
// =====================================================

// ── AÑOS ESCOLARES ────────────────────────────────────
$router->get('/admin/anos-escolares',                 'AdminController', 'anosEscolares');
$router->get('/admin/anos-escolares/crear',           'AdminController', 'crearAnoEscolar');
$router->post('/admin/anos-escolares/guardar',        'AdminController', 'guardarAnoEscolar');
$router->get('/admin/anos-escolares/{id}/editar',     'AdminController', 'editarAnoEscolar');
$router->post('/admin/anos-escolares/{id}/actualizar', 'AdminController', 'actualizarAnoEscolar');
$router->post('/admin/anos-escolares/{id}/activar',   'AdminController', 'activarAnoEscolar');
$router->post('/admin/anos-escolares/{id}/eliminar',  'AdminController', 'eliminarAnoEscolar');

// ── GRADOS (solo lectura — datos seed MINERD) ─────────
$router->get('/admin/grados',                         'AdminController', 'grados');

// ── SECCIONES ─────────────────────────────────────────
$router->get('/admin/secciones',                      'AdminController', 'secciones');
$router->get('/admin/secciones/crear',                'AdminController', 'crearSeccion');
$router->post('/admin/secciones/guardar',             'AdminController', 'guardarSeccion');
$router->get('/admin/secciones/{id}/editar',          'AdminController', 'editarSeccion');
$router->post('/admin/secciones/{id}/actualizar',     'AdminController', 'actualizarSeccion');
$router->post('/admin/secciones/{id}/eliminar',       'AdminController', 'eliminarSeccion');

// ── PERÍODOS DE EVALUACIÓN ────────────────────────────
$router->get('/admin/periodos',                       'AdminController', 'periodos');
$router->get('/admin/periodos/crear',                 'AdminController', 'crearPeriodo');
$router->post('/admin/periodos/guardar',              'AdminController', 'guardarPeriodo');
$router->get('/admin/periodos/{id}/editar',           'AdminController', 'editarPeriodo');
$router->post('/admin/periodos/{id}/actualizar',      'AdminController', 'actualizarPeriodo');
$router->post('/admin/periodos/{id}/eliminar',        'AdminController', 'eliminarPeriodo');
