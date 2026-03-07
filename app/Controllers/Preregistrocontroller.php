<?php
// =====================================================
// EduSaaS RD - PreregistroController
// Rutas públicas: formulario de solicitud de registro
// Rutas superadmin: revisar, aprobar, rechazar
// =====================================================

class PreregistroController extends BaseController
{
    private PreregistroModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PreregistroModel();
    }

    // ══════════════════════════════════════════════
    // RUTAS PÚBLICAS — sin login
    // ══════════════════════════════════════════════

    /**
     * GET /registro
     * Formulario público de solicitud de registro.
     * Solo accesible si sistema_registro_publico = 1
     */
    public function formulario(): void
    {
        if (!$this->registroHabilitado()) {
            $this->error404();
            return;
        }

        $planModel = new PlanModel();
        // Vista standalone — no usa main.php
        $planes     = $planModel->getActivos();
        $csrf_token = $this->generateCsrfToken();
        require_once __DIR__ . '/../../views/preregistro/formulario.php';
        exit;
    }

    /**
     * POST /registro
     * Procesa la solicitud de registro.
     */
    public function enviar(): void
    {
        if (!$this->registroHabilitado()) {
            $this->error404();
            return;
        }

        $this->verifyCsrfToken();

        $nombre   = trim($_POST['nombre']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (!$nombre || !$email) {
            $this->flash(FLASH_ERROR, 'El nombre y el email son obligatorios.');
            $this->redirect('/registro');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash(FLASH_ERROR, 'El email no es válido.');
            $this->redirect('/registro');
            return;
        }

        // Evitar spam / duplicados — redirigir a gracias para evitar confusión
        if ($this->model->emailYaRegistrado($email)) {
            $this->redirect('/registro/gracias');
            return;
        }

        $planId = (int)($_POST['plan_interes'] ?? 0) ?: null;

        $this->model->create([
            'nombre'          => $nombre,
            'tipo'            => $_POST['tipo']            ?? 'privado',
            'email'           => $email,
            'telefono'        => $telefono ?: null,
            'municipio'       => trim($_POST['municipio']  ?? '') ?: null,
            'provincia'       => trim($_POST['provincia']  ?? '') ?: null,
            'codigo_minerd'   => trim($_POST['codigo_minerd'] ?? '') ?: null,
            'nombre_director' => trim($_POST['nombre_director'] ?? '') ?: null,
            'cargo_contacto'  => trim($_POST['cargo_contacto']  ?? '') ?: null,
            'cant_estudiantes'=> (int)($_POST['cant_estudiantes'] ?? 0) ?: null,
            'mensaje'         => trim($_POST['mensaje']    ?? '') ?: null,
            'plan_interes'    => $planId,
            'estado'          => 'pendiente',
            'ip_origen'       => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // Notificar al superadmin por email
        try {
            $emailService = new EmailService();
            $emailService->notificarNuevoPreregistro($nombre, $email);
        } catch (Exception $ignored) {}

        $this->redirect('/registro/gracias');
    }

    /**
     * GET /registro/gracias
     * Página de confirmación post-envío.
     */
    public function gracias(): void
    {
        // Vista standalone — no usa main.php
        $email_soporte = ConfigModel::get('empresa_email', 'soporte@edusaas.do');
        require_once __DIR__ . '/../../views/preregistro/gracias.php';
        exit;
    }

    // ══════════════════════════════════════════════
    // RUTAS SUPERADMIN — gestión de solicitudes
    // ══════════════════════════════════════════════

    /**
     * GET /superadmin/preregistros
     */
    public function lista(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $estado = $_GET['estado'] ?? '';
        $todos  = $this->model->getTodos($estado);

        // Contadores para los tabs
        $db = Database::getInstance();
        $contadores = $db->query(
            "SELECT estado, COUNT(*) AS n FROM preregistro_colegios GROUP BY estado"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->render('superadmin/preregistros/index', [
            'solicitudes' => $todos,
            'contadores'  => $contadores,
            'estadoActual'=> $estado,
            'csrf_token'  => $this->generateCsrfToken(),
        ], 'Solicitudes de Registro');
    }

    /**
     * GET /superadmin/preregistros/{id}
     */
    public function ver(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $solicitud = $this->model->getConDetalle((int)$id);
        if (!$solicitud) { $this->error404(); return; }

        $planModel = new PlanModel();

        $this->render('superadmin/preregistros/ver', [
            'solicitud'  => $solicitud,
            'planes'     => $planModel->getActivos(),
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Solicitud #' . $id);
    }

    /**
     * POST /superadmin/preregistros/{id}/aprobar
     * Aprueba la solicitud y crea la institución + usuario + suscripción.
     */
    public function aprobar(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $solicitud = $this->model->getConDetalle((int)$id);
        if (!$solicitud || $solicitud['estado'] !== 'pendiente') {
            $this->flash(FLASH_ERROR, 'Solicitud no válida o ya procesada.');
            $this->redirect('/superadmin/preregistros');
            return;
        }

        $planId   = (int)($_POST['plan_id']       ?? 0);
        $tipoFac  = $_POST['tipo_facturacion']     ?? 'mensual';
        $password = $_POST['password_admin']       ?? 'Colegio2024!';
        $notas    = trim($_POST['notas_internas']  ?? '');

        if (!$planId) {
            $this->flash(FLASH_ERROR, 'Selecciona un plan para aprobar.');
            $this->redirect('/superadmin/preregistros/' . $id);
            return;
        }

        $planModel = new PlanModel();
        $plan = $planModel->find($planId);

        // Generar subdomain a partir del nombre
        $subdomain = $this->generarSubdomain($solicitud['nombre']);

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Crear institución
            $instModel = new InstitucionModel();
            $instId = $instModel->create([
                'nombre'         => $solicitud['nombre'],
                'tipo'           => $solicitud['tipo'],
                'email'          => $solicitud['email'],
                'telefono'       => $solicitud['telefono'],
                'municipio'      => $solicitud['municipio'],
                'provincia'      => $solicitud['provincia'],
                'codigo_minerd'  => $solicitud['codigo_minerd'],
                'subdomain'      => $subdomain,
                'activo'         => 1,
            ]);

            // 2. Crear usuario admin del colegio
            $usuarioModel = new UsuarioModel();
            $usuarioModel->createWithPassword([
                'institucion_id' => $instId,
                'rol_id'         => ROL_ADMIN,
                'username'       => $subdomain . '_admin',
                'email'          => $solicitud['email'],
                'password'       => $password,
                'nombres'        => $solicitud['nombre_director'] ?: 'Administrador',
                'apellidos'      => $solicitud['nombre'],
                'activo'         => 1,
            ]);

            // 3. Crear suscripción
            $fechaInicio = date('Y-m-d');
            $fechaFin    = $tipoFac === 'anual'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));
            $monto = $tipoFac === 'anual' ? $plan['precio_anual'] : $plan['precio_mensual'];

            $suscModel = new SuscripcionModel();
            $suscId = $suscModel->create([
                'institucion_id'    => $instId,
                'plan_id'           => $planId,
                'tipo_facturacion'  => $tipoFac,
                'monto'             => $monto,
                'fecha_inicio'      => $fechaInicio,
                'fecha_vencimiento' => $fechaFin,
                'estado'            => SUSCRIPCION_ACTIVA,
                'notas'             => 'Aprobado desde preregistro #' . $id,
                'creado_por'        => $_SESSION['usuario_id'],
            ]);

            // 3b. Registrar pago en pagos_saas (aparece en Cobros e Ingresos)
            $metodoPago = $_POST['metodo_pago'] ?? 'transferencia';
            $referencia = trim($_POST['referencia'] ?? 'PREREGISTRO-' . $id);
            $pagoModel  = new PagoSaasModel();
            $pagoModel->create([
                'institucion_id'    => $instId,
                'suscripcion_id'    => $suscId,
                'plan_id'           => $planId,
                'concepto'          => 'Activación ' . ucfirst($tipoFac) . ' — ' . $plan['nombre'],
                'monto_original'    => $monto,
                'descuento_tipo'    => null,
                'descuento_valor'   => null,
                'descuento_monto'   => 0,
                'monto'             => $monto,
                'metodo_pago'       => $metodoPago,
                'referencia'        => $referencia ?: null,
                'estado'            => 'confirmado',
                'periodo_inicio'    => $fechaInicio,
                'periodo_fin'       => $fechaFin,
                'registrado_por'    => $_SESSION['usuario_id'],
            ]);

            // 4. Log
            $db->prepare(
                "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
                 VALUES (:id, 'activada', 'Aprobada desde preregistro', :usr)"
            )->execute([':id' => $instId, ':usr' => $_SESSION['usuario_id']]);

            // 5. Actualizar solicitud
            $this->model->update((int)$id, [
                'estado'         => 'aprobado',
                'notas_internas' => $notas ?: null,
                'institucion_id' => $instId,
                'revisado_por'   => $_SESSION['usuario_id'],
                'revisado_en'    => date('Y-m-d H:i:s'),
            ]);

            $db->commit();

            // 6. Email de bienvenida al colegio
            try {
                $emailService = new EmailService();
                $emailService->bienvenidaPreregistro(
                    $solicitud,
                    $subdomain . '_admin',
                    $password,
                    $plan['nombre']
                );
            } catch (Exception $ignored) {}

            ActivityLog::registrar('preregistros', 'aprobar', "Preregistro aprobado: {$solicitud['nombre']}", ['entidad_tipo'=>'institucion','entidad_id'=>$instId,'detalle'=>['plan_id'=>$planId]]);
            $this->flash(FLASH_SUCCESS,
                "✅ Institución <strong>{$solicitud['nombre']}</strong> creada. " .
                "Credenciales enviadas a <strong>{$solicitud['email']}</strong>."
            );

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash(FLASH_ERROR, 'Error al crear la institución: ' . $e->getMessage());
        }

        $this->redirect('/superadmin/preregistros/' . $id);
    }

    /**
     * POST /superadmin/preregistros/{id}/rechazar
     */
    public function rechazar(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $solicitud = $this->model->find((int)$id);
        if (!$solicitud) { $this->error404(); return; }

        $this->model->update((int)$id, [
            'estado'         => 'rechazado',
            'notas_internas' => trim($_POST['motivo'] ?? '') ?: null,
            'revisado_por'   => $_SESSION['usuario_id'],
            'revisado_en'    => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::registrar('preregistros', 'rechazar', "Preregistro rechazado: {$solicitud['nombre']}");
        $this->flash(FLASH_WARNING, 'Solicitud rechazada.');
        $this->redirect('/superadmin/preregistros');
    }

    /**
     * POST /superadmin/preregistros/{id}/contactado
     * Marca como "en contacto" sin aprobar ni rechazar.
     */
    public function marcarContactado(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $this->model->update((int)$id, [
            'estado'         => 'contactado',
            'notas_internas' => trim($_POST['notas'] ?? '') ?: null,
            'revisado_por'   => $_SESSION['usuario_id'],
            'revisado_en'    => date('Y-m-d H:i:s'),
        ]);

        $this->flash(FLASH_INFO, 'Solicitud marcada como contactado.');
        $this->redirect('/superadmin/preregistros/' . $id);
    }

    // ── Privados ─────────────────────────────────────

    private function registroHabilitado(): bool
    {
        try {
            return ConfigModel::get('sistema_registro_publico', '0') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    private function generarSubdomain(string $nombre): string
    {
        // "Colegio San José" → "colegiosanjose"
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nombre)));
        $base = substr($base, 0, 20) ?: 'colegio';

        $db = Database::getInstance();
        $sub = $base;
        $i   = 2;
        while (true) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM instituciones WHERE subdomain = :s");
            $stmt->execute([':s' => $sub]);
            if ((int)$stmt->fetchColumn() === 0) break;
            $sub = $base . $i++;
        }
        return $sub;
    }
}