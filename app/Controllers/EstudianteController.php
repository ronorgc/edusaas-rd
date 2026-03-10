<?php
// =====================================================
// EstudianteController.php
// =====================================================
// CRUD de estudiantes del colegio (panel admin).
// Todos los métodos requieren rol ADMIN de institución.
//
// Bugs corregidos:
//   B-EC-1 ✅ — requireRole([ROL_ADMIN]) agregado a todos
//               los métodos públicos (reemplaza requireAuth).
//               requireRole() verifica auth + rol internamente.
//
// Bugs pendientes (en orden documentado):
//   B-EC-2 🟡 — eliminar() via GET sin CSRF (Sprint 2.1)
//   B-EC-3 🟡 — subirFoto() no valida MIME real (Sprint 2.1)
// =====================================================

class EstudianteController extends BaseController
{
    private EstudianteModel $model;
    private GradoModel      $gradoModel;
    private SeccionModel    $secModel;
    private AnoEscolarModel $anoModel;

    public function __construct()
    {
        parent::__construct();
        $this->model      = new EstudianteModel();
        $this->gradoModel = new GradoModel();
        $this->secModel   = new SeccionModel();
        $this->anoModel   = new AnoEscolarModel();
    }

    /**
     * Atajo interno: obtiene institucion_id de la sesión o redirige.
     * Centraliza la llamada para no repetir el método largo en cada acción.
     */
    private function instId(): int
    {
        return $this->getInstitucionIdOrRedirect();
    }

    // ── INDEX ──────────────────────────────────────────
    // Lista todos los estudiantes activos del colegio con filtros opcionales.
    public function index(): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido (antes: requireAuth)
        $this->requireSuscripcion();
        $instId = $this->instId();

        $filtros = [
            'busqueda'   => trim($_GET['q']            ?? ''),
            'grado_id'   => (int)($_GET['grado_id']    ?? 0) ?: null,
            'seccion_id' => (int)($_GET['seccion_id']  ?? 0) ?: null,
        ];

