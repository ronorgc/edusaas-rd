<?php
// =====================================================
// PreinscripcionController.php
// =====================================================
// Gestiona dos contextos completamente distintos:
//
//   1. PÚBLICO (sin auth): formulario de preinscripción
//      accesible por padres/tutores vía /preinscripcion/{slug}
//
//   2. ADMIN (requiere ROL_ADMIN): panel interno del colegio
//      para revisar, aprobar y convertir solicitudes.
//
// Bugs corregidos:
//   B-PC-1 ✅ — $_SESSION['user_id'] → 'usuario_id' en adminActualizar()
//               y limpiado el fallback inconsistente en adminConvertir().
//   B-PC-2 ✅ — adminIndex(), adminVer(), adminActualizar() y adminConvertir()
//               ahora usan requireRole([ROL_ADMIN]) + requireSuscripcion()
//               en lugar de solo requireAuth().
//
// Pendientes futuros:
//   C18 — helper n() duplicado → mover a BaseController
//   C19 — renderPublic() solo aquí → mover a BaseController
// =====================================================

class PreinscripcionController extends BaseController
{
    // Límite de tamaño y tipos permitidos para documentos adjuntos
    private const MAX_FILE_SIZE    = 5 * 1024 * 1024; // 5 MB
    private const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    private const EXT_PERMITIDAS   = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    // ══════════════════════════════════════════════════
    // SECCIÓN 1 — FLUJO PÚBLICO (sin autenticación)
    // ══════════════════════════════════════════════════

    // ── FORMULARIO PÚBLICO ─────────────────────────────
    // Renderiza el formulario de solicitud de ingreso del colegio.
    // Usa session_write_close() para evitar bloqueo de sesión PHP cuando
    // el admin tiene otra pestaña abierta al mismo tiempo.
    public function formulario(string $slug): void
    {
        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $db     = Database::getInstance();
        $grados = $db->prepare(
            "SELECT id, nombre, nivel
             FROM grados
             WHERE institucion_id = :id AND activo = 1
             ORDER BY orden, nombre"
        );
        $grados->execute([':id' => $inst['id']]);

        // Leer datos de sesión ANTES de liberar el lock
        $csrf   = $this->generateCsrfToken();
        $errors = $_SESSION['pre_errors'] ?? [];
        $old    = $_SESSION['pre_old']    ?? [];
        unset($_SESSION['pre_errors'], $_SESSION['pre_old']);

        // Liberar lock de sesión — sin esto, PHP espera indefinidamente
        // si hay otra pestaña del admin abierta (cuelgue eterno).
        session_write_close();

        $this->renderPublic('colegio/publico/preinscripcion/formulario', [
            'inst'       => $inst,
            'grados'     => $grados->fetchAll(),
            'csrf_token' => $csrf,
            'errors'     => $errors,
            'old'        => $old,
        ]);
    }

