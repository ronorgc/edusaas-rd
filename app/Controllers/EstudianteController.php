<?php
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

    private function instId(): int
    {
        return $this->getInstitucionIdOrRedirect();
    }

    // ── INDEX ──────────────────────────────────────────
    public function index(): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $instId = $this->instId();

        $filtros = [
            'busqueda'   => trim($_GET['q']           ?? ''),
            'grado_id'   => (int)($_GET['grado_id']   ?? 0) ?: null,
            'seccion_id' => (int)($_GET['seccion_id']  ?? 0) ?: null,
        ];

        $this->render('estudiantes/index', [
            'estudiantes' => $this->model->getAllConMatricula($instId, $filtros),
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'stats'       => $this->model->getStats($instId),
            'filtros'     => $filtros,
        ], 'Estudiantes');
    }

    // ── VER ────────────────────────────────────────────
    public function ver(string $id): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $instId     = $this->instId();
        $estudiante = $this->model->getConTutores((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->render('estudiantes/ver', compact('estudiante'),
            $estudiante['nombres'] . ' ' . $estudiante['apellidos']);
    }

    // ── CREAR ──────────────────────────────────────────
    public function crear(): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId    = $this->instId();
        $this->verificarLimite('estudiante', $instId);
        $anoActivo = $this->anoModel->getActivo($instId);

        $this->render('estudiantes/form', [
            'estudiante'  => null,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'anoActivo'   => $anoActivo,
            'modoEdicion' => false,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Nuevo Estudiante');
    }

    // ── GUARDAR ────────────────────────────────────────
    public function guardar(): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId = $this->instId();
        $this->verificarLimite('estudiante', $instId);

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $fotoRuta = null;
            if (!empty($_FILES['foto']['tmp_name'])) {
                $fotoRuta = $this->subirFoto($_FILES['foto'], $instId);
            }

            $codigo = $this->model->generarCodigo($instId);
            $estId  = $this->model->create([
                'institucion_id'      => $instId,
                'codigo_estudiante'   => $codigo,
                'nombres'             => trim($_POST['nombres']),
                'apellidos'           => trim($_POST['apellidos']),
                'fecha_nacimiento'    => $_POST['fecha_nacimiento'],
                'sexo'                => $_POST['sexo'] ?? 'M',
                'cedula'              => $this->n($_POST['cedula']            ?? ''),
                'nie'                 => $this->n($_POST['nie']               ?? ''),
                'lugar_nacimiento'    => $this->n($_POST['lugar_nacimiento']  ?? ''),
                'nacionalidad'        => $_POST['nacionalidad'] ?? 'Dominicana',
                'foto'                => $fotoRuta,
                'direccion'           => $this->n($_POST['direccion']         ?? ''),
                'municipio'           => $this->n($_POST['municipio']         ?? ''),
                'provincia'           => $this->n($_POST['provincia']         ?? ''),
                'telefono'            => $this->n($_POST['telefono']          ?? ''),
                'email'               => $this->n($_POST['email']             ?? ''),
                'tipo_sangre'         => $this->n($_POST['tipo_sangre']       ?? ''),
                'alergias'            => $this->n($_POST['alergias']          ?? ''),
                'condiciones_medicas' => $this->n($_POST['condiciones_medicas'] ?? ''),
                'activo'              => 1,
            ]);

            // Tutor
            if (!empty(trim($_POST['tutor_nombres'] ?? ''))) {
                $db->prepare(
                    "INSERT INTO tutores
                     (estudiante_id,parentesco,nombres,apellidos,cedula,
                      telefono,telefono_trabajo,email,ocupacion,es_responsable)
                     VALUES (:eid,:par,:nom,:ape,:ced,:tel,:telt,:em,:oc,1)"
                )->execute([
                    ':eid'  => $estId,
                    ':par'  => $_POST['tutor_parentesco']       ?? 'tutor',
                    ':nom'  => trim($_POST['tutor_nombres']     ?? ''),
                    ':ape'  => trim($_POST['tutor_apellidos']   ?? ''),
                    ':ced'  => $this->n($_POST['tutor_cedula']       ?? ''),
                    ':tel'  => $this->n($_POST['tutor_telefono']     ?? ''),
                    ':telt' => $this->n($_POST['tutor_tel_trabajo']  ?? ''),
                    ':em'   => $this->n($_POST['tutor_email']        ?? ''),
                    ':oc'   => $this->n($_POST['tutor_ocupacion']    ?? ''),
                ]);
            }

            // Matrícula
            $seccionId = (int)($_POST['seccion_id']     ?? 0);
            $anoId     = (int)($_POST['ano_escolar_id'] ?? 0);
            if ($seccionId && $anoId) {
                $numMat = 'MAT-' . date('Y') . '-' . str_pad($estId, 5, '0', STR_PAD_LEFT);
                $db->prepare(
                    "INSERT INTO matriculas
                     (institucion_id,estudiante_id,ano_escolar_id,seccion_id,
                      fecha_matricula,numero_matricula,estado)
                     VALUES (:inst,:est,:ano,:sec,CURDATE(),:num,'activa')"
                )->execute([
                    ':inst' => $instId, ':est' => $estId,
                    ':ano'  => $anoId,  ':sec' => $seccionId,
                    ':num'  => $numMat,
                ]);
            }

            $db->commit();
            $this->flash('success',
                "✅ Estudiante <strong>{$_POST['nombres']} {$_POST['apellidos']}</strong> registrado. Código: <code>{$codigo}</code>"
            );
            $this->redirect('/estudiantes/' . $estId);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect('/estudiantes/crear');
        }
    }

    // ── EDITAR ─────────────────────────────────────────
    public function editar(string $id): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId     = $this->instId();
        $estudiante = $this->model->getConTutores((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->render('estudiantes/form', [
            'estudiante'  => $estudiante,
            'grados'      => $this->gradoModel->getByInstitucion($instId),
            'secciones'   => $this->secModel->getByInstitucion($instId),
            'anoActivo'   => $this->anoModel->getActivo($instId),
            'modoEdicion' => true,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Editar Estudiante');
    }

    // ── ACTUALIZAR ─────────────────────────────────────
    public function actualizar(string $id): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $this->verifyCsrfToken();
        $instId     = $this->instId();
        $estudiante = $this->model->find((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

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
                'cedula'              => $this->n($_POST['cedula']            ?? ''),
                'nie'                 => $this->n($_POST['nie']               ?? ''),
                'lugar_nacimiento'    => $this->n($_POST['lugar_nacimiento']  ?? ''),
                'nacionalidad'        => $_POST['nacionalidad'] ?? 'Dominicana',
                'foto'                => $fotoRuta,
                'direccion'           => $this->n($_POST['direccion']         ?? ''),
                'municipio'           => $this->n($_POST['municipio']         ?? ''),
                'provincia'           => $this->n($_POST['provincia']         ?? ''),
                'telefono'            => $this->n($_POST['telefono']          ?? ''),
                'email'               => $this->n($_POST['email']             ?? ''),
                'tipo_sangre'         => $this->n($_POST['tipo_sangre']       ?? ''),
                'alergias'            => $this->n($_POST['alergias']          ?? ''),
                'condiciones_medicas' => $this->n($_POST['condiciones_medicas'] ?? ''),
            ]);

            // ── Tutor responsable ──────────────────────────────
            $tutorNombres = trim($_POST['tutor_nombres'] ?? '');

            if ($tutorNombres !== '') {
                // ¿Ya existe un tutor responsable para este estudiante?
                $stmt = $db->prepare(
                    "SELECT id FROM tutores WHERE estudiante_id = :eid AND es_responsable = 1 LIMIT 1"
                );
                $stmt->execute([':eid' => $id]);
                $tutorExistente = $stmt->fetchColumn();

                $datosTutor = [
                    ':par'  => $_POST['tutor_parentesco']       ?? 'tutor',
                    ':nom'  => $tutorNombres,
                    ':ape'  => trim($_POST['tutor_apellidos']   ?? ''),
                    ':ced'  => $this->n($_POST['tutor_cedula']       ?? ''),
                    ':tel'  => $this->n($_POST['tutor_telefono']     ?? ''),
                    ':telt' => $this->n($_POST['tutor_tel_trabajo']  ?? ''),
                    ':em'   => $this->n($_POST['tutor_email']        ?? ''),
                    ':oc'   => $this->n($_POST['tutor_ocupacion']    ?? ''),
                ];

                if ($tutorExistente) {
                    // Actualizar el tutor existente
                    $db->prepare(
                        "UPDATE tutores SET
                            parentesco        = :par,
                            nombres           = :nom,
                            apellidos         = :ape,
                            cedula            = :ced,
                            telefono          = :tel,
                            telefono_trabajo  = :telt,
                            email             = :em,
                            ocupacion         = :oc
                         WHERE id = :tid"
                    )->execute(array_merge($datosTutor, [':tid' => $tutorExistente]));
                } else {
                    // Insertar tutor nuevo
                    $db->prepare(
                        "INSERT INTO tutores
                         (estudiante_id,parentesco,nombres,apellidos,cedula,
                          telefono,telefono_trabajo,email,ocupacion,es_responsable)
                         VALUES (:eid,:par,:nom,:ape,:ced,:tel,:telt,:em,:oc,1)"
                    )->execute(array_merge($datosTutor, [':eid' => $id]));
                }
            }

            $db->commit();
            $this->flash('success', '✅ Datos del estudiante actualizados.');
            $this->redirect('/estudiantes/' . $id);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Error al actualizar: ' . $e->getMessage());
            $this->redirect('/estudiantes/' . $id . '/editar');
        }
    }

    // ── ELIMINAR ───────────────────────────────────────
    public function eliminar(string $id): void
    {
        $this->requireAuth();
        $this->requireSuscripcion();
        $this->requireModoEscritura();
        $instId     = $this->instId();
        $estudiante = $this->model->find((int)$id);

        if (!$estudiante || (int)$estudiante['institucion_id'] !== $instId) {
            $this->error404(); return;
        }

        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('warning', 'Estudiante desactivado del sistema.');
        $this->redirect('/estudiantes');
    }

    // ── HELPERS ────────────────────────────────────────
    private function n(string $val): ?string
    {
        $v = trim($val);
        return $v === '' ? null : $v;
    }

    private function subirFoto(array $file, int $instId): ?string
    {
        $dir = __DIR__ . '/../../public/uploads/fotos/' . $instId . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;

        $nombre  = uniqid('foto_') . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
            return '/uploads/fotos/' . $instId . '/' . $nombre;
        }
        return null;
    }
}