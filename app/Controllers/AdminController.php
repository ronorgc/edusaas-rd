<?php
// =====================================================
// EduSaaS RD — AdminController
// =====================================================
// Gestiona la configuración académica del colegio.
// Agrupa los módulos del Sprint 2.1:
//
//   — Años Escolares  (CRUD + activar año vigente)
//   — Grados          (solo listado — datos seed MINERD)
//   — Secciones       (CRUD vinculado a grado + año activo)
//   — Períodos        (CRUD vinculado al año activo)
//
// SEGURIDAD — todos los métodos siguen el patrón estándar:
//   LECTURA : requireRole → verificar suscripción → obtener instId
//   ESCRITURA: + requireModoEscritura + verifyCsrfToken + ActivityLog
//
// =====================================================

class AdminController extends BaseController
{
    private AnoEscolarModel $anoModel;
    private GradoModel      $gradoModel;
    private SeccionModel    $seccionModel;

    public function __construct()
    {
        parent::__construct();
        $this->anoModel     = new AnoEscolarModel();
        $this->gradoModel   = new GradoModel();
        $this->seccionModel = new SeccionModel();
    }

    // ══════════════════════════════════════════════════
    // MÓDULO: AÑOS ESCOLARES
    // Ruta base: /admin/anos-escolares
    // ══════════════════════════════════════════════════

    /**
     * GET /admin/anos-escolares
     * Lista todos los años escolares del colegio.
     * Muestra cuál está activo, cuántas secciones y períodos tiene cada uno.
     */
    public function anosEscolares(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        VisorMiddleware::aplicar();
        $instId = $this->getInstitucionIdOrRedirect();

        $anos     = $this->anoModel->getByInstitucion($instId);
        $anoActivo = $this->anoModel->getActivo($instId);

        // Enriquecer cada año con conteos de secciones y períodos
        $db = Database::getInstance();
        foreach ($anos as &$ano) {
            $s = $db->prepare(
                "SELECT COUNT(*) FROM secciones WHERE ano_escolar_id = ? AND activo = 1"
            );
            $s->execute([$ano['id']]);
            $ano['total_secciones'] = (int)$s->fetchColumn();

            $p = $db->prepare(
                "SELECT COUNT(*) FROM periodos WHERE ano_escolar_id = ?"
            );
            $p->execute([$ano['id']]);
            $ano['total_periodos'] = (int)$p->fetchColumn();
        }
        unset($ano);

        $this->render('colegio/admin/anos-escolares/index', [
            'anos'      => $anos,
            'anoActivo' => $anoActivo,
        ], 'Años Escolares');
    }

