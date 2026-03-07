<?php
// =====================================================
// EduSaaS RD - Definición de Rutas
// =====================================================
// ✅ Activo     = controller existe y funciona
// 🔲 Futuro     = comentado, pendiente de desarrollo
// =====================================================

// --------------------------------------------------
// PÚBLICAS — sin autenticación requerida
// --------------------------------------------------

// Registro público de colegios
// (solo accesible si sistema_registro_publico = 1 en Configuración)
$router->get('/registro',         'PreregistroController', 'formulario');
$router->post('/registro',        'PreregistroController', 'enviar');
$router->get('/registro/gracias', 'PreregistroController', 'gracias');

// Pre-inscripción de estudiantes por colegio
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
// ✅ DASHBOARD
// --------------------------------------------------
$router->get('/dashboard', 'DashboardController', 'index');

// --------------------------------------------------
// ✅ ESTUDIANTES
// --------------------------------------------------
$router->get('/estudiantes',               'EstudianteController', 'index');
$router->get('/estudiantes/crear',         'EstudianteController', 'crear');
$router->post('/estudiantes/crear',        'EstudianteController', 'guardar');
$router->get('/estudiantes/{id}',          'EstudianteController', 'ver');
$router->get('/estudiantes/{id}/editar',   'EstudianteController', 'editar');
$router->post('/estudiantes/{id}/editar',  'EstudianteController', 'actualizar');
$router->get('/estudiantes/{id}/eliminar', 'EstudianteController', 'eliminar');

// --------------------------------------------------
// ✅ PRE-INSCRIPCIONES — Panel admin del colegio
// --------------------------------------------------
$router->get('/admin/preinscripciones',                  'PreinscripcionController', 'adminIndex');
$router->get('/admin/preinscripciones/{id}',             'PreinscripcionController', 'adminVer');
$router->post('/admin/preinscripciones/{id}/actualizar', 'PreinscripcionController', 'adminActualizar');
$router->post('/admin/preinscripciones/{id}/convertir',  'PreinscripcionController', 'adminConvertir');

// --------------------------------------------------
// 🔲 PROFESORES — Futuro
// --------------------------------------------------
// $router->get('/profesores',              'ProfesorController', 'index');
// $router->get('/profesores/crear',        'ProfesorController', 'crear');
// $router->post('/profesores/crear',       'ProfesorController', 'guardar');
// $router->get('/profesores/{id}',         'ProfesorController', 'ver');
// $router->get('/profesores/{id}/editar',  'ProfesorController', 'editar');
// $router->post('/profesores/{id}/editar', 'ProfesorController', 'actualizar');

// --------------------------------------------------
// 🔲 MATRÍCULAS — Futuro
// --------------------------------------------------
// $router->get('/matriculas',        'MatriculaController', 'index');
// $router->get('/matriculas/crear',  'MatriculaController', 'crear');
// $router->post('/matriculas/crear', 'MatriculaController', 'guardar');
// $router->get('/matriculas/{id}',   'MatriculaController', 'ver');

// --------------------------------------------------
// 🔲 CALIFICACIONES — Futuro
// --------------------------------------------------
// $router->get('/calificaciones',                       'CalificacionController', 'index');
// $router->get('/calificaciones/seccion/{id}',          'CalificacionController', 'porSeccion');
// $router->post('/calificaciones/guardar',              'CalificacionController', 'guardar');
// $router->get('/calificaciones/boleta/{matricula_id}', 'CalificacionController', 'boleta');

// --------------------------------------------------
// 🔲 ASISTENCIA — Futuro
// --------------------------------------------------
// $router->get('/asistencia',              'AsistenciaController', 'index');
// $router->get('/asistencia/seccion/{id}', 'AsistenciaController', 'porSeccion');
// $router->post('/asistencia/registrar',   'AsistenciaController', 'registrar');
// $router->get('/asistencia/reporte/{id}', 'AsistenciaController', 'reporte');

// --------------------------------------------------
// 🔲 PAGOS Y CUOTAS del colegio — Futuro
// --------------------------------------------------
// $router->get('/pagos',             'PagoController', 'index');
// $router->get('/pagos/cuotas',      'PagoController', 'cuotas');
// $router->post('/pagos/registrar',  'PagoController', 'registrar');
// $router->get('/pagos/recibo/{id}', 'PagoController', 'recibo');
// $router->get('/pagos/morosos',     'PagoController', 'morosos');