        $this->render('colegio/admin/estudiantes/index', [
            'estudiantes' => $this->model->getAllConMatricula($instId, $filtros),
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'stats'       => $this->model->getStats($instId),
            'filtros'     => $filtros,
        ], 'Estudiantes');
    }

    // ── VER ────────────────────────────────────────────
    // Ficha completa del estudiante con tutores y matrícula activa.
    public function ver(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $instId     = $this->instId();
        $estudiante = $this->model->getConTutores((int)$id);

        // Verifica que el estudiante pertenece al colegio en sesión
        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->render(
            'colegio/admin/estudiantes/ver',
            compact('estudiante'),
            $estudiante['nombres'] . ' ' . $estudiante['apellidos']
        );
    }

    // ── CREAR (formulario) ─────────────────────────────
    // Muestra el formulario en blanco para registrar un estudiante nuevo.
    public function crear(): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId    = $this->instId();
        $this->verificarLimite('estudiante', $instId);
        $anoActivo = $this->anoModel->getActivo($instId);

        $this->render('colegio/admin/estudiantes/form', [
            'estudiante'  => null,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'anoActivo'   => $anoActivo,
            'modoEdicion' => false,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Nuevo Estudiante');
    }

    // ── GUARDAR (POST crear) ───────────────────────────
    // Persiste el estudiante nuevo junto con su tutor y matrícula inicial.
    // Usa transacción: estudiante + tutor + matrícula se guardan o ninguno.
    public function guardar(): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->instId();
        $this->verificarLimite('estudiante', $instId);

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Foto opcional — retorna ruta relativa o null si falla validación
            $fotoRuta = null;
            if (!empty($_FILES['foto']['tmp_name'])) {
                $fotoRuta = $this->subirFoto($_FILES['foto'], $instId);
            }

            // Código único generado por el model (ej: EST-2026-00042)
            $codigo = $this->model->generarCodigo($instId);

            $estId = $this->model->create([
                'institucion_id'      => $instId,
                'codigo_estudiante'   => $codigo,
                'nombres'             => trim($_POST['nombres']),
                'apellidos'           => trim($_POST['apellidos']),
                'fecha_nacimiento'    => $_POST['fecha_nacimiento'],
                'sexo'                => $_POST['sexo'] ?? 'M',
                'cedula'              => $this->n($_POST['cedula']             ?? ''),
                'nie'                 => $this->n($_POST['nie']                ?? ''),
                'lugar_nacimiento'    => $this->n($_POST['lugar_nacimiento']   ?? ''),
                'nacionalidad'        => $_POST['nacionalidad'] ?? 'Dominicana',
                'foto'                => $fotoRuta,
                'direccion'           => $this->n($_POST['direccion']          ?? ''),
                'municipio'           => $this->n($_POST['municipio']          ?? ''),
                'provincia'           => $this->n($_POST['provincia']          ?? ''),
                'telefono'            => $this->n($_POST['telefono']           ?? ''),
                'email'               => $this->n($_POST['email']              ?? ''),
                'tipo_sangre'         => $this->n($_POST['tipo_sangre']        ?? ''),
                'alergias'            => $this->n($_POST['alergias']           ?? ''),
                'condiciones_medicas' => $this->n($_POST['condiciones_medicas'] ?? ''),
                'activo'              => 1,
            ]);

            // ── Tutor responsable (opcional) ───────────────────
            if (!empty(trim($_POST['tutor_nombres'] ?? ''))) {
                $db->prepare(
                    "INSERT INTO tutores
                     (estudiante_id, parentesco, nombres, apellidos, cedula,
                      telefono, telefono_trabajo, email, ocupacion, es_responsable)
                     VALUES (:eid, :par, :nom, :ape, :ced, :tel, :telt, :em, :oc, 1)"
                )->execute([
                    ':eid'  => $estId,
                    ':par'  => $_POST['tutor_parentesco']      ?? 'tutor',
                    ':nom'  => trim($_POST['tutor_nombres']    ?? ''),
                    ':ape'  => trim($_POST['tutor_apellidos']  ?? ''),
                    ':ced'  => $this->n($_POST['tutor_cedula']      ?? ''),
                    ':tel'  => $this->n($_POST['tutor_telefono']    ?? ''),
                    ':telt' => $this->n($_POST['tutor_tel_trabajo'] ?? ''),
                    ':em'   => $this->n($_POST['tutor_email']       ?? ''),
                    ':oc'   => $this->n($_POST['tutor_ocupacion']   ?? ''),
                ]);
            }

            // ── Matrícula inicial (opcional, requiere sección y año) ───
            $seccionId = (int)($_POST['seccion_id']     ?? 0);
            $anoId     = (int)($_POST['ano_escolar_id'] ?? 0);

            if ($seccionId && $anoId) {
                $numMat = 'MAT-' . date('Y') . '-' . str_pad($estId, 5, '0', STR_PAD_LEFT);
                $db->prepare(
                    "INSERT INTO matriculas
                     (institucion_id, estudiante_id, ano_escolar_id, seccion_id,
                      fecha_matricula, numero_matricula, estado)
                     VALUES (:inst, :est, :ano, :sec, CURDATE(), :num, 'activa')"
                )->execute([
                    ':inst' => $instId, ':est' => $estId,
                    ':ano'  => $anoId,  ':sec' => $seccionId,
                    ':num'  => $numMat,
                ]);
            }

            $db->commit();
            $this->flash('success',
                "✅ Estudiante <strong>{$_POST['nombres']} {$_POST['apellidos']}</strong>" .
                " registrado. Código: <code>{$codigo}</code>"
            );
            $this->redirect('/admin/estudiantes/' . $estId);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect('/admin/estudiantes/crear');
        }
    }

    // ── EDITAR (formulario) ────────────────────────────
    // Muestra el formulario prellenado con los datos actuales del estudiante.
    public function editar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId     = $this->instId();
        $estudiante = $this->model->getConTutores((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->render('colegio/admin/estudiantes/form', [
            'estudiante'  => $estudiante,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'anoActivo'   => $this->anoModel->getActivo($instId),
            'modoEdicion' => true,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Editar Estudiante');
    }

    // ── ACTUALIZAR (POST editar) ───────────────────────
    // Persiste los cambios del estudiante y su tutor responsable.
    // Si ya existe tutor responsable → UPDATE; si no → INSERT.
    public function actualizar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId     = $this->instId();
        $estudiante = $this->model->find((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        // Mantener foto actual si no se subió una nueva
        $fotoRuta = $estudiante['foto'];
        if (!empty($_FILES['foto']['tmp_name'])) {
            $fotoRuta = $this->subirFoto($_FILES['foto'], $instId);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $this->model->update((int)$id, [
                'nombres'             => trim($_POST['nombres']),
                'apellidos'           => trim($_POST['apellidos']),
                'fecha_nacimiento'    => $_POST['fecha_nacimiento'],
                'sexo'                => $_POST['sexo'] ?? 'M',
                'cedula'              => $this->n($_POST['cedula']             ?? ''),
                'nie'                 => $this->n($_POST['nie']                ?? ''),
                'lugar_nacimiento'    => $this->n($_POST['lugar_nacimiento']   ?? ''),
                'nacionalidad'        => $_POST['nacionalidad'] ?? 'Dominicana',
                'foto'                => $fotoRuta,
                'direccion'           => $this->n($_POST['direccion']          ?? ''),
                'municipio'           => $this->n($_POST['municipio']          ?? ''),
                'provincia'           => $this->n($_POST['provincia']          ?? ''),
                'telefono'            => $this->n($_POST['telefono']           ?? ''),
                'email'               => $this->n($_POST['email']              ?? ''),
                'tipo_sangre'         => $this->n($_POST['tipo_sangre']        ?? ''),
                'alergias'            => $this->n($_POST['alergias']           ?? ''),
                'condiciones_medicas' => $this->n($_POST['condiciones_medicas'] ?? ''),
            ]);

            // ── Tutor responsable ──────────────────────────────
            $tutorNombres = trim($_POST['tutor_nombres'] ?? '');

            if ($tutorNombres !== '') {
                // Buscar si ya existe tutor responsable para este estudiante
                $stmt = $db->prepare(
                    "SELECT id FROM tutores
                     WHERE estudiante_id = :eid AND es_responsable = 1
                     LIMIT 1"
                );
                $stmt->execute([':eid' => $id]);
                $tutorExistente = $stmt->fetchColumn();

                $datosTutor = [
                    ':par'  => $_POST['tutor_parentesco']      ?? 'tutor',
                    ':nom'  => $tutorNombres,
                    ':ape'  => trim($_POST['tutor_apellidos']  ?? ''),
                    ':ced'  => $this->n($_POST['tutor_cedula']      ?? ''),
                    ':tel'  => $this->n($_POST['tutor_telefono']    ?? ''),
                    ':telt' => $this->n($_POST['tutor_tel_trabajo'] ?? ''),
                    ':em'   => $this->n($_POST['tutor_email']       ?? ''),
                    ':oc'   => $this->n($_POST['tutor_ocupacion']   ?? ''),
                ];

                if ($tutorExistente) {
                    // Actualizar tutor existente
                    $db->prepare(
                        "UPDATE tutores SET
                            parentesco       = :par,
                            nombres          = :nom,
                            apellidos        = :ape,
                            cedula           = :ced,
                            telefono         = :tel,
                            telefono_trabajo = :telt,
                            email            = :em,
                            ocupacion        = :oc
                         WHERE id = :tid"
                    )->execute(array_merge($datosTutor, [':tid' => $tutorExistente]));
                } else {
                    // Insertar tutor nuevo como responsable
                    $db->prepare(
                        "INSERT INTO tutores
                         (estudiante_id, parentesco, nombres, apellidos, cedula,
                          telefono, telefono_trabajo, email, ocupacion, es_responsable)
                         VALUES (:eid, :par, :nom, :ape, :ced, :tel, :telt, :em, :oc, 1)"
                    )->execute(array_merge($datosTutor, [':eid' => $id]));
                }
            }

            $db->commit();
            $this->flash('success', '✅ Datos del estudiante actualizados.');
            $this->redirect('/admin/estudiantes/' . $id);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Error al actualizar: ' . $e->getMessage());
            $this->redirect('/admin/estudiantes/' . $id . '/editar');
        }
    }

    // ── ELIMINAR ───────────────────────────────────────
    // Desactivación lógica (activo = 0). No borra el registro de BD.
    // ⚠️ B-EC-2 pendiente: esta ruta acepta GET sin CSRF — corregir en Sprint 2.1.
    public function eliminar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-EC-1 corregido
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId     = $this->instId();
        $estudiante = $this->model->find((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('warning', 'Estudiante desactivado del sistema.');
        $this->redirect('/admin/estudiantes');
    }

    // ══════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════

    /**
     * Convierte string vacío a null. Usado para campos opcionales del formulario.
     * TODO (C18): mover a BaseController para reutilizar en otros controllers.
     *
     * @param  string $val  Valor crudo del $_POST
     * @return string|null  El valor trimmed, o null si estaba vacío
     */
    private function n(string $val): ?string
    {
        $v = trim($val);
        return $v === '' ? null : $v;
    }

    /**
     * Sube la foto del estudiante al directorio de la institución.
     * Solo acepta jpg/jpeg/png/webp y máx. 2 MB.
     * ⚠️ B-EC-3 pendiente: validar MIME real con finfo, no solo la extensión.
     *
     * @param  array $file    Entrada de $_FILES
     * @param  int   $instId  ID de la institución (para separar carpetas)
     * @return string|null    Ruta pública relativa, o null si falla
     */
    private function subirFoto(array $file, int $instId): ?string
    {
        $dir = rtrim($this->config['upload']['path'] ?? (__DIR__ . '/../../public/uploads/'), '/') . '/fotos/' . $instId . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validación básica por extensión (B-EC-3: añadir finfo más adelante)
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $nombre = uniqid('foto_') . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
            return '/uploads/fotos/' . $instId . '/' . $nombre;
        }

        return null;
    }
}