    /**
     * GET /admin/anos-escolares/crear
     * Muestra el formulario para crear un año escolar nuevo.
     */
    public function crearAnoEscolar(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $this->render('colegio/admin/anos-escolares/form', [
            'ano'         => null,
            'modoEdicion' => false,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Nuevo Año Escolar');
    }

    /**
     * POST /admin/anos-escolares/guardar
     * Persiste el año escolar nuevo.
     * Valida que el nombre no esté duplicado en la misma institución.
     */
    public function guardarAnoEscolar(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $nombre     = trim($_POST['nombre']       ?? '');
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin']    ?? '');

        // Validación básica
        if (empty($nombre) || empty($fechaInicio) || empty($fechaFin)) {
            $this->flash('error', 'Nombre y fechas son obligatorios.');
            $this->redirect('/admin/anos-escolares/crear');
            return;
        }

        // Verificar nombre duplicado en esta institución
        $existe = Database::getInstance()->prepare(
            "SELECT id FROM anos_escolares
             WHERE institucion_id = ? AND nombre = ? LIMIT 1"
        );
        $existe->execute([$instId, $nombre]);
        if ($existe->fetchColumn()) {
            $this->flash('error', "Ya existe un año escolar llamado <strong>{$nombre}</strong>.");
            $this->redirect('/admin/anos-escolares/crear');
            return;
        }

        Database::getInstance()->prepare(
            "INSERT INTO anos_escolares
             (institucion_id, nombre, fecha_inicio, fecha_fin, activo)
             VALUES (?, ?, ?, ?, 0)"
        )->execute([$instId, $nombre, $fechaInicio, $fechaFin]);

        ActivityLog::registrar(
            'anos_escolares', 'crear',
            "Año escolar creado: {$nombre}",
            ['entidad_tipo' => 'ano_escolar']
        );

        $this->flash('success', "✅ Año escolar <strong>{$nombre}</strong> creado correctamente.");
        $this->redirect('/admin/anos-escolares');
    }

    /**
     * GET /admin/anos-escolares/{id}/editar
     * Muestra el formulario prellenado para editar un año escolar.
     */
    public function editarAnoEscolar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $ano = $this->anoModel->find((int)$id);
        if (!$ano || (int)$ano['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        $this->render('colegio/admin/anos-escolares/form', [
            'ano'         => $ano,
            'modoEdicion' => true,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Editar Año Escolar');
    }

    /**
     * POST /admin/anos-escolares/{id}/actualizar
     * Persiste los cambios de un año escolar existente.
     * No permite editar si tiene secciones o períodos vinculados (protección de datos).
     */
    public function actualizarAnoEscolar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $ano = $this->anoModel->find((int)$id);
        if (!$ano || (int)$ano['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        $nombre      = trim($_POST['nombre']       ?? '');
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin']    ?? '');

        if (empty($nombre) || empty($fechaInicio) || empty($fechaFin)) {
            $this->flash('error', 'Nombre y fechas son obligatorios.');
            $this->redirect("/admin/anos-escolares/{$id}/editar");
            return;
        }

        Database::getInstance()->prepare(
            "UPDATE anos_escolares
             SET nombre = ?, fecha_inicio = ?, fecha_fin = ?
             WHERE id = ? AND institucion_id = ?"
        )->execute([$nombre, $fechaInicio, $fechaFin, $id, $instId]);

        ActivityLog::registrar(
            'anos_escolares', 'editar',
            "Año escolar actualizado: {$nombre}",
            ['entidad_tipo' => 'ano_escolar', 'entidad_id' => (int)$id]
        );

        $this->flash('success', "✅ Año escolar <strong>{$nombre}</strong> actualizado.");
        $this->redirect('/admin/anos-escolares');
    }

    /**
     * POST /admin/anos-escolares/{id}/activar
     * Marca un año escolar como vigente y desactiva los demás.
     * Usa AnoEscolarModel::activar() que lo hace en transacción.
     */
    public function activarAnoEscolar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $ano = $this->anoModel->find((int)$id);
        if (!$ano || (int)$ano['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        if ($this->anoModel->activar((int)$id, $instId)) {
            ActivityLog::registrar(
                'anos_escolares', 'activar',
                "Año escolar activado como vigente: {$ano['nombre']}",
                ['entidad_tipo' => 'ano_escolar', 'entidad_id' => (int)$id]
            );
            $this->flash('success', "✅ <strong>{$ano['nombre']}</strong> es ahora el año escolar vigente.");
        } else {
            $this->flash('error', 'No se pudo activar el año escolar. Intenta nuevamente.');
        }

        $this->redirect('/admin/anos-escolares');
    }

    /**
     * POST /admin/anos-escolares/{id}/eliminar
     * Elimina un año escolar solo si no tiene secciones ni períodos vinculados.
     * Nunca eliminar el año activo.
     */
    public function eliminarAnoEscolar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $ano = $this->anoModel->find((int)$id);
        if (!$ano || (int)$ano['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        // Proteger el año activo
        if ($ano['activo']) {
            $this->flash('error', 'No puedes eliminar el año escolar vigente. Activa otro primero.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        $db = Database::getInstance();

        // Verificar secciones vinculadas
        $sec = $db->prepare("SELECT COUNT(*) FROM secciones WHERE ano_escolar_id = ?");
        $sec->execute([$id]);
        if ((int)$sec->fetchColumn() > 0) {
            $this->flash('error', 'No se puede eliminar: el año tiene secciones registradas.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        // Verificar períodos vinculados
        $per = $db->prepare("SELECT COUNT(*) FROM periodos WHERE ano_escolar_id = ?");
        $per->execute([$id]);
        if ((int)$per->fetchColumn() > 0) {
            $this->flash('error', 'No se puede eliminar: el año tiene períodos de evaluación registrados.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        $db->prepare("DELETE FROM anos_escolares WHERE id = ? AND institucion_id = ?")
           ->execute([$id, $instId]);

        ActivityLog::registrar(
            'anos_escolares', 'eliminar',
            "Año escolar eliminado: {$ano['nombre']}",
            ['entidad_tipo' => 'ano_escolar', 'entidad_id' => (int)$id]
        );

        $this->flash('warning', "Año escolar <strong>{$ano['nombre']}</strong> eliminado.");
        $this->redirect('/admin/anos-escolares');
    }

    // ══════════════════════════════════════════════════
    // MÓDULO: GRADOS
    // Ruta base: /admin/grados
    // Solo lectura — datos provienen del seed MINERD.
    // El CRUD de grados no está disponible en el panel.
    // ══════════════════════════════════════════════════

    /**
     * GET /admin/grados
     * Lista los grados de la institución agrupados por nivel.
     * Muestra conteo de secciones y matriculados del año activo.
     */
    public function grados(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        VisorMiddleware::aplicar();
        $instId = $this->getInstitucionIdOrRedirect();

        $grados    = $this->gradoModel->getConConteos($instId);
        $anoActivo = $this->anoModel->getActivo($instId);

        // Agrupar por nivel para la vista
        $porNivel = ['inicial' => [], 'primario' => [], 'secundario' => []];
        foreach ($grados as $g) {
            $porNivel[$g['nivel']][] = $g;
        }

        $this->render('colegio/admin/grados/index', [
            'porNivel'  => $porNivel,
            'anoActivo' => $anoActivo,
        ], 'Grados');
    }

    // ══════════════════════════════════════════════════
    // MÓDULO: SECCIONES
    // Ruta base: /admin/secciones
    // ══════════════════════════════════════════════════

    /**
     * GET /admin/secciones
     * Lista las secciones del año escolar activo.
     * Si no hay año activo muestra advertencia y no lista secciones.
     */
    public function secciones(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        VisorMiddleware::aplicar();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        $secciones = $anoActivo
            ? $this->seccionModel->getByInstitucion($instId)
            : [];

        $this->render('colegio/admin/secciones/index', [
            'secciones' => $secciones,
            'anoActivo' => $anoActivo,
        ], 'Secciones');
    }

    /**
     * GET /admin/secciones/crear
     * Muestra el formulario para crear una sección nueva.
     * Requiere año activo — redirige si no existe.
     */
    public function crearSeccion(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        if (!$anoActivo) {
            $this->flash('error', '⚠️ Debes activar un año escolar antes de crear secciones.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        // Verificar límite del plan
        $this->verificarLimite('seccion', $instId);

        $this->render('colegio/admin/secciones/form', [
            'seccion'     => null,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'anoActivo'   => $anoActivo,
            'modoEdicion' => false,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Nueva Sección');
    }

    /**
     * POST /admin/secciones/guardar
     * Persiste la sección nueva vinculada al año activo.
     * Valida duplicado: mismo grado + mismo nombre + mismo año.
     */
    public function guardarSeccion(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        if (!$anoActivo) {
            $this->flash('error', 'No hay año escolar activo.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        $gradoId   = (int)($_POST['grado_id']  ?? 0);
        $nombre    = trim($_POST['nombre']      ?? '');
        $capacidad = (int)($_POST['capacidad']  ?? 40);

        if (!$gradoId || empty($nombre)) {
            $this->flash('error', 'El grado y el nombre de la sección son obligatorios.');
            $this->redirect('/admin/secciones/crear');
            return;
        }

        // Verificar duplicado: mismo grado + nombre + año en esta institución
        $dup = Database::getInstance()->prepare(
            "SELECT id FROM secciones
             WHERE institucion_id = ? AND ano_escolar_id = ?
               AND grado_id = ? AND nombre = ? LIMIT 1"
        );
        $dup->execute([$instId, $anoActivo['id'], $gradoId, $nombre]);
        if ($dup->fetchColumn()) {
            $this->flash('error', "Ya existe una sección <strong>{$nombre}</strong> para ese grado en el año activo.");
            $this->redirect('/admin/secciones/crear');
            return;
        }

        Database::getInstance()->prepare(
            "INSERT INTO secciones
             (institucion_id, ano_escolar_id, grado_id, nombre, capacidad, activo)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute([$instId, $anoActivo['id'], $gradoId, $nombre, $capacidad]);

        ActivityLog::registrar(
            'secciones', 'crear',
            "Sección creada: {$nombre} (año: {$anoActivo['nombre']})",
            ['entidad_tipo' => 'seccion']
        );

        $this->flash('success', "✅ Sección <strong>{$nombre}</strong> creada en {$anoActivo['nombre']}.");
        $this->redirect('/admin/secciones');
    }

    /**
     * GET /admin/secciones/{id}/editar
     * Muestra formulario prellenado para editar la sección.
     */
    public function editarSeccion(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $seccion = $this->seccionModel->find((int)$id);
        if (!$seccion || (int)$seccion['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        $this->render('colegio/admin/secciones/form', [
            'seccion'     => $seccion,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'anoActivo'   => $this->anoModel->getActivo($instId),
            'modoEdicion' => true,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Editar Sección');
    }

    /**
     * POST /admin/secciones/{id}/actualizar
     * Persiste cambios en nombre y capacidad de la sección.
     * No permite cambiar el grado si tiene estudiantes matriculados.
     */
    public function actualizarSeccion(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $seccion = $this->seccionModel->find((int)$id);
        if (!$seccion || (int)$seccion['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        $nombre    = trim($_POST['nombre']     ?? '');
        $capacidad = (int)($_POST['capacidad'] ?? 40);

        if (empty($nombre)) {
            $this->flash('error', 'El nombre de la sección es obligatorio.');
            $this->redirect("/admin/secciones/{$id}/editar");
            return;
        }

        Database::getInstance()->prepare(
            "UPDATE secciones SET nombre = ?, capacidad = ?
             WHERE id = ? AND institucion_id = ?"
        )->execute([$nombre, $capacidad, $id, $instId]);

        ActivityLog::registrar(
            'secciones', 'editar',
            "Sección actualizada: {$nombre}",
            ['entidad_tipo' => 'seccion', 'entidad_id' => (int)$id]
        );

        $this->flash('success', "✅ Sección <strong>{$nombre}</strong> actualizada.");
        $this->redirect('/admin/secciones');
    }

    /**
     * POST /admin/secciones/{id}/eliminar
     * Desactivación lógica (activo = 0).
     * Bloquea si la sección tiene matrículas activas.
     */
    public function eliminarSeccion(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $seccion = $this->seccionModel->find((int)$id);
        if (!$seccion || (int)$seccion['institucion_id'] !== $instId) {
            $this->error404();
            return;
        }

        // Verificar matrículas activas antes de eliminar
        $mat = Database::getInstance()->prepare(
            "SELECT COUNT(*) FROM matriculas WHERE seccion_id = ? AND estado = 'activa'"
        );
        $mat->execute([$id]);
        if ((int)$mat->fetchColumn() > 0) {
            $this->flash('error', 'No se puede eliminar: la sección tiene estudiantes matriculados activos.');
            $this->redirect('/admin/secciones');
            return;
        }

        Database::getInstance()->prepare(
            "UPDATE secciones SET activo = 0 WHERE id = ? AND institucion_id = ?"
        )->execute([$id, $instId]);

        ActivityLog::registrar(
            'secciones', 'eliminar',
            "Sección desactivada: {$seccion['nombre']}",
            ['entidad_tipo' => 'seccion', 'entidad_id' => (int)$id]
        );

        $this->flash('warning', "Sección <strong>{$seccion['nombre']}</strong> desactivada.");
        $this->redirect('/admin/secciones');
    }

    // ══════════════════════════════════════════════════
    // MÓDULO: PERÍODOS DE EVALUACIÓN
    // Ruta base: /admin/periodos
    // Vinculados al año escolar activo.
    // Ejemplo RD/MINERD: 1er Período, 2do Período, 3er Período,
    //   Recuperación, Período Final.
    // ══════════════════════════════════════════════════

    /**
     * GET /admin/periodos
     * Lista los períodos del año escolar activo.
     */
    public function periodos(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        VisorMiddleware::aplicar();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        $periodos  = [];

        if ($anoActivo) {
            $stmt = Database::getInstance()->prepare(
                "SELECT * FROM periodos
                 WHERE ano_escolar_id = ? AND institucion_id = ?
                 ORDER BY orden ASC, nombre ASC"
            );
            $stmt->execute([$anoActivo['id'], $instId]);
            $periodos = $stmt->fetchAll();
        }

        $this->render('colegio/admin/periodos/index', [
            'periodos'  => $periodos,
            'anoActivo' => $anoActivo,
        ], 'Períodos de Evaluación');
    }

    /**
     * GET /admin/periodos/crear
     * Formulario para crear un período nuevo en el año activo.
     */
    public function crearPeriodo(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        if (!$anoActivo) {
            $this->flash('error', '⚠️ Debes activar un año escolar antes de crear períodos.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        $this->render('colegio/admin/periodos/form', [
            'periodo'     => null,
            'anoActivo'   => $anoActivo,
            'modoEdicion' => false,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Nuevo Período');
    }

    /**
     * POST /admin/periodos/guardar
     * Persiste el período nuevo vinculado al año activo.
     */
    public function guardarPeriodo(): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $anoActivo = $this->anoModel->getActivo($instId);
        if (!$anoActivo) {
            $this->flash('error', 'No hay año escolar activo.');
            $this->redirect('/admin/anos-escolares');
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $orden  = (int)($_POST['orden'] ?? 1);

        if (empty($nombre)) {
            $this->flash('error', 'El nombre del período es obligatorio.');
            $this->redirect('/admin/periodos/crear');
            return;
        }

        // Verificar duplicado en el mismo año
        $dup = Database::getInstance()->prepare(
            "SELECT id FROM periodos
             WHERE ano_escolar_id = ? AND institucion_id = ? AND nombre = ? LIMIT 1"
        );
        $dup->execute([$anoActivo['id'], $instId, $nombre]);
        if ($dup->fetchColumn()) {
            $this->flash('error', "Ya existe un período llamado <strong>{$nombre}</strong> en el año activo.");
            $this->redirect('/admin/periodos/crear');
            return;
        }

        Database::getInstance()->prepare(
            "INSERT INTO periodos (institucion_id, ano_escolar_id, nombre, orden)
             VALUES (?, ?, ?, ?)"
        )->execute([$instId, $anoActivo['id'], $nombre, $orden]);

        ActivityLog::registrar(
            'periodos', 'crear',
            "Período creado: {$nombre} (año: {$anoActivo['nombre']})",
            ['entidad_tipo' => 'periodo']
        );

        $this->flash('success', "✅ Período <strong>{$nombre}</strong> creado.");
        $this->redirect('/admin/periodos');
    }

    /**
     * GET /admin/periodos/{id}/editar
     * Formulario prellenado para editar un período.
     */
    public function editarPeriodo(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $instId = $this->getInstitucionIdOrRedirect();

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM periodos WHERE id = ? AND institucion_id = ?"
        );
        $stmt->execute([$id, $instId]);
        $periodo = $stmt->fetch();

        if (!$periodo) {
            $this->error404();
            return;
        }

        $this->render('colegio/admin/periodos/form', [
            'periodo'     => $periodo,
            'anoActivo'   => $this->anoModel->getActivo($instId),
            'modoEdicion' => true,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Editar Período');
    }

    /**
     * POST /admin/periodos/{id}/actualizar
     * Persiste cambios de nombre y orden del período.
     */
    public function actualizarPeriodo(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM periodos WHERE id = ? AND institucion_id = ?"
        );
        $stmt->execute([$id, $instId]);
        $periodo = $stmt->fetch();

        if (!$periodo) {
            $this->error404();
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $orden  = (int)($_POST['orden'] ?? $periodo['orden']);

        if (empty($nombre)) {
            $this->flash('error', 'El nombre del período es obligatorio.');
            $this->redirect("/admin/periodos/{$id}/editar");
            return;
        }

        Database::getInstance()->prepare(
            "UPDATE periodos SET nombre = ?, orden = ?
             WHERE id = ? AND institucion_id = ?"
        )->execute([$nombre, $orden, $id, $instId]);

        ActivityLog::registrar(
            'periodos', 'editar',
            "Período actualizado: {$nombre}",
            ['entidad_tipo' => 'periodo', 'entidad_id' => (int)$id]
        );

        $this->flash('success', "✅ Período <strong>{$nombre}</strong> actualizado.");
        $this->redirect('/admin/periodos');
    }

    /**
     * POST /admin/periodos/{id}/eliminar
     * Elimina el período si no tiene calificaciones registradas.
     */
    public function eliminarPeriodo(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);
        SuscripcionMiddleware::verificar();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM periodos WHERE id = ? AND institucion_id = ?"
        );
        $stmt->execute([$id, $instId]);
        $periodo = $stmt->fetch();

        if (!$periodo) {
            $this->error404();
            return;
        }

        // Verificar que no tenga calificaciones registradas
        $cal = Database::getInstance()->prepare(
            "SELECT COUNT(*) FROM calificaciones WHERE periodo_id = ?"
        );
        $cal->execute([$id]);
        if ((int)$cal->fetchColumn() > 0) {
            $this->flash('error', 'No se puede eliminar: el período tiene calificaciones registradas.');
            $this->redirect('/admin/periodos');
            return;
        }

        Database::getInstance()->prepare(
            "DELETE FROM periodos WHERE id = ? AND institucion_id = ?"
        )->execute([$id, $instId]);

        ActivityLog::registrar(
            'periodos', 'eliminar',
            "Período eliminado: {$periodo['nombre']}",
            ['entidad_tipo' => 'periodo', 'entidad_id' => (int)$id]
        );

        $this->flash('warning', "Período <strong>{$periodo['nombre']}</strong> eliminado.");
        $this->redirect('/admin/periodos');
    }
}