// --------------------------------------------------
// 🔲 COMUNICADOS — Futuro
// --------------------------------------------------
// $router->get('/comunicados',        'ComunicadoController', 'index');
// $router->get('/comunicados/crear',  'ComunicadoController', 'crear');
// $router->post('/comunicados/crear', 'ComunicadoController', 'guardar');
// $router->get('/comunicados/{id}',   'ComunicadoController', 'ver');

// --------------------------------------------------
// 🔲 REPORTES — Futuro
// --------------------------------------------------
// $router->get('/reportes',            'ReporteController', 'index');
// $router->get('/reportes/academico',  'ReporteController', 'academico');
// $router->get('/reportes/asistencia', 'ReporteController', 'asistencia');
// $router->get('/reportes/financiero', 'ReporteController', 'financiero');

// --------------------------------------------------
// 🔲 ADMINISTRACIÓN DEL COLEGIO — Futuro
// --------------------------------------------------
// $router->get('/admin/usuarios',              'AdminController', 'usuarios');
// $router->get('/admin/anos-escolares',        'AdminController', 'anosEscolares');
// $router->get('/admin/anos-escolares/crear',  'AdminController', 'crearAnoEscolar');
// $router->post('/admin/anos-escolares/crear', 'AdminController', 'guardarAnoEscolar');
// $router->get('/admin/secciones',             'AdminController', 'secciones');
// $router->get('/admin/asignaturas',           'AdminController', 'asignaturas');
// $router->get('/admin/horarios',              'AdminController', 'horarios');

// --------------------------------------------------
// ✅ SUPER ADMIN — Dashboard y métricas
// --------------------------------------------------
$router->get('/superadmin', 'SuperAdminController', 'dashboard');

// Instituciones
$router->get('/superadmin/instituciones',                              'SuperAdminController', 'instituciones');
$router->get('/superadmin/instituciones/crear',                        'SuperAdminController', 'crearInstitucionForm');
$router->post('/superadmin/instituciones/crear',                       'SuperAdminController', 'crearInstitucion');
$router->get('/superadmin/instituciones/{id}',                         'SuperAdminController', 'verInstitucion');
$router->get('/superadmin/instituciones/{id}/editar',                  'SuperAdminController', 'editarInstitucionForm');
$router->post('/superadmin/instituciones/{id}/editar',                 'SuperAdminController', 'editarInstitucion');
$router->post('/superadmin/instituciones/{id}/suspender',              'SuperAdminController', 'suspenderInstitucion');
$router->post('/superadmin/instituciones/{id}/reactivar',              'SuperAdminController', 'reactivarInstitucion');
$router->post('/superadmin/instituciones/{id}/eliminar',               'SuperAdminController', 'eliminarInstitucion');
$router->post('/superadmin/instituciones/{id}/notas',                  'SuperAdminController', 'guardarNotas');
$router->post('/superadmin/instituciones/{id}/cambiar-plan',           'SuperAdminController', 'cambiarPlan');
$router->post('/superadmin/instituciones/{id}/pago',                   'SuperAdminController', 'registrarPago');
$router->get('/superadmin/instituciones/{id}/revisar',                 'SuperAdminController', 'revisarColegio');
$router->get('/superadmin/instituciones/{id}/usuarios',                'SuperAdminController', 'usuariosColegio');
$router->post('/superadmin/instituciones/{id}/usuarios/{uid}/reset',   'SuperAdminController', 'resetPassword');
$router->post('/superadmin/instituciones/{id}/usuarios/{uid}/toggle',  'SuperAdminController', 'toggleUsuario');

// Planes
$router->get('/superadmin/planes',              'SuperAdminController', 'planes');
$router->get('/superadmin/planes/crear',        'SuperAdminController', 'crearPlanForm');
$router->post('/superadmin/planes/crear',       'SuperAdminController', 'crearPlan');
$router->get('/superadmin/planes/{id}/editar',  'SuperAdminController', 'editarPlanForm');
$router->post('/superadmin/planes/{id}/editar', 'SuperAdminController', 'editarPlan');
$router->post('/superadmin/planes/{id}/toggle', 'SuperAdminController', 'togglePlan');