    // ── PROCESAR ENVÍO ─────────────────────────────────
    // Valida, sube documentos y persiste la solicitud en BD.
    // Libera el lock de sesión antes del proceso pesado (I/O de archivos).
    public function enviar(string $slug): void
    {
        // Detectar si PHP descartó los datos silenciosamente por exceder post_max_size.
        // Cuando ocurre, $_POST y $_FILES llegan vacíos sin error visible al usuario.
        if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH'])
            && (int)$_SERVER['CONTENT_LENGTH'] > 0)
        {
            $maxPost = ini_get('post_max_size');
            session_write_close();
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

        // Liberar lock antes del proceso de archivos (puede tardar varios segundos).
        // Sin esto, cualquier pestaña del admin bloquea este request indefinidamente.
        session_write_close();

        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $errors = $this->validar($_POST, $_FILES);

        if (!empty($errors)) {
            // Reabrir sesión brevemente solo para guardar los errores y redirigir
            session_start();
            $_SESSION['pre_errors'] = $errors;
            $_SESSION['pre_old']    = array_diff_key($_POST, array_flip(['_csrf_token']));
            session_write_close();
            $this->redirect('/preinscripcion/' . $slug);
            return;
        }

        $db     = Database::getInstance();
        $instId = $inst['id'];

        // Crear carpeta de uploads ANTES de abrir la transacción.
        // En Windows mkdir() puede fallar silenciosamente dentro de un catch PDO.
        $dir = __DIR__ . '/../../public/uploads/preinscripciones/' . $instId . '/';

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                session_start();
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
            // Generar número de secuencia atómico por institución
            $db->prepare(
                "INSERT INTO secuencias_preinscripcion (institucion_id, ultimo_numero)
                 VALUES (:id, 1)
                 ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1"
            )->execute([':id' => $instId]);

            // B-LP-1 ✅ — preparado para evitar inyección SQL (antes: interpolación directa)
            $numStmt = $db->prepare(
                "SELECT ultimo_numero FROM secuencias_preinscripcion
                 WHERE institucion_id = ?"
            );
            $numStmt->execute([$instId]);
            $num = (int)$numStmt->fetchColumn();
            $codigo = 'PRE-' . date('Y') . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

            // Subir documentos al disco y obtener rutas relativas
            $docs  = $this->subirDocumentos($_FILES, $dir, $instId);
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
                ':inst'      => $instId,
                ':cod'       => $codigo,
                ':nom'       => trim($_POST['nombres']),
                ':ape'       => trim($_POST['apellidos']),
                ':fnac'      => $_POST['fecha_nacimiento'],
                ':sexo'      => $_POST['sexo'],
                ':ced'       => $this->n($_POST['cedula']               ?? ''),
                ':nie'       => $this->n($_POST['nie']                  ?? ''),
                ':lnac'      => $this->n($_POST['lugar_nacimiento']     ?? ''),
                ':nac'       => $_POST['nacionalidad'] ?? 'Dominicana',
                ':dir'       => $this->n($_POST['direccion']            ?? ''),
                ':mun'       => $this->n($_POST['municipio']            ?? ''),
                ':prov'      => $this->n($_POST['provincia']            ?? ''),
                ':tel'       => $this->n($_POST['telefono']             ?? ''),
                ':email'     => $this->n($_POST['email_estudiante']     ?? ''),
                ':sangre'    => $this->n($_POST['tipo_sangre']          ?? ''),
                ':aler'      => $this->n($_POST['alergias']             ?? ''),
                ':cond'      => $this->n($_POST['condiciones_medicas']  ?? ''),
                ':grado_id'  => $this->n($_POST['grado_id']             ?? '') ?: null,
                ':grado_nom' => $this->n($_POST['grado_nombre']         ?? ''),
                ':tpar'      => $_POST['tutor_parentesco']  ?? 'tutor',
                ':tnom'      => trim($_POST['tutor_nombres']),
                ':tape'      => trim($_POST['tutor_apellidos']),
                ':tced'      => $this->n($_POST['tutor_cedula']         ?? ''),
                ':ttel'      => trim($_POST['tutor_telefono']),
                ':tcel'      => $this->n($_POST['tutor_celular']        ?? ''),
                ':tem'       => trim($_POST['tutor_email']),
                ':toc'       => $this->n($_POST['tutor_ocupacion']      ?? ''),
                ':tdir'      => $this->n($_POST['tutor_direccion']      ?? ''),
                ':viene'     => $viene,
                ':col_ant'   => $this->n($_POST['colegio_anterior']     ?? ''),
                ':ult_grado' => $this->n($_POST['ultimo_grado_aprobado'] ?? ''),
                ':dfoto'     => $docs['foto'],
                ':dacta'     => $docs['acta_nacimiento'],
                ':dced'      => $docs['cedula_tutor'],
                ':dcert'     => $docs['cert_medico'],
                ':dvac'      => $docs['tarjeta_vacuna'],
                ':dnot'      => $docs['notas_anteriores'] ?? null,
                ':dsal'      => $docs['carta_saldo']      ?? null,
                ':dsig'      => $docs['sigerd']           ?? null,
                ':ip'        => $_SERVER['REMOTE_ADDR']   ?? null,
            ]);

            $db->commit();
            $this->redirect('/preinscripcion/' . $slug . '/gracias/' . urlencode($codigo));

        } catch (Exception $e) {
            $db->rollBack();
            // Reabrir sesión brevemente para guardar el error antes de redirigir
            session_start();
            $_SESSION['pre_errors'] = ['general' =>
                'Error al procesar. Intenta nuevamente. (' . $e->getMessage() . ')'
            ];
            $_SESSION['pre_old'] = array_diff_key($_POST, array_flip(['_csrf_token']));
            session_write_close();
            $this->redirect('/preinscripcion/' . $slug);
        }
    }

    // ── CONFIRMACIÓN ────────────────────────────────────
    // Muestra la página de éxito con el código de solicitud generado.
    public function gracias(string $slug, string $codigo): void
    {
        $inst = $this->getInstitucionBySlug($slug);
        if (!$inst) { $this->error404(); return; }

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM preinscripciones
             WHERE codigo_solicitud = :cod AND institucion_id = :inst"
        );
        $stmt->execute([':cod' => urldecode($codigo), ':inst' => $inst['id']]);
        $pre = $stmt->fetch();
        if (!$pre) { $this->error404(); return; }

        session_write_close(); // liberar lock antes de renderizar
        $this->renderPublic('colegio/publico/preinscripcion/gracias', compact('inst', 'pre'));
    }

    // ══════════════════════════════════════════════════
    // SECCIÓN 2 — PANEL ADMIN (requiere ROL_ADMIN)
    // ══════════════════════════════════════════════════

    // ── ADMIN: LISTADO ──────────────────────────────────
    // Lista las solicitudes recibidas filtrables por estado.
    public function adminIndex(): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-PC-2 corregido (antes: requireAuth)
        $this->requireSuscripcion();
        $instId = $this->getInstitucionIdOrRedirect();

        $estado = $_GET['estado'] ?? 'pendiente';
        $db     = Database::getInstance();

        // Consulta con JOIN a grados para mostrar nombre real del grado
        $stmt = $db->prepare(
            "SELECT p.*, g.nombre AS grado_nombre_real
             FROM preinscripciones p
             LEFT JOIN grados g ON p.grado_id = g.id
             WHERE p.institucion_id = :id
             " . ($estado !== 'todas' ? "AND p.estado = :est" : "") . "
             ORDER BY p.created_at DESC"
        );
        $params = [':id' => $instId];
        if ($estado !== 'todas') {
            $params[':est'] = $estado;
        }
        $stmt->execute($params);

        // Conteo por estado para los badges del menú
        $totales = $db->prepare(
            "SELECT estado, COUNT(*) AS total
             FROM preinscripciones
             WHERE institucion_id = :id
             GROUP BY estado"
        );
        $totales->execute([':id' => $instId]);
        $conteos = [];
        foreach ($totales->fetchAll() as $row) {
            $conteos[$row['estado']] = $row['total'];
        }

        $instStmt = $db->prepare("SELECT * FROM instituciones WHERE id = :id");
        $instStmt->execute([':id' => $instId]);

        $this->render('colegio/admin/preinscripciones/index', [
            'preinscripciones' => $stmt->fetchAll(),
            'conteos'          => $conteos,
            'estadoActivo'     => $estado,
            'inst'             => $instStmt->fetch(),
        ], 'Preinscripciones');
    }

    // ── ADMIN: VER DETALLE ──────────────────────────────
    // Muestra la ficha completa de una solicitud con sus documentos.
    public function adminVer(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-PC-2 corregido (antes: requireAuth)
        $this->requireSuscripcion();
        $instId = $this->getInstitucionIdOrRedirect();

        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM preinscripciones WHERE id = :id AND institucion_id = :inst"
        );
        $stmt->execute([':id' => $id, ':inst' => $instId]);
        $pre = $stmt->fetch();
        if (!$pre) { $this->error404(); return; }

        $this->render('colegio/admin/preinscripciones/ver', [
            'pre'        => $pre,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Solicitud ' . $pre['codigo_solicitud']);
    }

    // ── ADMIN: CAMBIAR ESTADO ───────────────────────────
    // Actualiza el estado de la solicitud (pendiente/aprobada/rechazada)
    // y guarda notas del admin. Registra quién hizo el cambio.
    public function adminActualizar(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-PC-2 corregido (antes: requireAuth)
        $this->requireSuscripcion();
        $this->requireModoEscritura();
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
                estado         = :est,
                notas_admin    = :notas,
                revisado_por   = :rev,
                fecha_revision = NOW()
             WHERE id = :id"
        )->execute([
            ':est'   => $nuevoEstado,
            ':notas' => $notas ?: null,
            ':rev'   => $_SESSION['usuario_id'],    // ← B-PC-1 corregido (antes: 'user_id')
            ':id'    => $id,
        ]);

        $this->flash('success', 'Solicitud actualizada a: <strong>' . $nuevoEstado . '</strong>');
        $this->redirect('/admin/preinscripciones/' . $id);
    }

    // ── ADMIN: CONVERTIR A ESTUDIANTE ───────────────────
    // Convierte una solicitud aprobada en un registro de estudiante completo.
    // Flujo: validar duplicado → transacción (estudiante + tutor) →
    //        mover documentos → marcar convertida → enviar email al tutor.
    public function adminConvertir(string $id): void
    {
        $this->requireRole([ROL_ADMIN]);    // ← B-PC-2 corregido (antes: requireAuth)
        $this->requireSuscripcion();
        $this->requireModoEscritura();
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

        // Validar cédula duplicada antes de iniciar la transacción
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

            // Mover documentos de /preinscripciones/ al expediente del estudiante
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
                'foto'                => $fotoFinal,
                'tipo_sangre'         => $pre['tipo_sangre'],
                'alergias'            => $pre['alergias'],
                'condiciones_medicas' => $pre['condiciones_medicas'],
                'activo'              => 1,
            ]);

            // Crear tutor responsable del estudiante recién registrado
            $db->prepare(
                "INSERT INTO tutores
                 (estudiante_id, parentesco, nombres, apellidos, cedula,
                  telefono, email, ocupacion, es_responsable)
                 VALUES (:eid, :par, :nom, :ape, :ced, :tel, :em, :oc, 1)"
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

            // Marcar preinscripción como convertida y registrar quién lo hizo
            $db->prepare(
                "UPDATE preinscripciones SET
                    estado         = 'convertida',
                    estudiante_id  = :estId,
                    revisado_por   = :rev,
                    fecha_revision = NOW()
                 WHERE id = :id"
            )->execute([
                ':estId' => $estId,
                ':rev'   => $_SESSION['usuario_id'],    // ← B-PC-1 corregido (antes: fallback inconsistente)
                ':id'    => $id,
            ]);

            $db->commit();

            // Notificar al padre/tutor por email — fallo no revierte la transacción
            $emailOk = false;
            if (!empty($pre['tutor_email'])) {
                try {
                    $instData = [
                        'nombre'    => $pre['inst_nombre'],
                        'telefono'  => $pre['inst_tel'],
                        'email'     => $pre['inst_email'],
                        'municipio' => $pre['inst_municipio'],
                        'provincia' => $pre['inst_provincia'],
                        'logo'      => $pre['inst_logo'],
                    ];
                    $emailService = new EmailService();
                    $emailOk = $emailService->preinscripcionAprobada($pre, $instData, $codigo);

                    // Registrar intento de envío en el log de notificaciones
                    $db->prepare(
                        "INSERT INTO notificaciones_email
                         (institucion_id, tipo, destinatario, asunto, estado, enviado_por)
                         VALUES (:inst, 'personalizado', :dest, :asunto, :estado, :uid)"
                    )->execute([
                        ':inst'   => $instId,
                        ':dest'   => $pre['tutor_email'],
                        ':asunto' => "Solicitud de {$pre['nombres']} {$pre['apellidos']} aprobada",
                        ':estado' => $emailOk ? 'enviado' : 'error',
                        ':uid'    => $_SESSION['usuario_id'],    // ← B-PC-1 corregido
                    ]);
                } catch (Exception $emailEx) {
                    // El email falló — el estudiante ya fue creado, no hacemos rollback
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

    // ══════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════

    /**
     * Busca la institución por su subdomain (slug público).
     * Solo retorna instituciones activas.
     */
    private function getInstitucionBySlug(string $slug): ?array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM instituciones WHERE subdomain = :s AND activo = 1"
        );
        $stmt->execute([':s' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Valida todos los campos del formulario público.
     * Retorna array de errores; vacío = válido.
     */
    private function validar(array $post, array $files): array
    {
        $errors = [];

        // Datos obligatorios del estudiante
        if (empty(trim($post['nombres']          ?? ''))) $errors['nombres']          = 'El nombre es obligatorio.';
        if (empty(trim($post['apellidos']        ?? ''))) $errors['apellidos']        = 'Los apellidos son obligatorios.';
        if (empty($post['fecha_nacimiento']      ?? ''))  $errors['fecha_nacimiento'] = 'La fecha de nacimiento es obligatoria.';
        if (empty($post['sexo']                  ?? ''))  $errors['sexo']             = 'El sexo es obligatorio.';
        if (empty(trim($post['direccion']        ?? ''))) $errors['direccion']        = 'La dirección es obligatoria.';

        // Datos obligatorios del tutor
        if (empty(trim($post['tutor_nombres']    ?? ''))) $errors['tutor_nombres']   = 'El nombre del tutor es obligatorio.';
        if (empty(trim($post['tutor_apellidos']  ?? ''))) $errors['tutor_apellidos'] = 'Los apellidos del tutor son obligatorios.';
        if (empty(trim($post['tutor_telefono']   ?? ''))) $errors['tutor_telefono']  = 'El teléfono del tutor es obligatorio.';

        if (empty(trim($post['tutor_email'] ?? ''))) {
            $errors['tutor_email'] = 'El email del tutor es obligatorio.';
        } elseif (!filter_var(trim($post['tutor_email']), FILTER_VALIDATE_EMAIL)) {
            $errors['tutor_email'] = 'El email no es válido.';
        }

        // Documentos obligatorios
        $docsObligatorios = [
            'foto'            => 'Foto del estudiante',
            'acta_nacimiento' => 'Acta de nacimiento',
            'cedula_tutor'    => 'Cédula del padre/tutor',
            'cert_medico'     => 'Certificado médico',
            'tarjeta_vacuna'  => 'Tarjeta de vacunación',
        ];

        foreach ($docsObligatorios as $campo => $label) {
            if (empty($files[$campo]['tmp_name']) || $files[$campo]['error'] !== UPLOAD_ERR_OK) {
                $errors[$campo] = "{$label} es obligatorio.";
            } else {
                $err = $this->validarArchivo($files[$campo], $campo === 'foto');
                if ($err) {
                    $errors[$campo] = $err;
                }
            }
        }

        // Documentos de colegio anterior — obligatorios si viene_de_otro_colegio está marcado
        if (!empty($post['viene_de_otro_colegio'])) {
            if (empty($files['notas_anteriores']['tmp_name'])
                || $files['notas_anteriores']['error'] !== UPLOAD_ERR_OK)
            {
                $errors['notas_anteriores'] = 'Las notas del colegio anterior son obligatorias.';
            }
            if (empty($files['carta_saldo']['tmp_name'])
                || $files['carta_saldo']['error'] !== UPLOAD_ERR_OK)
            {
                $errors['carta_saldo'] = 'La carta de saldo es obligatoria.';
            }
        }

        return $errors;
    }

    /**
     * Valida tamaño y tipo de un archivo subido.
     * $soloImagen = true: solo acepta formatos de imagen (no PDF).
     *
     * @param  array $file        Entrada de $_FILES para un campo
     * @param  bool  $soloImagen  Si true, rechaza PDF
     * @return string|null        Mensaje de error o null si es válido
     */
    private function validarArchivo(array $file, bool $soloImagen = false): ?string
    {
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return 'El archivo supera el tamaño máximo de 5 MB.';
        }

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']); // validación MIME real con finfo

        if ($soloImagen) {
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                return 'Solo se aceptan imágenes (JPG, PNG, WEBP).';
            }
        } else {
            if (!in_array($ext, self::EXT_PERMITIDAS, true)) {
                return 'Formato no permitido. Use PDF, JPG o PNG.';
            }
            if (!in_array($mime, self::TIPOS_PERMITIDOS, true)) {
                return 'Tipo de archivo no permitido.';
            }
        }

        return null;
    }

    /**
     * Sube todos los documentos del formulario al directorio de la institución.
     * Retorna array con rutas relativas por campo; null si no se subió.
     */
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

    /**
     * Mueve los documentos de /preinscripciones/{instId}/ al expediente
     * definitivo en /uploads/estudiantes/{instId}/{codigo}/.
     * Retorna la ruta pública de la foto para actualizar el registro
     * del estudiante, o null si no había foto.
     */
    private function moverDocumentos(array $pre, int $instId, string $codigo): ?string
    {
        $docCols = [
            'doc_foto', 'doc_acta_nacimiento', 'doc_cedula_tutor',
            'doc_cert_medico', 'doc_vacunas', 'doc_notas_anterior',
            'doc_carta_saldo', 'doc_sigerd', 'doc_extra_1', 'doc_extra_2',
        ];

        $destDir    = __DIR__ . '/../../public/uploads/estudiantes/' . $instId . '/' . $codigo . '/';
        $basePublic = '/uploads/estudiantes/' . $instId . '/' . $codigo . '/';

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $fotoFinal = null;

        foreach ($docCols as $col) {
            if (empty($pre[$col])) continue;

            // Ruta guardada es relativa con slash inicial: /uploads/preinscripciones/...
            $srcRelativa = ltrim($pre[$col], '/');
            $srcAbsoluta = __DIR__ . '/../../public/' . $srcRelativa;

            if (!file_exists($srcAbsoluta)) continue;

            $nombreArchivo = basename($srcAbsoluta);
            $destAbsoluta  = $destDir . $nombreArchivo;

            if (rename($srcAbsoluta, $destAbsoluta)) {
                // Guardar ruta de la foto para el campo 'foto' del estudiante
                if ($col === 'doc_foto') {
                    $fotoFinal = $basePublic . $nombreArchivo;
                }
            }
        }

        // Limpiar directorio de preinscripción si quedó vacío
        if (!empty($pre['doc_foto'])) {
            $tokenDir = dirname(__DIR__ . '/../../public/' . ltrim($pre['doc_foto'], '/'));
            if (is_dir($tokenDir) && count(scandir($tokenDir)) === 2) {
                rmdir($tokenDir); // solo contiene . y ..
            }
        }

        return $fotoFinal;
    }

    /**
     * Convierte string vacío a null. Útil para campos opcionales del formulario.
     * TODO (C18): mover a BaseController — actualmente duplicado con EstudianteController.
     *
     * @param  string $val  Valor crudo de $_POST
     * @return string|null  El valor trimmed, o null si estaba vacío
     */
    private function n(string $val): ?string
    {
        $v = trim($val);
        return $v === '' ? null : $v;
    }

    /**
     * Renderiza una vista sin el layout del panel admin.
     * Usa require directo para el HTML público del formulario.
     * TODO (C19): mover a BaseController — solo existe aquí actualmente.
     *
     * @param string $view  Ruta relativa de la vista (puntos como separadores)
     * @param array  $data  Variables que se pasan a la vista vía extract()
     */
    private function renderPublic(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';
        require $viewFile;
        exit;
    }
}