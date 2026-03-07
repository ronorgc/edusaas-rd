<?php
// =====================================================
// PreinscripcionController — Formulario Público
// =====================================================
// Rutas sin autenticación — accesible por padres/tutores

class PreinscripcionController extends BaseController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    private const TIPOS_PERMITIDOS = ['image/jpeg','image/png','image/webp','application/pdf'];
    private const EXT_PERMITIDAS   = ['jpg','jpeg','png','webp','pdf'];

    // ── FORMULARIO PÚBLICO ─────────────────────────────
    public function formulario(string $slug): void
    {
        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $db     = Database::getInstance();
        $grados = $db->prepare(
            "SELECT id, nombre, nivel FROM grados WHERE institucion_id = :id AND activo = 1 ORDER BY orden, nombre"
        );
        $grados->execute([':id' => $inst['id']]);

        // Leer datos de sesión ANTES de cerrar el lock
        $csrf   = $this->generateCsrfToken();
        $errors = $_SESSION['pre_errors'] ?? [];
        $old    = $_SESSION['pre_old']    ?? [];
        unset($_SESSION['pre_errors'], $_SESSION['pre_old']);

        // ── Liberar el lock de sesión ──────────────────────
        // Sin esto, si hay otra pestaña del admin abierta,
        // PHP espera el lock indefinidamente (cuelgue eterno).
        session_write_close();

        $this->renderPublic('preinscripcion/form', [
            'inst'       => $inst,
            'grados'     => $grados->fetchAll(),
            'csrf_token' => $csrf,
            'errors'     => $errors,
            'old'        => $old,
        ]);
    }

    // ── PROCESAR ENVÍO ─────────────────────────────────
    public function enviar(string $slug): void
    {
        // Detectar si PHP descartó silenciosamente los datos por exceder post_max_size.
        // Cuando esto ocurre $_POST y $_FILES llegan vacíos — sin error visible.
        if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            $maxPost = ini_get('post_max_size');
            session_write_close();
            // Redirigir de vuelta al formulario con error claro
            session_start();
            $_SESSION['pre_errors'] = ['general' =>
                "Los archivos enviados son demasiado grandes. El límite total es {$maxPost}. " .
                "Reduce el tamaño de los documentos (usa PDF comprimido o imagen JPG)."
            ];
            session_write_close();
            $this->redirect('/preinscripcion/' . $slug);
            return;
        }

        $this->verifyCsrfToken();

        // Liberar lock de sesión antes del proceso pesado (validación + upload de archivos).
        // Sin esto, cualquier pestaña del admin abierta bloquea este request indefinidamente.
        session_write_close();

        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $errors = $this->validar($_POST, $_FILES);

        if (!empty($errors)) {
            // La sesión fue cerrada arriba — reabrir brevemente para guardar errores
            session_start();
            $_SESSION['pre_errors'] = $errors;
            $_SESSION['pre_old']    = array_diff_key($_POST, array_flip(['_csrf_token']));
            session_write_close();
            $this->redirect('/preinscripcion/' . $slug);
            return;
        }

        $db = Database::getInstance();

        // Crear carpeta de uploads ANTES de abrir la transacción.
        // En Windows mkdir() puede fallar silenciosamente dentro de try/catch PDO.
        $instId   = $inst['id'];
        $dirBase  = __DIR__ . '/../../public/uploads/preinscripciones/';
        $dir      = $dirBase . $instId . '/';

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $_SESSION['pre_errors'] = ['general' =>
                    'Error interno: no se puede crear el directorio de uploads. ' .
                    'Crea manualmente la carpeta: public/uploads/preinscripciones/' . $instId . '/'
                ];
                session_write_close();
                $this->redirect('/preinscripcion/' . $slug);
                return;
            }
        }

        $db->beginTransaction();

        try {
            // $instId y $dir ya definidos arriba

            // Generar código único
            $db->prepare(
                "INSERT INTO secuencias_preinscripcion (institucion_id, ultimo_numero)
                 VALUES (:id, 1)
                 ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1"
            )->execute([':id' => $instId]);

            $num    = (int) $db->query(
                "SELECT ultimo_numero FROM secuencias_preinscripcion WHERE institucion_id = {$instId}"
            )->fetchColumn();
            $codigo = 'PRE-' . date('Y') . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

            // Subir documentos
            $docs = $this->subirDocumentos($_FILES, $dir, $instId);

            $viene = !empty($_POST['viene_de_otro_colegio']) ? 1 : 0;

            $stmt = $db->prepare("
                INSERT INTO preinscripciones (
                    institucion_id, codigo_solicitud,
                    nombres, apellidos, fecha_nacimiento, sexo,
                    cedula, nie, lugar_nacimiento, nacionalidad,
                    direccion, municipio, provincia, telefono, email_estudiante,
                    tipo_sangre, alergias, condiciones_medicas,
                    grado_id, grado_nombre,
                    tutor_parentesco, tutor_nombres, tutor_apellidos, tutor_cedula,
                    tutor_telefono, tutor_celular, tutor_email, tutor_ocupacion, tutor_direccion,
                    viene_de_otro_colegio, colegio_anterior, ultimo_grado_aprobado,
                    doc_foto, doc_acta_nacimiento, doc_cedula_tutor,
                    doc_cert_medico, doc_tarjeta_vacuna,
                    doc_notas_anteriores, doc_carta_saldo, doc_sigerd,
                    ip_origen
                ) VALUES (
                    :inst, :cod,
                    :nom, :ape, :fnac, :sexo,
                    :ced, :nie, :lnac, :nac,
                    :dir, :mun, :prov, :tel, :email,
                    :sangre, :aler, :cond,
                    :grado_id, :grado_nom,
                    :tpar, :tnom, :tape, :tced,
                    :ttel, :tcel, :tem, :toc, :tdir,
                    :viene, :col_ant, :ult_grado,
                    :dfoto, :dacta, :dced,
                    :dcert, :dvac,
                    :dnot, :dsal, :dsig,
                    :ip
                )
            ");

            $stmt->execute([
                ':inst'    => $instId,
                ':cod'     => $codigo,
                ':nom'     => trim($_POST['nombres']),
                ':ape'     => trim($_POST['apellidos']),
                ':fnac'    => $_POST['fecha_nacimiento'],
                ':sexo'    => $_POST['sexo'],
                ':ced'     => $this->n($_POST['cedula']            ?? ''),
                ':nie'     => $this->n($_POST['nie']               ?? ''),
                ':lnac'    => $this->n($_POST['lugar_nacimiento']  ?? ''),
                ':nac'     => $_POST['nacionalidad'] ?? 'Dominicana',
                ':dir'     => $this->n($_POST['direccion']         ?? ''),
                ':mun'     => $this->n($_POST['municipio']         ?? ''),
                ':prov'    => $this->n($_POST['provincia']         ?? ''),
                ':tel'     => $this->n($_POST['telefono']          ?? ''),
                ':email'   => $this->n($_POST['email_estudiante']  ?? ''),
                ':sangre'  => $this->n($_POST['tipo_sangre']       ?? ''),
                ':aler'    => $this->n($_POST['alergias']          ?? ''),
                ':cond'    => $this->n($_POST['condiciones_medicas'] ?? ''),
                ':grado_id'  => $this->n($_POST['grado_id']        ?? '') ?: null,
                ':grado_nom' => $this->n($_POST['grado_nombre']    ?? ''),
                ':tpar'    => $_POST['tutor_parentesco']           ?? 'tutor',
                ':tnom'    => trim($_POST['tutor_nombres']),
                ':tape'    => trim($_POST['tutor_apellidos']),
                ':tced'    => $this->n($_POST['tutor_cedula']      ?? ''),
                ':ttel'    => trim($_POST['tutor_telefono']),
                ':tcel'    => $this->n($_POST['tutor_celular']     ?? ''),
                ':tem'     => trim($_POST['tutor_email']),
                ':toc'     => $this->n($_POST['tutor_ocupacion']   ?? ''),
                ':tdir'    => $this->n($_POST['tutor_direccion']   ?? ''),
                ':viene'   => $viene,
                ':col_ant' => $this->n($_POST['colegio_anterior']  ?? ''),
                ':ult_grado' => $this->n($_POST['ultimo_grado_aprobado'] ?? ''),
                ':dfoto'   => $docs['foto'],
                ':dacta'   => $docs['acta_nacimiento'],
                ':dced'    => $docs['cedula_tutor'],
                ':dcert'   => $docs['cert_medico'],
                ':dvac'    => $docs['tarjeta_vacuna'],
                ':dnot'    => $docs['notas_anteriores'] ?? null,
                ':dsal'    => $docs['carta_saldo']      ?? null,
                ':dsig'    => $docs['sigerd']           ?? null,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->commit();

            // Redirigir a confirmación
            $this->redirect('/preinscripcion/' . $slug . '/gracias/' . urlencode($codigo));

        } catch (Exception $e) {
            $db->rollBack();
            // La sesión fue cerrada con session_write_close() — hay que reabrirla
            // brevemente para guardar el error antes de redirigir.
            session_start();
            $_SESSION['pre_errors'] = ['general' => 'Error al procesar. Intenta nuevamente. (' . $e->getMessage() . ')'];
            $_SESSION['pre_old']    = array_diff_key($_POST, array_flip(['_csrf_token']));
            session_write_close();
            $this->redirect('/preinscripcion/' . $slug);
        }
    }

    // ── CONFIRMACIÓN ────────────────────────────────────
    public function gracias(string $slug, string $codigo): void
    {
        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM preinscripciones WHERE codigo_solicitud = :cod AND institucion_id = :inst"
        );
        $stmt->execute([':cod' => urldecode($codigo), ':inst' => $inst['id']]);
        $pre = $stmt->fetch();
        if (!$pre) { $this->error404(); return; }

        session_write_close(); // liberar lock antes de renderizar
        $this->renderPublic('preinscripcion/gracias', compact('inst', 'pre'));
    }

    // ── ADMIN: LISTADO ──────────────────────────────────
    public function adminIndex(): void
    {
        $this->requireAuth();
        $instId = $this->getInstitucionIdOrRedirect();

        $estado = $_GET['estado'] ?? 'pendiente';
        $db     = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT p.*, g.nombre AS grado_nombre_real
             FROM preinscripciones p
             LEFT JOIN grados g ON p.grado_id = g.id
             WHERE p.institucion_id = :id
             " . ($estado !== 'todas' ? "AND p.estado = :est" : "") . "
             ORDER BY p.created_at DESC"
        );
        $params = [':id' => $instId];
        if ($estado !== 'todas') $params[':est'] = $estado;
        $stmt->execute($params);

        $totales = $db->prepare(
            "SELECT estado, COUNT(*) AS total FROM preinscripciones
             WHERE institucion_id = :id GROUP BY estado"
        );
        $totales->execute([':id' => $instId]);
        $conteos = [];
        foreach ($totales->fetchAll() as $row) $conteos[$row['estado']] = $row['total'];

        $inst = $db->prepare("SELECT * FROM instituciones WHERE id = :id");
        $inst->execute([':id' => $instId]);

        $this->render('preinscripcion/admin_index', [
            'preinscripciones' => $stmt->fetchAll(),
            'conteos'          => $conteos,
            'estadoActivo'     => $estado,
            'inst'             => $inst->fetch(),
        ], 'Preinscripciones');
    }

    // ── ADMIN: VER DETALLE ──────────────────────────────
    public function adminVer(string $id): void
    {
        $this->requireAuth();
        $instId = $this->getInstitucionIdOrRedirect();

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM preinscripciones WHERE id = :id AND institucion_id = :inst"
        );
        $stmt->execute([':id' => $id, ':inst' => $instId]);
        $pre = $stmt->fetch();
        if (!$pre) { $this->error404(); return; }

        $this->render('preinscripcion/admin_ver', [
            'pre'        => $pre,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Solicitud ' . $pre['codigo_solicitud']);
    }

    // ── ADMIN: CAMBIAR ESTADO ───────────────────────────
    public function adminActualizar(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM preinscripciones WHERE id = :id AND institucion_id = :inst"
        );
        $stmt->execute([':id' => $id, ':inst' => $instId]);
        $pre = $stmt->fetch();
        if (!$pre) { $this->error404(); return; }

        $nuevoEstado = $_POST['estado']      ?? $pre['estado'];
        $notas       = $_POST['notas_admin'] ?? '';

        $db->prepare(
            "UPDATE preinscripciones SET
                estado        = :est,
                notas_admin   = :notas,
                revisado_por  = :rev,
                fecha_revision = NOW()
             WHERE id = :id"
        )->execute([
            ':est'   => $nuevoEstado,
            ':notas' => $notas ?: null,
            ':rev'   => $_SESSION['user_id'],
            ':id'    => $id,
        ]);

        $this->flash('success', 'Solicitud actualizada a: <strong>' . $nuevoEstado . '</strong>');
        $this->redirect('/admin/preinscripciones/' . $id);
    }

    // ── ADMIN: CONVERTIR A ESTUDIANTE ───────────────────
    public function adminConvertir(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrfToken();
        $instId = $this->getInstitucionIdOrRedirect();

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.*, i.nombre AS inst_nombre, i.telefono AS inst_tel,
                     i.email AS inst_email, i.municipio AS inst_municipio,
                     i.provincia AS inst_provincia, i.logo AS inst_logo
             FROM preinscripciones p
             INNER JOIN instituciones i ON p.institucion_id = i.id
             WHERE p.id = :id AND p.institucion_id = :inst"
        );
        $stmt->execute([':id' => $id, ':inst' => $instId]);
        $pre = $stmt->fetch();

        if (!$pre || $pre['estado'] === 'convertida') {
            $this->flash('error', 'No se puede convertir esta solicitud.');
            $this->redirect('/admin/preinscripciones');
            return;
        }

        // ── FIX 1: Validar cédula duplicada ───────────────────
        if (!empty($pre['cedula'])) {
            $dupStmt = $db->prepare(
                "SELECT id, nombres, apellidos FROM estudiantes
                  WHERE institucion_id = :inst AND cedula = :ced AND activo = 1
                  LIMIT 1"
            );
            $dupStmt->execute([':inst' => $instId, ':ced' => $pre['cedula']]);
            $duplicado = $dupStmt->fetch();
            if ($duplicado) {
                $this->flash('error',
                    "⚠️ Ya existe un estudiante con la cédula <strong>{$pre['cedula']}</strong>: " .
                    "<a href='/estudiantes/{$duplicado['id']}'>" .
                    htmlspecialchars($duplicado['nombres'] . ' ' . $duplicado['apellidos']) .
                    "</a>. Verifica antes de continuar."
                );
                $this->redirect('/admin/preinscripciones/' . $id);
                return;
            }
        }

        $db->beginTransaction();
        try {
            $estModel = new EstudianteModel();
            $codigo   = $estModel->generarCodigo($instId);

            // ── FIX 2: Mover documentos al expediente del estudiante ──
            $fotoFinal = $this->moverDocumentos($pre, $instId, $codigo);

            $estId = $estModel->create([
                'institucion_id'      => $instId,
                'codigo_estudiante'   => $codigo,
                'nombres'             => $pre['nombres'],
                'apellidos'           => $pre['apellidos'],
                'fecha_nacimiento'    => $pre['fecha_nacimiento'],
                'sexo'                => $pre['sexo'],
                'cedula'              => $pre['cedula'],
                'nie'                 => $pre['nie'],
                'lugar_nacimiento'    => $pre['lugar_nacimiento'],
                'nacionalidad'        => $pre['nacionalidad'],
                'direccion'           => $pre['direccion'],
                'municipio'           => $pre['municipio'],
                'provincia'           => $pre['provincia'],
                'telefono'            => $pre['telefono'],
                'email'               => $pre['email_estudiante'] ?? null,
                'foto'                => $fotoFinal,          // ruta nueva en /fotos/
                'tipo_sangre'         => $pre['tipo_sangre'],
                'alergias'            => $pre['alergias'],
                'condiciones_medicas' => $pre['condiciones_medicas'],
                'activo'              => 1,
            ]);

            // Crear tutor
            $db->prepare(
                "INSERT INTO tutores
                 (estudiante_id,parentesco,nombres,apellidos,cedula,
                  telefono,email,ocupacion,es_responsable)
                 VALUES (:eid,:par,:nom,:ape,:ced,:tel,:em,:oc,1)"
            )->execute([
                ':eid' => $estId,
                ':par' => $pre['tutor_parentesco'],
                ':nom' => $pre['tutor_nombres'],
                ':ape' => $pre['tutor_apellidos'],
                ':ced' => $pre['tutor_cedula'],
                ':tel' => $pre['tutor_telefono'],
                ':em'  => $pre['tutor_email'],
                ':oc'  => $pre['tutor_ocupacion'],
            ]);

            // Marcar como convertida
            $db->prepare(
                "UPDATE preinscripciones SET
                    estado = 'convertida', estudiante_id = :estId,
                    revisado_por = :rev, fecha_revision = NOW()
                 WHERE id = :id"
            )->execute([':estId' => $estId, ':rev' => $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null, ':id' => $id]);

            $db->commit();

            // ── FIX 3: Notificar al padre por email ───────────────
            $emailOk = false;
            if (!empty($pre['tutor_email'])) {
                try {
                    $inst = [
                        'nombre'    => $pre['inst_nombre'],
                        'telefono'  => $pre['inst_tel'],
                        'email'     => $pre['inst_email'],
                        'municipio' => $pre['inst_municipio'],
                        'provincia' => $pre['inst_provincia'],
                        'logo'      => $pre['inst_logo'],
                    ];
                    $emailService = new EmailService();
                    $emailOk = $emailService->preinscripcionAprobada($pre, $inst, $codigo);

                    // Registrar en log de notificaciones
                    $db->prepare(
                        "INSERT INTO notificaciones_email
                         (institucion_id, tipo, destinatario, asunto, estado, enviado_por)
                         VALUES (:inst, 'personalizado', :dest, :asunto, :estado, :uid)"
                    )->execute([
                        ':inst'   => $instId,
                        ':dest'   => $pre['tutor_email'],
                        ':asunto' => "Solicitud de {$pre['nombres']} {$pre['apellidos']} aprobada",
                        ':estado' => $emailOk ? 'enviado' : 'error',
                        ':uid'    => $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null,
                    ]);
                } catch (Exception $emailEx) {
                    // El email falló pero el estudiante ya fue creado — no hacer rollback
                    $emailOk = false;
                }
            }

            $emailMsg = $emailOk
                ? " · 📧 Notificación enviada a <strong>{$pre['tutor_email']}</strong>"
                : " · ⚠️ No se pudo enviar el email al tutor";

            $appUrl = (require __DIR__ . '/../../config/app.php')['url'];
            $this->flash('success',
                "✅ Estudiante registrado con código <code>{$codigo}</code>. " .
                "<a href='{$appUrl}/estudiantes/{$estId}'>Ver ficha →</a>{$emailMsg}"
            );
            $this->redirect('/admin/preinscripciones/' . $id);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Error al crear el estudiante: ' . $e->getMessage());
            $this->redirect('/admin/preinscripciones/' . $id);
        }
    }

    /**
     * Mueve los documentos de /preinscripciones/{instId}/{token}/
     * a /uploads/estudiantes/{instId}/{codigo}/
     * Retorna la ruta de la foto del estudiante (o null).
     */
    private function moverDocumentos(array $pre, int $instId, string $codigo): ?string
    {
        $docCols = [
            'doc_foto', 'doc_acta_nacimiento', 'doc_cedula_tutor',
            'doc_cert_medico', 'doc_vacunas', 'doc_notas_anterior',
            'doc_carta_saldo', 'doc_sigerd', 'doc_extra_1', 'doc_extra_2',
        ];

        $destDir = __DIR__ . '/../../public/uploads/estudiantes/' . $instId . '/' . $codigo . '/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $fotoFinal = null;
        $basePublic = '/uploads/estudiantes/' . $instId . '/' . $codigo . '/';

        foreach ($docCols as $col) {
            if (empty($pre[$col])) continue;

            // La ruta guardada es relativa: /uploads/preinscripciones/...
            $srcRelativa = ltrim($pre[$col], '/');
            $srcAbsoluta = __DIR__ . '/../../public/' . $srcRelativa;

            if (!file_exists($srcAbsoluta)) continue;

            $nombreArchivo = basename($srcAbsoluta);
            $destAbsoluta  = $destDir . $nombreArchivo;

            if (rename($srcAbsoluta, $destAbsoluta)) {
                // Si es la foto, guardamos la ruta nueva para actualizar el registro
                if ($col === 'doc_foto') {
                    $fotoFinal = $basePublic . $nombreArchivo;
                }
            }
        }

        // Intentar limpiar el directorio de preinscripción si quedó vacío
        if (!empty($pre['doc_foto'])) {
            $tokenDir = dirname(__DIR__ . '/../../public/' . ltrim($pre['doc_foto'], '/'));
            if (is_dir($tokenDir) && count(scandir($tokenDir)) === 2) {
                rmdir($tokenDir); // solo . y ..
            }
        }

        return $fotoFinal;
    }

    // ── HELPERS ────────────────────────────────────────
    private function getInstitucionBySlug(string $slug): ?array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM instituciones WHERE subdomain = :s AND activo = 1"
        );
        $stmt->execute([':s' => $slug]);
        return $stmt->fetch() ?: null;
    }

    private function validar(array $post, array $files): array
    {
        $errors = [];

        // Datos obligatorios del estudiante
        if (empty(trim($post['nombres'] ?? '')))          $errors['nombres']          = 'El nombre es obligatorio.';
        if (empty(trim($post['apellidos'] ?? '')))        $errors['apellidos']        = 'Los apellidos son obligatorios.';
        if (empty($post['fecha_nacimiento'] ?? ''))       $errors['fecha_nacimiento'] = 'La fecha de nacimiento es obligatoria.';
        if (empty($post['sexo'] ?? ''))                   $errors['sexo']             = 'El sexo es obligatorio.';
        if (empty(trim($post['direccion'] ?? '')))        $errors['direccion']        = 'La dirección es obligatoria.';

        // Datos obligatorios del tutor
        if (empty(trim($post['tutor_nombres'] ?? '')))    $errors['tutor_nombres']    = 'El nombre del tutor es obligatorio.';
        if (empty(trim($post['tutor_apellidos'] ?? '')))  $errors['tutor_apellidos']  = 'Los apellidos del tutor son obligatorios.';
        if (empty(trim($post['tutor_telefono'] ?? '')))   $errors['tutor_telefono']   = 'El teléfono del tutor es obligatorio.';
        if (empty(trim($post['tutor_email'] ?? '')))      $errors['tutor_email']      = 'El email del tutor es obligatorio.';
        elseif (!filter_var(trim($post['tutor_email']), FILTER_VALIDATE_EMAIL))
            $errors['tutor_email'] = 'El email no es válido.';

        // Documentos obligatorios
        $docsObligatorios = [
            'foto'             => 'Foto del estudiante',
            'acta_nacimiento'  => 'Acta de nacimiento',
            'cedula_tutor'     => 'Cédula del padre/tutor',
            'cert_medico'      => 'Certificado médico',
            'tarjeta_vacuna'   => 'Tarjeta de vacunación',
        ];

        foreach ($docsObligatorios as $campo => $label) {
            if (empty($files[$campo]['tmp_name']) || $files[$campo]['error'] !== UPLOAD_ERR_OK) {
                $errors[$campo] = "{$label} es obligatorio.";
            } else {
                $err = $this->validarArchivo($files[$campo], $campo === 'foto');
                if ($err) $errors[$campo] = $err;
            }
        }

        // Documentos del colegio anterior — obligatorios si viene_de_otro_colegio
        if (!empty($post['viene_de_otro_colegio'])) {
            if (empty($files['notas_anteriores']['tmp_name']) || $files['notas_anteriores']['error'] !== UPLOAD_ERR_OK)
                $errors['notas_anteriores'] = 'Las notas del colegio anterior son obligatorias.';
            if (empty($files['carta_saldo']['tmp_name']) || $files['carta_saldo']['error'] !== UPLOAD_ERR_OK)
                $errors['carta_saldo'] = 'La carta de saldo es obligatoria.';
        }

        return $errors;
    }

    private function validarArchivo(array $file, bool $soloImagen = false): ?string
    {
        if ($file['size'] > self::MAX_FILE_SIZE)
            return 'El archivo supera el tamaño máximo de 5 MB.';

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);

        if ($soloImagen) {
            if (!in_array($ext, ['jpg','jpeg','png','webp']))
                return 'Solo se aceptan imágenes (JPG, PNG, WEBP).';
        } else {
            if (!in_array($ext, self::EXT_PERMITIDAS))
                return 'Formato no permitido. Use PDF, JPG o PNG.';
            if (!in_array($mime, self::TIPOS_PERMITIDOS))
                return 'Tipo de archivo no permitido.';
        }

        return null;
    }

    private function subirDocumentos(array $files, string $dir, int $instId): array
    {
        $campos = [
            'foto', 'acta_nacimiento', 'cedula_tutor',
            'cert_medico', 'tarjeta_vacuna',
            'notas_anteriores', 'carta_saldo', 'sigerd',
        ];

        $resultado = [];
        foreach ($campos as $campo) {
            if (!empty($files[$campo]['tmp_name']) && $files[$campo]['error'] === UPLOAD_ERR_OK) {
                $ext    = strtolower(pathinfo($files[$campo]['name'], PATHINFO_EXTENSION));
                $nombre = $campo . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($files[$campo]['tmp_name'], $dir . $nombre);
                $resultado[$campo] = '/uploads/preinscripciones/' . $instId . '/' . $nombre;
            } else {
                $resultado[$campo] = null;
            }
        }

        return $resultado;
    }

    private function n(string $val): ?string
    {
        $v = trim($val);
        return $v === '' ? null : $v;
    }

    // Renderiza sin layout de admin — layout público
    private function renderPublic(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';
        require $viewFile;
        exit;
    }
}