// Cobros y facturación
$router->get('/superadmin/cobros',                  'SuperAdminController', 'cobros');
$router->get('/superadmin/cobros/masivo',           'SuperAdminController', 'renovacionMasiva');
$router->post('/superadmin/cobros/masivo/procesar', 'SuperAdminController', 'procesarRenovacionMasiva');
$router->get('/superadmin/cobros/formulario',       'SuperAdminController', 'formularioCobro');
$router->post('/superadmin/cobros/procesar',        'SuperAdminController', 'procesarCobro');
$router->get('/superadmin/cobros/recibo/{id}',      'SuperAdminController', 'reciboCobro');

// Ingresos y reportes
$router->get('/superadmin/ingresos/exportar', 'SuperAdminController', 'exportarIngresos');
$router->get('/superadmin/ingresos',          'SuperAdminController', 'ingresos');
$router->get('/superadmin/pagos/{id}/recibo', 'SuperAdminController', 'recibo');

// Solicitudes de preregistro de colegios
$router->get('/superadmin/preregistros',                   'PreregistroController', 'lista');
$router->get('/superadmin/preregistros/{id}',              'PreregistroController', 'ver');
$router->post('/superadmin/preregistros/{id}/aprobar',     'PreregistroController', 'aprobar');
$router->post('/superadmin/preregistros/{id}/rechazar',    'PreregistroController', 'rechazar');
$router->post('/superadmin/preregistros/{id}/contactado',  'PreregistroController', 'marcarContactado');

// Usuarios superadmin
$router->get('/superadmin/usuarios',                'SuperAdminController', 'usuariosSuperAdmin');
$router->get('/superadmin/usuarios/crear',          'SuperAdminController', 'crearUsuarioSAForm');
$router->post('/superadmin/usuarios/crear',         'SuperAdminController', 'crearUsuarioSA');
$router->get('/superadmin/usuarios/{id}/editar',    'SuperAdminController', 'editarUsuarioSAForm');
$router->post('/superadmin/usuarios/{id}/editar',   'SuperAdminController', 'editarUsuarioSA');
$router->post('/superadmin/usuarios/{id}/toggle',   'SuperAdminController', 'toggleUsuarioSA');
$router->post('/superadmin/usuarios/{id}/eliminar', 'SuperAdminController', 'eliminarUsuarioSA');

// Notificaciones y SMTP
$router->get('/superadmin/notificaciones',                       'SuperAdminController', 'notificaciones');
$router->post('/superadmin/notificaciones/enviar-vencimientos',  'SuperAdminController', 'enviarAvisosVencimiento');
$router->post('/superadmin/notificaciones/enviar-individual',    'SuperAdminController', 'enviarIndividual');
$router->post('/superadmin/notificaciones/smtp-guardar',         'SuperAdminController', 'smtpGuardar');
$router->post('/superadmin/notificaciones/smtp-test',            'SuperAdminController', 'smtpTest');

// Export instituciones
$router->get('/superadmin/instituciones/exportar', 'SuperAdminController', 'exportarInstituciones');

// Salud del sistema
$router->get('/superadmin/salud',  'SuperAdminController', 'saludSistema');
$router->post('/superadmin/salud', 'SuperAdminController', 'saludSistema');

// Cron recordatorio vencimiento
$router->get('/superadmin/cron/avisos-vencimiento',  'SuperAdminController', 'cronAvisosVencimiento');
$router->post('/superadmin/cron/avisos-vencimiento', 'SuperAdminController', 'cronAvisosVencimiento');

// Desbloquear usuario
$router->post('/superadmin/desbloquear-usuario', 'SuperAdminController', 'desbloquearUsuario');

// Log de emails
$router->get('/superadmin/emails', 'SuperAdminController', 'emailsLog');

// Log de actividad
$router->get('/superadmin/log', 'SuperAdminController', 'logActividad');

// Configuración del sistema
$router->get('/superadmin/configuracion',  'SuperAdminController', 'configuracion');
$router->post('/superadmin/configuracion', 'SuperAdminController', 'guardarConfiguracion');