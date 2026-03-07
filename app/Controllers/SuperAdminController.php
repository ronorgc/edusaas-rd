<?php
// =====================================================
// EduSaaS RD - SuperAdminController
// Solo accesible para ROL_SUPER_ADMIN
// =====================================================

class SuperAdminController extends BaseController
{
    private InstitucionModel  $instModel;
    private PlanModel         $planModel;
    private SuscripcionModel  $suscModel;
    private PagoSaasModel     $pagoModel;

    public function __construct()
    {
        parent::__construct();
        $this->instModel = new InstitucionModel();
        $this->planModel = new PlanModel();
        $this->suscModel = new SuscripcionModel();
        $this->pagoModel = new PagoSaasModel();
    }

    // --------------------------------------------------
    // CAJA DE COBROS — Flujo manual de pagos
    // --------------------------------------------------

    /**
     * GET /superadmin/cobros
     * Pantalla principal de cobros. Muestra el buscador de
     * instituciones y el historial de pagos del día.
     */
    public function cobros(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        // Instituciones con su suscripción activa para el selector
        $instituciones = $this->instModel->getAllConSuscripcion();

        // Pagos registrados HOY
        $stmt = Database::getInstance()->prepare(
            "SELECT ps.*, i.nombre AS institucion_nombre, p.nombre AS plan_nombre
             FROM pagos_saas ps
             INNER JOIN instituciones i  ON ps.institucion_id = i.id
             INNER JOIN suscripciones s  ON ps.suscripcion_id = s.id
             INNER JOIN planes p         ON s.plan_id = p.id
             WHERE ps.estado = 'confirmado'
               AND ps.fecha_pago = CURDATE()
             ORDER BY ps.created_at DESC"
        );
        $stmt->execute();
        $pagosHoy = $stmt->fetchAll();

        $totalHoy = array_sum(array_column($pagosHoy, 'monto'));

        $this->render('superadmin/cobros/index', [
            'instituciones' => $instituciones,
            'planes'        => $this->planModel->getActivos(),
            'pagosHoy'      => $pagosHoy,
            'totalHoy'      => $totalHoy,
            'csrf_token'    => $this->generateCsrfToken(),
        ], 'Caja de Cobros');
    }

    /**
     * GET /superadmin/cobros/formulario?inst_id=X
     * Carga el formulario de cobro para una institución específica
     * (llamado vía fetch/JS desde la pantalla principal).
     */
    public function formularioCobro(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $instId = (int)($_GET['inst_id'] ?? 0);
        if (!$instId) {
            echo json_encode(['error' => 'Institución no válida.']);
            return;
        }

        $inst       = $this->instModel->find($instId);
        $suscActiva = $this->suscModel->getActivaPorInstitucion($instId);
        $planes     = $this->planModel->getActivos();

        if (!$inst) {
            echo json_encode(['error' => 'Institución no encontrada.']);
            return;
        }

        // Calcular monto sugerido según plan y tipo de facturación actual
        $montoSugerido = 0;
        $planActualId  = $suscActiva['plan_id'] ?? null;

        if ($suscActiva) {
            $montoSugerido = $suscActiva['tipo_facturacion'] === 'anual'
                ? $suscActiva['precio_anual']  ?? 0
                : $suscActiva['precio_mensual'] ?? 0;
        }

        ob_start();
        require __DIR__ . '/../../views/superadmin/cobros/formulario_partial.php';
        $html = ob_get_clean();

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /**
     * POST /superadmin/cobros/procesar
     * Procesa el pago manual y redirige al recibo.
     * Flujo: formulario → procesar → recibo imprimible
     */
    public function procesarCobro(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $instId     = (int)($_POST['institucion_id']  ?? 0);
        $planId     = (int)($_POST['plan_id']          ?? 0);
        $tipoFac    = $_POST['tipo_facturacion']        ?? 'mensual';
        $metodo     = $_POST['metodo_pago']             ?? 'efectivo';
        $referencia = trim($_POST['referencia']         ?? '');
        $notas      = trim($_POST['notas']              ?? '');
        $montoCustom      = (float)($_POST['monto_custom']    ?? 0);
        $descuentoTipo    = $_POST['descuento_tipo']            ?? '';
        $descuentoValor   = (float)($_POST['descuento_valor']   ?? 0);
        $descuentoMotivo  = trim($_POST['descuento_motivo']     ?? '');

        // Validación
        if (!$instId || !$planId) {
            $this->flash(FLASH_ERROR, 'Completa todos los campos obligatorios.');
            $this->redirect('/superadmin/cobros');
            return;
        }

        $inst = $this->instModel->find($instId);
        $plan = $this->planModel->find($planId);

        if (!$inst || !$plan) {
            $this->flash(FLASH_ERROR, 'Institución o plan no válido.');
            $this->redirect('/superadmin/cobros');
            return;
        }

        // Calcular monto: si el admin escribió uno custom, usar ese
        $montoBase = $montoCustom > 0
            ? $montoCustom
            : ($tipoFac === 'anual' ? (float)$plan['precio_anual'] : (float)$plan['precio_mensual']);

        // Aplicar descuento
        $descuentoMonto = 0;
        if ($descuentoTipo === 'pct' && $descuentoValor > 0) {
            $descuentoMonto = round($montoBase * $descuentoValor / 100, 2);
        } elseif ($descuentoTipo === 'monto' && $descuentoValor > 0) {
            $descuentoMonto = min($descuentoValor, $montoBase);
        }
        $monto = max(0, $montoBase - $descuentoMonto);

        $hoy      = date('Y-m-d');
        $fechaFin = $tipoFac === 'anual'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Cancelar suscripción anterior
            $db->prepare(
                "UPDATE suscripciones SET estado = 'cancelada'
                 WHERE institucion_id = :id AND estado IN ('activa','vencida','suspendida')"
            )->execute([':id' => $instId]);

            // 2. Crear nueva suscripción
            $suscId = $this->suscModel->create([
                'institucion_id'    => $instId,
                'plan_id'           => $planId,
                'tipo_facturacion'  => $tipoFac,
                'monto'             => $monto,
                'fecha_inicio'      => $hoy,
                'fecha_vencimiento' => $fechaFin,
                'estado'            => SUSCRIPCION_ACTIVA,
                'notas'             => $notas ?: null,
                'creado_por'        => $_SESSION['usuario_id'],
            ]);

            // 3. Registrar pago
            $numeroFactura = $this->pagoModel->generarNumeroFactura();
            $pagoId = $this->pagoModel->create([
                'institucion_id' => $instId,
                'suscripcion_id' => $suscId,
                'numero_factura' => $numeroFactura,
                'monto'          => $monto,
                'fecha_pago'     => $hoy,
                'metodo_pago'    => $metodo,
                'referencia'     => $referencia ?: null,
                'periodo_desde'  => $hoy,
                'periodo_hasta'  => $fechaFin,
                'estado'         => 'confirmado',
                'notas'          => $notas ?: null,
                'registrado_por'  => $_SESSION['usuario_id'],
                'monto_original'  => $descuentoMonto > 0 ? $montoBase  : null,
                'descuento_monto' => $descuentoMonto > 0 ? $descuentoMonto : null,
                'descuento_pct'   => ($descuentoTipo === 'pct' && $descuentoMonto > 0) ? $descuentoValor : null,
                'descuento_motivo'=> $descuentoMotivo ?: null,
            ]);

            // 4. Reactivar institución si estaba suspendida
            $this->instModel->update($instId, ['activo' => 1]);

            $db->commit();

            // 5. Enviar correo de confirmación
            try {
                $emailService = new EmailService();
                $pagoData     = $this->pagoModel->find($pagoId);
                $suscData     = ['plan_nombre' => $plan['nombre']];
                $emailOk      = $emailService->confirmacionPago($inst, $pagoData, $suscData);
                (new NotificacionModel())->create([
                    'institucion_id' => $instId,
                    'tipo'           => 'plan_renovado',
                    'destinatario'   => $inst['email'],
                    'asunto'         => '✅ Confirmación de pago ' . $numeroFactura,
                    'estado'         => $emailOk ? 'enviado' : 'error',
                    'error_detalle'  => $emailOk ? null : $emailService->getLastError(),
                    'enviado_por'    => $_SESSION['usuario_id'],
                ]);
            } catch (Exception $ignored) {}

            // 6. Redirigir directo al recibo para imprimir
            $this->redirect('/superadmin/cobros/recibo/' . $pagoId);

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash(FLASH_ERROR, 'Error al procesar el pago: ' . $e->getMessage());
            $this->redirect('/superadmin/cobros');
        }
    }

    /**
     * GET /superadmin/cobros/masivo
     * Lista todos los colegios vencidos o por vencer — cobro masivo.
     */
    public function renovacionMasiva(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $db = Database::getInstance();

        // Colegios vencidos o que vencen en los próximos 30 días
        $stmt = $db->query(
            "SELECT i.id, i.nombre, i.email, i.activo,
                    s.id AS susc_id, s.tipo_facturacion, s.fecha_vencimiento,
                    s.estado AS susc_estado,
                    p.id AS plan_id, p.nombre AS plan_nombre,
                    p.precio_mensual, p.precio_anual, p.color AS plan_color,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM instituciones i
             INNER JOIN suscripciones s ON s.institucion_id = i.id
                 AND s.estado IN ('activa','vencida','suspendida')
             INNER JOIN planes p ON s.plan_id = p.id
             WHERE s.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY s.fecha_vencimiento ASC"
        );
        $pendientes = $stmt->fetchAll();

        $this->render('superadmin/cobros/masivo', [
            'pendientes' => $pendientes,
            'planes'     => $this->planModel->getActivos(),
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Renovación Masiva');
    }

    /**
     * POST /superadmin/cobros/masivo/procesar
     * Procesa múltiples renovaciones en una sola operación.
     */
    public function procesarRenovacionMasiva(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $seleccionados = $_POST['seleccionados'] ?? [];
        $metodo        = $_POST['metodo_pago'] ?? 'transferencia';
        $tipoFac       = $_POST['tipo_facturacion'] ?? 'mensual';

        if (empty($seleccionados)) {
            $this->flash(FLASH_ERROR, 'Selecciona al menos un colegio para renovar.');
            $this->redirect('/superadmin/cobros/masivo');
            return;
        }

        $db   = Database::getInstance();
        $hoy  = date('Y-m-d');
        $ok   = 0;
        $fail = 0;
        $totalMonto = 0;

        foreach ($seleccionados as $instId) {
            $instId = (int)$instId;
            $planId = (int)($_POST["plan_{$instId}"]  ?? 0);
            $monto  = (float)($_POST["monto_{$instId}"] ?? 0);

            if (!$instId || !$planId || $monto <= 0) { $fail++; continue; }

            $inst = $this->instModel->find($instId);
            $plan = $this->planModel->find($planId);
            if (!$inst || !$plan) { $fail++; continue; }

            $fechaFin = $tipoFac === 'anual'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));

            $db->beginTransaction();
            try {
                // Cancelar suscripción anterior
                $db->prepare(
                    "UPDATE suscripciones SET estado = 'cancelada'
                     WHERE institucion_id = :id AND estado IN ('activa','vencida','suspendida')"
                )->execute([':id' => $instId]);

                // Nueva suscripción
                $suscId = $this->suscModel->create([
                    'institucion_id'    => $instId,
                    'plan_id'           => $planId,
                    'tipo_facturacion'  => $tipoFac,
                    'monto'             => $monto,
                    'fecha_inicio'      => $hoy,
                    'fecha_vencimiento' => $fechaFin,
                    'estado'            => SUSCRIPCION_ACTIVA,
                    'notas'             => 'Renovación masiva',
                    'creado_por'        => $_SESSION['usuario_id'],
                ]);

                // Pago
                $numFact = $this->pagoModel->generarNumeroFactura();
                $pagoId  = $this->pagoModel->create([
                    'institucion_id' => $instId,
                    'suscripcion_id' => $suscId,
                    'numero_factura' => $numFact,
                    'monto'          => $monto,
                    'fecha_pago'     => $hoy,
                    'metodo_pago'    => $metodo,
                    'periodo_desde'  => $hoy,
                    'periodo_hasta'  => $fechaFin,
                    'estado'         => 'confirmado',
                    'notas'          => 'Renovación masiva',
                    'registrado_por' => $_SESSION['usuario_id'],
                ]);

                // Reactivar si estaba suspendido
                $this->instModel->update($instId, ['activo' => 1]);

                $db->commit();
                $ok++;
                $totalMonto += $monto;

                // Email en background — no bloquea si falla
                try {
                    $emailService = new EmailService();
                    $pagoData     = $this->pagoModel->find($pagoId);
                    $suscData     = ['plan_nombre' => $plan['nombre']];
                    $emailOk      = $emailService->confirmacionPago($inst, $pagoData, $suscData);
                    (new NotificacionModel())->create([
                        'institucion_id' => $instId,
                        'tipo'           => 'plan_renovado',
                        'destinatario'   => $inst['email'],
                        'asunto'         => "✅ Renovación de suscripción {$numFact}",
                        'estado'         => $emailOk ? 'enviado' : 'error',
                        'error_detalle'  => $emailOk ? null : $emailService->getLastError(),
                        'enviado_por'    => $_SESSION['usuario_id'],
                    ]);
                } catch (Exception $ignored) {}

            } catch (Exception $e) {
                $db->rollBack();
                $fail++;
            }
        }

        $total = 'RD$' . number_format($totalMonto, 0, '.', ',');
        if ($ok > 0 && $fail === 0) {
            $this->flash(FLASH_SUCCESS,
                "✅ <strong>{$ok} colegio(s)</strong> renovados correctamente — Total cobrado: <strong>{$total}</strong>"
            );
        } elseif ($ok > 0) {
            $this->flash(FLASH_WARNING,
                "⚠️ {$ok} renovados OK — {$fail} fallaron. Total cobrado: {$total}"
            );
        } else {
            $this->flash(FLASH_ERROR, 'No se pudo procesar ninguna renovación.');
        }

        $this->redirect('/superadmin/cobros/masivo');
    }

    /**
     * GET /superadmin/cobros/recibo/{id}
     * Recibo completo post-cobro con opción de imprimir
     * y botón para registrar otro pago.
     */
    public function reciboCobro(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $pago = $this->pagoModel->find((int)$id);
        if (!$pago) { $this->error404(); return; }

        $inst = $this->instModel->find($pago['institucion_id']);
        $susc = $this->suscModel->find($pago['suscripcion_id']);
        $plan = $susc ? $this->planModel->find($susc['plan_id']) : null;

        // Vista de recibo post-cobro (con botones de acción)
        require_once __DIR__ . '/../../views/superadmin/cobros/recibo_cobro.php';
        exit;
    }

    // --------------------------------------------------
    // RECIBO DE PAGO (imprimible desde historial)
    // --------------------------------------------------

    /**
     * GET /superadmin/pagos/{id}/recibo
     * Renderiza el recibo de un pago específico para imprimir.
     */
    public function recibo(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $pago = $this->pagoModel->find((int)$id);
        if (!$pago) { $this->error404(); return; }

        $inst = $this->instModel->find($pago['institucion_id']);
        $susc = $this->suscModel->find($pago['suscripcion_id']);
        $plan = $susc ? $this->planModel->find($susc['plan_id']) : null;

        // Vista dedicada de recibo (sin layout del sistema, solo impresión)
        require_once __DIR__ . '/../../views/superadmin/pagos/recibo.php';
        exit;
    }

    // --------------------------------------------------
    // NOTIFICACIONES
    // --------------------------------------------------

    /**
     * GET /superadmin/notificaciones
     * Panel de notificaciones: historial + acción de envío masivo.
     */
    public function notificaciones(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $notifModel  = new NotificacionModel();
        $historial   = $notifModel->getAllConDetalle(50);
        $stats       = $notifModel->getStats();
        $porVencer   = $this->suscModel->getPorVencer(7);

        $this->render('superadmin/notificaciones/index', [
            'historial'  => $historial,
            'stats'      => $stats,
            'porVencer'  => $porVencer,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Notificaciones');
    }

    /**
     * POST /superadmin/notificaciones/enviar-vencimientos
     * Envía correos a todas las instituciones que vencen en N días.
     * Se puede ejecutar manualmente o configurar como cron job.
     */

    // --------------------------------------------------
    // SMTP — Guardar configuración desde el panel
    // --------------------------------------------------

    public function smtpGuardar(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $driver   = $_POST['driver']          ?? 'smtp';
        $host     = trim($_POST['smtp_host']  ?? '');
        $port     = trim($_POST['smtp_port']  ?? '587');
        $enc      = $_POST['smtp_encryption'] ?? 'tls';
        $user     = trim($_POST['smtp_user']  ?? '');
        // FIX: quitar espacios — Google muestra "abcd efgh ijkl mnop" pero deben pegarse sin espacios
        $pass     = str_replace(' ', '', trim($_POST['smtp_password'] ?? ''));

        // Leer .env actual y reemplazar o agregar las claves
        $envPath = __DIR__ . '/../../.env';
        $lineas  = file_exists($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];

        $nuevasClaves = [
            'MAIL_DRIVER'          => $driver,
            'MAIL_FROM_EMAIL'      => $user ?: ($_ENV['MAIL_FROM_EMAIL'] ?? ''),
            'MAIL_SMTP_HOST'       => $host,
            'MAIL_SMTP_PORT'       => $port,
            'MAIL_SMTP_ENCRYPTION' => $enc,
            'MAIL_SMTP_USER'       => $user,
        ];

        // Solo actualizar password si se envió uno nuevo (no placeholder)
        if ($pass !== '' && $pass !== '(guardada)') {
            $nuevasClaves['MAIL_SMTP_PASSWORD'] = $pass;
        }

        // Actualizar líneas existentes o agregar nuevas
        $encontradas = [];
        foreach ($lineas as &$linea) {
            if (str_starts_with(trim($linea), '#') || !str_contains($linea, '=')) continue;
            [$clave] = explode('=', $linea, 2);
            $clave = trim($clave);
            if (array_key_exists($clave, $nuevasClaves)) {
                $linea = $clave . '=' . $nuevasClaves[$clave];
                $encontradas[] = $clave;
            }
        }
        unset($linea);

        // Agregar las que no existían
        foreach ($nuevasClaves as $k => $v) {
            if (!in_array($k, $encontradas)) {
                $lineas[] = $k . '=' . $v;
            }
        }

        file_put_contents($envPath, implode(PHP_EOL, $lineas) . PHP_EOL);

        // FIX: actualizar $_ENV en memoria para que el redirect use los valores nuevos
        foreach ($nuevasClaves as $k => $v) {
            $_ENV[$k] = $v;
        }

        ActivityLog::registrar('configuracion', 'smtp', 'Configuración SMTP actualizada');
        $this->flash(FLASH_SUCCESS, '✅ Configuración SMTP guardada. Usa "Enviar prueba" para verificar.');
        $this->redirect('/superadmin/notificaciones');
    }

    // --------------------------------------------------
    // SMTP — Enviar email de prueba
    // --------------------------------------------------

    public function smtpTest(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $emailPrueba = trim($_POST['email_prueba'] ?? '');
        if (!filter_var($emailPrueba, FILTER_VALIDATE_EMAIL)) {
            $this->flash(FLASH_ERROR, 'Email de destino no válido.');
            $this->redirect('/superadmin/notificaciones');
            return;
        }

        $mail = new EmailService();
        $ok   = $mail->enviar(
            $emailPrueba,
            '🧪 EduSaaS RD — Prueba de configuración SMTP',
            'smtp_test',
            ['fecha' => date('d/m/Y H:i:s')]
        );
        $err = $mail->getLastError();

        if ($ok) {
            $this->flash(FLASH_SUCCESS,
                "✅ Email de prueba enviado a <strong>{$emailPrueba}</strong>. " .
                "Si no llega en 1 minuto, revisa la carpeta de Spam."
            );
        } else {
            $detail = $err ? "<br><small class='font-monospace'>{$err}</small>" : '';
            $this->flash(FLASH_ERROR,
                "❌ Falló el envío a {$emailPrueba}.{$detail}"
            );
        }

        $this->redirect('/superadmin/notificaciones');
    }

    public function enviarAvisosVencimiento(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $emailService = new EmailService();
        $notifModel   = new NotificacionModel();

        $enviados = 0;
        $errores  = 0;

        // Verificar vencimientos a 7, 3 y 0 días
        foreach ([7, 3, 0] as $dias) {
            $instituciones = $this->suscModel->getPorVencer($dias);

            foreach ($instituciones as $item) {
                // Solo las que vencen exactamente en $dias días
                if ((int)$item['dias_restantes'] !== $dias) continue;

                $tipo = match($dias) {
                    7 => 'vencimiento_7dias',
                    3 => 'vencimiento_3dias',
                    0 => 'vencimiento_hoy',
                };

                // No enviar si ya se mandó hoy
                if ($notifModel->yaEnviadaHoy((int)$item['institucion_id'], $tipo)) {
                    continue;
                }

                $inst = $this->instModel->find((int)$item['institucion_id']);
                if (!$inst || empty($inst['email'])) continue;

                $ok = $emailService->avisoVencimiento($inst, $item, $dias);

                $notifModel->create([
                    'institucion_id' => $inst['id'],
                    'tipo'           => $tipo,
                    'destinatario'   => $inst['email'],
                    'asunto'         => "Aviso de vencimiento ({$dias} días)",
                    'estado'         => $ok ? 'enviado' : 'error',
                    'enviado_por'    => $_SESSION['usuario_id'],
                ]);

                $ok ? $enviados++ : $errores++;
            }
        }

        $msg = "✅ Avisos enviados: {$enviados}.";
        if ($errores) $msg .= " ⚠️ Errores: {$errores}.";
        if ($enviados === 0 && $errores === 0) $msg = "ℹ️ No hay instituciones que requieran aviso hoy (o ya fueron notificadas).";

        $this->flash(FLASH_SUCCESS, $msg);
        $this->redirect('/superadmin/notificaciones');
    }

    /**
     * POST /superadmin/notificaciones/enviar-individual
     * Envía un correo personalizado a una institución específica.
     */
    public function enviarIndividual(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $instId  = (int)($_POST['institucion_id'] ?? 0);
        $asunto  = trim($_POST['asunto']          ?? '');
        $mensaje = trim($_POST['mensaje']         ?? '');

        if (!$instId || !$asunto || !$mensaje) {
            $this->flash(FLASH_ERROR, 'Completa todos los campos.');
            $this->redirect('/superadmin/notificaciones');
            return;
        }

        $inst = $this->instModel->find($instId);
        if (!$inst || empty($inst['email'])) {
            $this->flash(FLASH_ERROR, 'Institución no encontrada o sin email.');
            $this->redirect('/superadmin/notificaciones');
            return;
        }

        $emailService = new EmailService();
        $ok  = $emailService->personalizado($inst, $asunto, $mensaje);
        $err = $emailService->getLastError();

        $notifModel = new NotificacionModel();
        $notifModel->create([
            'institucion_id' => $instId,
            'tipo'           => 'personalizado',
            'destinatario'   => $inst['email'],
            'asunto'         => $asunto,
            'estado'         => $ok ? 'enviado' : 'error',
            'error_detalle'  => $err ?: null,
            'enviado_por'    => $_SESSION['usuario_id'],
        ]);

        $errMsg = $err ? " <br><small class='text-muted font-monospace'>{$err}</small>" : '';
        $this->flash(
            $ok ? FLASH_SUCCESS : FLASH_ERROR,
            $ok
                ? "✅ Correo enviado a <strong>{$inst['email']}</strong>."
                : "❌ Error al enviar a {$inst['email']}.{$errMsg}"
        );
        $this->redirect('/superadmin/notificaciones');
    }

    // --------------------------------------------------
    // USUARIOS DEL SUPERADMIN
    // --------------------------------------------------

    public function usuariosSuperAdmin(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $db      = Database::getInstance();
        $usuarios = $db->query(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON u.rol_id = r.id
             WHERE u.rol_id = " . ROL_SUPER_ADMIN . "
             ORDER BY u.nombres"
        )->fetchAll();

        $this->render('superadmin/usuarios/index', [
            'usuarios'   => $usuarios,
            'yo'         => (int)$_SESSION['usuario_id'],
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Usuarios Super Admin');
    }

    public function crearUsuarioSAForm(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->render('superadmin/usuarios/crear', [
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Nuevo Super Admin');
    }

    public function crearUsuarioSA(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $nombres  = trim($_POST['nombres']  ?? '');
        $apellidos= trim($_POST['apellidos'] ?? '');
        $username = trim($_POST['username']  ?? '');
        $email    = trim($_POST['email']     ?? '');
        $password = $_POST['password']       ?? '';

        if (!$nombres || !$username || !$email || !$password) {
            $this->flash(FLASH_ERROR, 'Completa todos los campos obligatorios.');
            $this->redirect('/superadmin/usuarios/crear');
            return;
        }

        $usuarioModel = new UsuarioModel();

        if ($usuarioModel->usernameExiste($username)) {
            $this->flash(FLASH_ERROR, "El username <strong>{$username}</strong> ya está en uso.");
            $this->redirect('/superadmin/usuarios/crear');
            return;
        }

        $db = Database::getInstance();
        $stmtEmail = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :e");
        $stmtEmail->execute([':e' => $email]);
        $emailExiste = (int)$stmtEmail->fetchColumn();

        if ($emailExiste) {
            $this->flash(FLASH_ERROR, "El email <strong>{$email}</strong> ya está registrado.");
            $this->redirect('/superadmin/usuarios/crear');
            return;
        }

        $usuarioModel->createWithPassword([
            'institucion_id' => null,
            'rol_id'         => ROL_SUPER_ADMIN,
            'username'       => $username,
            'email'          => $email,
            'password'       => $password,
            'nombres'        => $nombres,
            'apellidos'      => $apellidos,
            'activo'         => 1,
        ]);

        ActivityLog::registrar('usuarios', 'crear', "Super admin creado: {$nombres} {$apellidos}", ['entidad_tipo'=>'usuario']);

        // Email de bienvenida al nuevo superadmin
        try {
            (new EmailService())->enviar(
                $email,
                'Bienvenido al equipo — EduSaaS RD',
                'personalizado',
                [
                    'asunto'  => 'Tus credenciales de acceso',
                    'titulo'  => "¡Bienvenido, {$nombres}!",
                    'mensaje' => "Se ha creado tu cuenta de <strong>Super Administrador</strong> en EduSaaS RD." .
                                 "<br><br><strong>Usuario:</strong> {$username}" .
                                 "<br><strong>Contraseña:</strong> (la que configuraste)" .
                                 "<br><br>Guarda estos datos en un lugar seguro.",
                    'inst'    => ['nombre' => 'EduSaaS RD', 'email' => $email],
                ]
            );
        } catch (Exception $ignored) {}

        $this->flash(FLASH_SUCCESS, "✅ Super admin <strong>{$nombres} {$apellidos}</strong> creado. 📧 Email enviado.");
        $this->redirect('/superadmin/usuarios');
    }

    public function editarUsuarioSAForm(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);

        if (!$usuario || (int)$usuario['rol_id'] !== ROL_SUPER_ADMIN) {
            $this->error404(); return;
        }

        $this->render('superadmin/usuarios/editar', [
            'usuario'    => $usuario,
            'yo'         => (int)$_SESSION['usuario_id'],
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Editar Super Admin');
    }

    public function editarUsuarioSA(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);

        if (!$usuario || (int)$usuario['rol_id'] !== ROL_SUPER_ADMIN) {
            $this->error404(); return;
        }

        $datos = [
            'nombres'   => trim($_POST['nombres']   ?? ''),
            'apellidos' => trim($_POST['apellidos']  ?? ''),
            'email'     => trim($_POST['email']      ?? ''),
        ];

        // Cambiar contraseña solo si se proporcionó
        $nuevaPass = trim($_POST['password'] ?? '');
        if ($nuevaPass !== '') {
            if (strlen($nuevaPass) < 8) {
                $this->flash(FLASH_ERROR, 'La contraseña debe tener al menos 8 caracteres.');
                $this->redirect('/superadmin/usuarios/' . $id . '/editar');
                return;
            }
            $usuarioModel->changePassword((int)$id, $nuevaPass);
        }

        $usuarioModel->update((int)$id, $datos);
        $this->flash(FLASH_SUCCESS, '✅ Datos actualizados.');
        $this->redirect('/superadmin/usuarios');
    }

    public function toggleUsuarioSA(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        // No puede desactivarse a sí mismo
        if ((int)$id === (int)$_SESSION['usuario_id']) {
            $this->flash(FLASH_ERROR, 'No puedes desactivar tu propia cuenta.');
            $this->redirect('/superadmin/usuarios');
            return;
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);

        if (!$usuario || (int)$usuario['rol_id'] !== ROL_SUPER_ADMIN) {
            $this->error404(); return;
        }

        $nuevo = $usuario['activo'] ? 0 : 1;
        $usuarioModel->update((int)$id, ['activo' => $nuevo]);

        $msg = $nuevo
            ? "✅ Usuario <strong>{$usuario['nombres']}</strong> activado."
            : "Usuario <strong>{$usuario['nombres']}</strong> desactivado.";
        ActivityLog::registrar('usuarios', $nuevo ? 'activar' : 'desactivar', ($nuevo ? 'Usuario SA activado: ' : 'Usuario SA desactivado: ') . $usuario['nombres'] . ' ' . $usuario['apellidos'], ['entidad_tipo'=>'usuario','entidad_id'=>(int)$id]);
        $this->flash($nuevo ? FLASH_SUCCESS : FLASH_WARNING, $msg);
        $this->redirect('/superadmin/usuarios');
    }

    public function eliminarUsuarioSA(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        if ((int)$id === (int)$_SESSION['usuario_id']) {
            $this->flash(FLASH_ERROR, 'No puedes eliminar tu propia cuenta.');
            $this->redirect('/superadmin/usuarios');
            return;
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);

        if (!$usuario || (int)$usuario['rol_id'] !== ROL_SUPER_ADMIN) {
            $this->error404(); return;
        }

        Database::getInstance()
            ->prepare("DELETE FROM usuarios WHERE id = :id AND rol_id = :rol")
            ->execute([':id' => $id, ':rol' => ROL_SUPER_ADMIN]);

        ActivityLog::registrar('usuarios', 'eliminar', "Super admin eliminado: {$usuario['nombres']} {$usuario['apellidos']}");
        $this->flash(FLASH_WARNING, "Usuario <strong>{$usuario['nombres']}</strong> eliminado.");
        $this->redirect('/superadmin/usuarios');
    }

    // --------------------------------------------------
    // MODO VISOR — Revisar datos de un colegio (solo lectura)
    // --------------------------------------------------

    /**
     * GET /superadmin/instituciones/{id}/revisar
     * Activa el modo visor para ese colegio y redirige
     * al dashboard del colegio en modo lectura.
     */
    public function revisarColegio(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $inst = $this->instModel->find((int)$id);
        if (!$inst) {
            $this->error404();
            return;
        }

        // Activar modo visor
        VisorMiddleware::activar((int)$id, $inst['nombre']);

        $this->flash(FLASH_INFO,
            '👁️ Modo visor activo: <strong>' . htmlspecialchars($inst['nombre']) . '</strong>. ' .
            'Solo puedes consultar datos. No puedes modificar nada.'
        );

        $this->redirect('/dashboard');
    }

    // --------------------------------------------------
    // DASHBOARD SUPER ADMIN
    // --------------------------------------------------

    public function dashboard(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        // Marcar suscripciones vencidas automáticamente
        $this->suscModel->marcarVencidas();

        // Calcular todas las métricas de negocio
        $metricas    = (new MetricasService())->calcularTodo();
        $porVencer   = $this->suscModel->getPorVencer(DIAS_AVISO_VENCIMIENTO);
        $ultimosPagos = $this->pagoModel->getAllConDetalle(date('Y'), date('n'));

        $this->render('superadmin/dashboard', [
            'metricas'     => $metricas,
            'porVencer'    => $porVencer,
            'ultimosPagos' => array_slice($ultimosPagos, 0, 6),
        ], 'Panel Super Admin');
    }

    // --------------------------------------------------
    // INSTITUCIONES
    // --------------------------------------------------

    public function instituciones(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $filtroEstado = $_GET['estado'] ?? '';
        $busqueda     = trim($_GET['q'] ?? '');
        $instituciones = $this->instModel->getAllConSuscripcion();

        // Filtrar por estado
        if ($filtroEstado) {
            $instituciones = array_filter(
                $instituciones,
                fn($i) => ($i['suscripcion_estado'] ?? '') === $filtroEstado
            );
        }

        // Filtrar por búsqueda de texto
        if ($busqueda) {
            $q = strtolower($busqueda);
            $instituciones = array_filter(
                $instituciones,
                fn($i) => str_contains(strtolower($i['nombre'] ?? ''), $q)
                       || str_contains(strtolower($i['email'] ?? ''), $q)
                       || str_contains(strtolower($i['subdomain'] ?? ''), $q)
            );
        }

        $this->render('superadmin/instituciones/index', [
            'instituciones' => array_values($instituciones),
            'filtroEstado'  => $filtroEstado,
            'busqueda'      => $busqueda,
            'planes'        => $this->planModel->getActivos(),
            'csrf_token'    => $this->generateCsrfToken(),
        ], 'Instituciones');
    }

    public function crearInstitucionForm(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->render('superadmin/instituciones/crear', [
            'planes'     => $this->planModel->getActivos(),
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Nueva Institución');
    }

    public function crearInstitucion(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        // --- Validación ---
        $nombre   = trim($_POST['nombre'] ?? '');
        $tipo     = $_POST['tipo']        ?? '';
        $email    = trim($_POST['email']  ?? '');
        $subdomain = strtolower(trim($_POST['subdomain'] ?? ''));
        $planId    = (int)($_POST['plan_id']        ?? 0);
        $tipo_fac  = $_POST['tipo_facturacion']        ?? 'mensual';
        $tipoSusc  = $_POST['tipo_susc']               ?? 'normal';
        $trialDias = (int)($_POST['trial_dias']        ?? 14);
        $esTrial   = $tipoSusc === 'trial';

        if (!$nombre || !$tipo || !$email || !$subdomain || !$planId) {
            $this->flash(FLASH_ERROR, 'Completa todos los campos obligatorios.');
            $this->redirect('/superadmin/instituciones/crear');
            return;
        }

        // Verificar subdominio único
        if ($this->instModel->exists(['subdomain' => $subdomain])) {
            $this->flash(FLASH_ERROR, "El subdominio '{$subdomain}' ya está en uso.");
            $this->redirect('/superadmin/instituciones/crear');
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Crear institución
            $instId = $this->instModel->create([
                'nombre'    => $nombre,
                'tipo'      => $tipo,
                'email'     => $email,
                'subdomain' => $subdomain,
                'telefono'  => trim($_POST['telefono'] ?? ''),
                'municipio' => trim($_POST['municipio'] ?? ''),
                'provincia' => trim($_POST['provincia'] ?? ''),
                'codigo_minerd' => trim($_POST['codigo_minerd'] ?? ''),
                'activo'    => 1,
            ]);

            // 2. Crear usuario admin del colegio
            $usuarioModel = new UsuarioModel();
            $passwordAdmin = $_POST['password_admin'] ?? 'Colegio2024!';
            $usuarioModel->createWithPassword([
                'institucion_id' => $instId,
                'rol_id'         => ROL_ADMIN,
                'username'       => $subdomain . '_admin',
                'email'          => $email,
                'password'       => $passwordAdmin,
                'nombres'        => 'Administrador',
                'apellidos'      => $nombre,
                'activo'         => 1,
            ]);

            // 3. Crear suscripción
            $plan = $this->planModel->find($planId);
            $fechaInicio = date('Y-m-d');
            $fechaFin = $tipo_fac === 'anual'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));

            $monto = $esTrial ? 0
                : ($tipo_fac === 'anual' ? $plan['precio_anual'] : $plan['precio_mensual']);

            if ($esTrial) {
                $fechaFin = date('Y-m-d', strtotime("+{$trialDias} days"));
            }

            $suscId = $this->suscModel->create([
                'institucion_id'   => $instId,
                'plan_id'          => $planId,
                'tipo_facturacion' => $tipo_fac,
                'monto'            => $monto,
                'fecha_inicio'     => $fechaInicio,
                'fecha_vencimiento'=> $fechaFin,
                'es_trial'         => $esTrial ? 1 : 0,
                'trial_dias'       => $esTrial ? $trialDias : null,
                'estado'           => SUSCRIPCION_ACTIVA,
                'notas'            => $esTrial ? "Trial de {$trialDias} días" : null,
                'creado_por'       => $_SESSION['usuario_id'],
            ]);

            // 4. Registrar pago inicial si se indicó (nunca en trial)
            if (!$esTrial && !empty($_POST['registrar_pago'])) {
                $this->pagoModel->create([
                    'institucion_id' => $instId,
                    'suscripcion_id' => $suscId,
                    'numero_factura' => $this->pagoModel->generarNumeroFactura(),
                    'monto'          => $monto,
                    'fecha_pago'     => date('Y-m-d'),
                    'metodo_pago'    => $_POST['metodo_pago'] ?? 'efectivo',
                    'referencia'     => trim($_POST['referencia'] ?? ''),
                    'periodo_desde'  => $fechaInicio,
                    'periodo_hasta'  => $fechaFin,
                    'estado'         => 'confirmado',
                    'registrado_por' => $_SESSION['usuario_id'],
                ]);
            }

            // 5. Log
            $db->prepare(
                "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
                 VALUES (:id, 'activada', 'Institución creada', :usr)"
            )->execute([':id' => $instId, ':usr' => $_SESSION['usuario_id']]);

            $db->commit();

            // 6. Email de bienvenida con credenciales
            $emailOk  = false;
            $emailErr = '';
            if (!empty($email)) {
                try {
                    $instData = [
                        'nombre'    => $nombre,
                        'email'     => $email,
                        'subdomain' => $subdomain,
                        'telefono'  => trim($_POST['telefono'] ?? ''),
                        'municipio' => trim($_POST['municipio'] ?? ''),
                        'provincia' => trim($_POST['provincia'] ?? ''),
                    ];
                    $emailService = new EmailService();
                    $emailOk = $emailService->bienvenida(
                        $instData,
                        $subdomain . '_admin',
                        $passwordAdmin
                    );
                    $emailErr = $emailService->getLastError();

                    // Registrar en log de notificaciones
                    $db->prepare(
                        "INSERT INTO notificaciones_email
                         (institucion_id, tipo, destinatario, asunto, estado, error_detalle, enviado_por)
                         VALUES (:inst, 'bienvenida', :dest, :asunto, :estado, :err, :uid)"
                    )->execute([
                        ':inst'   => $instId,
                        ':dest'   => $email,
                        ':asunto' => "¡Bienvenido a EduSaaS RD! — Tus credenciales de acceso",
                        ':estado' => $emailOk ? 'enviado' : 'error',
                        ':err'    => $emailErr ?: null,
                        ':uid'    => $_SESSION['usuario_id'],
                    ]);
                } catch (Exception $emailEx) {
                    $emailErr = $emailEx->getMessage();
                }
            }

            $emailMsg = $emailOk
                ? " · 📧 Credenciales enviadas a <strong>{$email}</strong>"
                : " · ⚠️ No se pudo enviar el email de bienvenida" .
                  ($emailErr ? " <small class='font-monospace'>({$emailErr})</small>" : '');

            ActivityLog::registrar('instituciones', 'crear', "Institución creada: {$nombre}", ['entidad_tipo'=>'institucion','entidad_id'=>$instId]);
            $this->flash(FLASH_SUCCESS, "✅ Institución '{$nombre}' creada exitosamente.{$emailMsg}");
            $this->redirect('/superadmin/instituciones');

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash(FLASH_ERROR, 'Error al crear la institución: ' . $e->getMessage());
            $this->redirect('/superadmin/instituciones/crear');
        }
    }


    // --------------------------------------------------
    // EDITAR INSTITUCIÓN
    // --------------------------------------------------

    public function editarInstitucionForm(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $inst = $this->instModel->find((int)$id);
        if (!$inst) { $this->error404(); return; }

        $this->render('superadmin/instituciones/editar', [
            'inst'       => $inst,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Editar — ' . $inst['nombre']);
    }

    public function editarInstitucion(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $inst = $this->instModel->find((int)$id);
        if (!$inst) { $this->error404(); return; }

        $nombre    = trim($_POST['nombre']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $subdomain = strtolower(trim($_POST['subdomain'] ?? ''));

        if (!$nombre || !$email || !$subdomain) {
            $this->flash(FLASH_ERROR, 'Nombre, email y subdominio son obligatorios.');
            $this->redirect('/superadmin/instituciones/' . $id . '/editar');
            return;
        }

        // Verificar subdominio único (excluyendo la propia institución)
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id FROM instituciones WHERE subdomain = :sub AND id != :id LIMIT 1"
        );
        $stmt->execute([':sub' => $subdomain, ':id' => $id]);
        if ($stmt->fetch()) {
            $this->flash(FLASH_ERROR, "El subdominio '{$subdomain}' ya está en uso por otro colegio.");
            $this->redirect('/superadmin/instituciones/' . $id . '/editar');
            return;
        }

        $emailCambio = ($email !== ($inst['email'] ?? ''));

        $this->instModel->update((int)$id, [
            'nombre'        => $nombre,
            'tipo'          => $_POST['tipo']          ?? $inst['tipo'],
            'email'         => $email,
            'subdomain'     => $subdomain,
            'telefono'      => trim($_POST['telefono']      ?? ''),
            'municipio'     => trim($_POST['municipio']     ?? ''),
            'provincia'     => trim($_POST['provincia']     ?? ''),
            'codigo_minerd' => trim($_POST['codigo_minerd'] ?? ''),
            'rnc'           => trim($_POST['rnc']           ?? ''),
            'direccion'     => trim($_POST['direccion']     ?? ''),
        ]);

        // Log de estado
        $db->prepare(
            "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
             VALUES (:id, 'plan_cambiado', 'Datos editados por super admin', :usr)"
        )->execute([':id' => $id, ':usr' => $_SESSION['usuario_id']]);

        // Si cambió el email → notificar al nuevo correo con datos de acceso
        $emailMsg = '';
        if ($emailCambio) {
            try {
                $adminUser = $db->prepare(
                    "SELECT username FROM usuarios WHERE institucion_id = :id AND rol_id = :rol ORDER BY id ASC LIMIT 1"
                );
                $adminUser->execute([':id' => $id, ':rol' => ROL_ADMIN]);
                $adminData = $adminUser->fetch();
                if ($adminData) {
                    (new EmailService())->enviar(
                        $email,
                        'Tus datos de acceso — ' . $nombre,
                        'personalizado',
                        [
                            'asunto'  => '🔑 Datos de acceso actualizados',
                            'titulo'  => 'Datos de acceso',
                            'mensaje' => "El email de tu cuenta en <strong>EduSaaS RD</strong> ha sido actualizado." .
                                         "<br><br><strong>Usuario:</strong> " . htmlspecialchars($adminData['username']) .
                                         "<br><strong>Email:</strong> " . htmlspecialchars($email) .
                                         "<br><br>Si no reconoces este cambio, contáctanos de inmediato.",
                            'inst'    => ['nombre' => $nombre, 'email' => $email],
                        ]
                    );
                    $emailMsg = ' 📧 Credenciales enviadas al nuevo email.';
                }
            } catch (Exception $ignored) {}
        }

        ActivityLog::registrar('instituciones', 'editar', "Editada: {$nombre}", ['entidad_tipo'=>'institucion','entidad_id'=>(int)$id]);
        $this->flash(FLASH_SUCCESS, "✅ Datos de <strong>{$nombre}</strong> actualizados.{$emailMsg}");
        $this->redirect('/superadmin/instituciones/' . $id);
    }

    // --------------------------------------------------
    // USUARIOS DEL COLEGIO — Gestión y reset de clave
    // --------------------------------------------------

    public function usuariosColegio(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $inst = $this->instModel->find((int)$id);
        if (!$inst) { $this->error404(); return; }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON u.rol_id = r.id
             WHERE u.institucion_id = :id
             ORDER BY r.id, u.apellidos, u.nombres"
        );
        $stmt->execute([':id' => $id]);
        $usuarios = $stmt->fetchAll();

        $this->render('superadmin/instituciones/usuarios', [
            'inst'       => $inst,
            'usuarios'   => $usuarios,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Usuarios — ' . $inst['nombre']);
    }

    public function resetPassword(string $instId, string $userId): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $db = Database::getInstance();

        // Verificar que el usuario pertenece a esta institución
        $stmt = $db->prepare(
            "SELECT u.*, i.nombre AS inst_nombre, i.email AS inst_email
             FROM usuarios u
             INNER JOIN instituciones i ON u.institucion_id = i.id
             WHERE u.id = :uid AND u.institucion_id = :inst"
        );
        $stmt->execute([':uid' => $userId, ':inst' => $instId]);
        $usuario = $stmt->fetch();

        if (!$usuario) { $this->error404(); return; }

        $nuevaClave = trim($_POST['nueva_clave'] ?? '');

        // Si no se envió clave manual, generar una aleatoria segura
        if ($nuevaClave === '') {
            $nuevaClave = ucfirst(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5))
                        . rand(100, 999)
                        . str_shuffle('!@#$')[0];
        }

        if (strlen($nuevaClave) < 8) {
            $this->flash(FLASH_ERROR, 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/superadmin/instituciones/' . $instId . '/usuarios');
            return;
        }

        $db->prepare(
            "UPDATE usuarios SET password = :pwd WHERE id = :uid"
        )->execute([
            ':pwd' => password_hash($nuevaClave, PASSWORD_DEFAULT),
            ':uid' => $userId,
        ]);

        // Intentar enviar email con la nueva clave
        $emailEnviado = false;
        if (!empty($usuario['email'])) {
            try {
                $emailEnviado = (new EmailService())->enviar(
                    $usuario['email'],
                    '🔑 Tu contraseña ha sido restablecida — ' . $usuario['inst_nombre'],
                    'reset_clave',
                    [
                        'usuario'   => $usuario,
                        'nuevaClave'=> $nuevaClave,
                        'inst'      => ['nombre' => $usuario['inst_nombre'], 'email' => $usuario['inst_email']],
                    ]
                );
            } catch (Exception $ignored) {}
        }

        $msg = "✅ Contraseña de <strong>{$usuario['nombres']} {$usuario['apellidos']}</strong> restablecida.";
        if (!$emailEnviado && !empty($usuario['email'])) {
            $msg .= " ⚠️ No se pudo enviar email. Nueva clave: <code>{$nuevaClave}</code>";
        } elseif ($emailEnviado) {
            $msg .= " 📧 Nueva clave enviada a <strong>{$usuario['email']}</strong>.";
        } else {
            $msg .= " Nueva clave: <code>{$nuevaClave}</code>";
        }

        $this->flash(FLASH_SUCCESS, $msg);
        $this->redirect('/superadmin/instituciones/' . $instId . '/usuarios');
    }

    public function toggleUsuario(string $instId, string $userId): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT id, activo, nombres, apellidos FROM usuarios
             WHERE id = :uid AND institucion_id = :inst"
        );
        $stmt->execute([':uid' => $userId, ':inst' => $instId]);
        $usuario = $stmt->fetch();
        if (!$usuario) { $this->error404(); return; }

        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $db->prepare("UPDATE usuarios SET activo = :a WHERE id = :id")
           ->execute([':a' => $nuevoEstado, ':id' => $userId]);

        $accion = $nuevoEstado ? 'activado' : 'desactivado';
        $this->flash(FLASH_SUCCESS,
            "Usuario <strong>{$usuario['nombres']} {$usuario['apellidos']}</strong> {$accion}."
        );
        $this->redirect('/superadmin/instituciones/' . $instId . '/usuarios');
    }

    public function verInstitucion(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $inst      = $this->instModel->find((int)$id);
        if (!$inst) { $this->error404(); return; }

        $suscActiva = $this->suscModel->getActivaPorInstitucion((int)$id);
        $historial  = $this->suscModel->getHistorialPorInstitucion((int)$id);
        $pagos      = $this->pagoModel->getByInstitucion((int)$id);

        // Log de actividad
        $db  = Database::getInstance();
        $log = $db->prepare(
            "SELECT l.*, u.nombres, u.apellidos
             FROM log_estado_instituciones l
             LEFT JOIN usuarios u ON l.realizado_por = u.id
             WHERE l.institucion_id = :id
             ORDER BY l.created_at DESC
             LIMIT 50"
        );
        $log->execute([':id' => (int)$id]);
        $logActividad = $log->fetchAll();

        $this->render('superadmin/instituciones/ver', [
            'inst'         => $inst,
            'suscActiva'   => $suscActiva,
            'historial'    => $historial,
            'pagos'        => $pagos,
            'logActividad' => $logActividad,
            'planes'       => $this->planModel->getActivos(),
            'csrf_token'   => $this->generateCsrfToken(),
        ], $inst['nombre']);
    }

    // --------------------------------------------------
    // NOTAS INTERNAS
    // --------------------------------------------------

    public function guardarNotas(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $notas = trim($_POST['notas'] ?? '');
        $this->instModel->update((int)$id, ['notas' => $notas ?: null]);

        $this->flash(FLASH_SUCCESS, '📝 Notas guardadas.');
        $this->redirect('/superadmin/instituciones/' . $id);
    }

    // --------------------------------------------------
    // CAMBIAR PLAN SIN COBRO
    // --------------------------------------------------

    public function cambiarPlan(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $inst   = $this->instModel->find((int)$id);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$inst || !$planId) {
            $this->flash(FLASH_ERROR, 'Datos inválidos.');
            $this->redirect('/superadmin/instituciones/' . $id);
            return;
        }

        $plan       = $this->planModel->find($planId);
        $suscActiva = $this->suscModel->getActivaPorInstitucion((int)$id);

        if (!$plan) {
            $this->flash(FLASH_ERROR, 'Plan no encontrado.');
            $this->redirect('/superadmin/instituciones/' . $id);
            return;
        }

        $db = Database::getInstance();
        try {
            if ($suscActiva) {
                // Actualizar suscripción existente
                $db->prepare(
                    "UPDATE suscripciones SET plan_id = :plan, monto = :monto WHERE id = :id"
                )->execute([
                    ':plan'  => $planId,
                    ':monto' => $plan['precio_mensual'],
                    ':id'    => $suscActiva['id'],
                ]);
            } else {
                // Crear suscripción nueva sin pago
                $this->suscModel->create([
                    'institucion_id'   => (int)$id,
                    'plan_id'          => $planId,
                    'tipo_facturacion' => 'mensual',
                    'monto'            => $plan['precio_mensual'],
                    'fecha_inicio'     => date('Y-m-d'),
                    'fecha_vencimiento'=> date('Y-m-d', strtotime('+1 month')),
                    'estado'           => SUSCRIPCION_ACTIVA,
                    'creado_por'       => $_SESSION['usuario_id'],
                ]);
            }

            // Registrar en log
            $db->prepare(
                "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
                 VALUES (:id, 'plan_cambiado', :motivo, :usr)"
            )->execute([
                ':id'     => (int)$id,
                ':motivo' => "Plan cambiado a {$plan['nombre']}" . ($motivo ? " — $motivo" : ''),
                ':usr'    => $_SESSION['usuario_id'],
            ]);

            ActivityLog::registrar('instituciones', 'cambiar_plan', "Plan cambiado a {$plan['nombre']}: {$inst['nombre']}", ['entidad_tipo'=>'institucion','entidad_id'=>(int)$id,'detalle'=>['plan'=>$plan['nombre']]]);

            // Notificar al colegio del cambio de plan
            if (!empty($inst['email'])) {
                try {
                    (new EmailService())->enviar(
                        $inst['email'],
                        '📋 Tu plan ha sido actualizado — ' . $inst['nombre'],
                        'personalizado',
                        [
                            'asunto'  => '📋 Plan actualizado',
                            'titulo'  => 'Tu plan ha cambiado',
                            'mensaje' => "Tu suscripción ha sido actualizada al plan <strong>{$plan['nombre']}</strong>." .
                                         ($motivo ? " Motivo: {$motivo}" : "") .
                                         "<br><br>Si tienes alguna pregunta, contáctanos.",
                            'inst'    => $inst,
                        ]
                    );
                } catch (Exception $ignored) {}
            }

            $this->flash(FLASH_SUCCESS, "✅ Plan cambiado a <strong>{$plan['nombre']}</strong>. 📧 Colegio notificado.");
        } catch (Exception $e) {
            $this->flash(FLASH_ERROR, 'Error: ' . $e->getMessage());
        }

        $this->redirect('/superadmin/instituciones/' . $id);
    }

    // --------------------------------------------------
    // ELIMINAR INSTITUCIÓN
    // --------------------------------------------------

    public function eliminarInstitucion(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $inst = $this->instModel->find((int)$id);
        if (!$inst) {
            $this->flash(FLASH_ERROR, 'Institución no encontrada.');
            $this->redirect('/superadmin/instituciones');
            return;
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // Eliminar en cascada (FK ON DELETE CASCADE cubre la mayoría)
            // Eliminar archivos físicos de uploads si existen
            $uploadsDir = __DIR__ . '/../../public/uploads/preinscripciones/' . $id . '/';
            if (is_dir($uploadsDir)) {
                foreach (glob($uploadsDir . '*') as $archivo) {
                    @unlink($archivo);
                }
                @rmdir($uploadsDir);
            }
            $estudiantesDir = __DIR__ . '/../../public/uploads/estudiantes/' . $id . '/';
            if (is_dir($estudiantesDir)) {
                array_map('unlink', glob($estudiantesDir . '*/*') ?: []);
                foreach (glob($estudiantesDir . '*') as $sub) {
                    @rmdir($sub);
                }
                @rmdir($estudiantesDir);
            }

            // Desvincular preregistro si existe (SET NULL para conservar historial)
            $db->prepare(
                "UPDATE preregistro_colegios SET institucion_id = NULL WHERE institucion_id = :id"
            )->execute([':id' => (int)$id]);

            // Eliminar usuarios del colegio explícitamente (por si el CASCADE no está activo en la BD)
            $db->prepare("DELETE FROM usuarios WHERE institucion_id = :id")
               ->execute([':id' => (int)$id]);

            // Eliminar registro — la CASCADE de BD cubre el resto (suscripciones, pagos, etc.)
            $db->prepare("DELETE FROM instituciones WHERE id = :id")
               ->execute([':id' => (int)$id]);

            $db->commit();

            ActivityLog::registrar('instituciones', 'eliminar', "Eliminada: {$inst['nombre']}", ['entidad_tipo'=>'institucion','entidad_id'=>(int)$id]);
            $this->flash(FLASH_SUCCESS, "🗑️ Institución <strong>{$inst['nombre']}</strong> eliminada permanentemente.");
        } catch (Exception $e) {
            $db->rollBack();
            $this->flash(FLASH_ERROR, 'Error al eliminar: ' . $e->getMessage());
        }

        $this->redirect('/superadmin/instituciones');
    }

    // --------------------------------------------------
    // SUSPENDER / REACTIVAR
    // --------------------------------------------------

    public function suspenderInstitucion(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $motivo = trim($_POST['motivo'] ?? 'Suspendida por el administrador.');
        $inst   = $this->instModel->find((int)$id); // Cargado ANTES para ActivityLog al final

        $this->instModel->update((int)$id, ['activo' => 0]);
        $this->suscModel->where(['institucion_id' => (int)$id, 'estado' => 'activa']);

        Database::getInstance()->prepare(
            "UPDATE suscripciones SET estado = 'suspendida'
             WHERE institucion_id = :id AND estado = 'activa'"
        )->execute([':id' => $id]);

        Database::getInstance()->prepare(
            "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
             VALUES (:id, 'suspendida', :motivo, :usr)"
        )->execute([':id' => $id, ':motivo' => $motivo, ':usr' => $_SESSION['usuario_id']]);

        // Correo de aviso de suspensión
        try {
            $inst = $this->instModel->find((int)$id);
            if ($inst && !empty($inst['email'])) {
                $emailSvc = new EmailService();
                $emailOk  = $emailSvc->avisoSuspension($inst, $motivo);
                (new NotificacionModel())->create([
                    'institucion_id' => (int)$id,
                    'tipo'           => 'suspension',
                    'destinatario'   => $inst['email'],
                    'asunto'         => '🔒 Acceso suspendido — ' . $inst['nombre'],
                    'estado'         => $emailOk ? 'enviado' : 'error',
                    'error_detalle'  => $emailOk ? null : $emailSvc->getLastError(),
                    'enviado_por'    => $_SESSION['usuario_id'],
                ]);
            }
        } catch (Exception $ignored) {}

        ActivityLog::registrar('instituciones', 'suspender', "Suspendida: {$inst['nombre']}", ['entidad_tipo'=>'institucion','entidad_id'=>(int)$id]);
        $this->flash(FLASH_WARNING, 'Institución suspendida.');
        $this->redirect('/superadmin/instituciones/' . $id);
    }

    public function reactivarInstitucion(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $inst = $this->instModel->find((int)$id); // Cargar ANTES del update para tener el nombre

        $this->instModel->update((int)$id, ['activo' => 1]);

        Database::getInstance()->prepare(
            "UPDATE suscripciones SET estado = 'activa'
             WHERE institucion_id = :id AND estado = 'suspendida'"
        )->execute([':id' => $id]);

        Database::getInstance()->prepare(
            "INSERT INTO log_estado_instituciones (institucion_id, accion, motivo, realizado_por)
             VALUES (:id, 'reactivada', 'Reactivada manualmente', :usr)"
        )->execute([':id' => $id, ':usr' => $_SESSION['usuario_id']]);

        // Email de notificación de reactivación
        if ($inst && !empty($inst['email'])) {
            try {
                $emailSvc = new EmailService();
                $emailSvc->enviar(
                    $inst['email'],
                    '✅ Tu acceso ha sido reactivado — ' . $inst['nombre'],
                    'personalizado',
                    [
                        'asunto'  => '✅ Acceso reactivado',
                        'titulo'  => '¡Bienvenido de vuelta!',
                        'mensaje' => "Tu acceso a <strong>EduSaaS RD</strong> ha sido reactivado. Ya puedes ingresar al sistema con normalidad.",
                        'inst'    => $inst,
                    ]
                );
                (new NotificacionModel())->create([
                    'institucion_id' => (int)$id, 'tipo' => 'personalizado',
                    'destinatario' => $inst['email'],
                    'asunto' => '✅ Acceso reactivado — ' . $inst['nombre'],
                    'estado' => 'enviado', 'enviado_por' => $_SESSION['usuario_id'],
                ]);
            } catch (Exception $ignored) {}
        }

        ActivityLog::registrar('instituciones', 'reactivar', "Reactivada: " . ($inst['nombre'] ?? "#{$id}"), ['entidad_tipo'=>'institucion','entidad_id'=>(int)$id]);
        $this->flash(FLASH_SUCCESS, '✅ Institución reactivada. 📧 Email de notificación enviado.');
        $this->redirect('/superadmin/instituciones/' . $id);
    }

    // --------------------------------------------------
    // RENOVAR / REGISTRAR PAGO
    // --------------------------------------------------

    public function registrarPago(string $instId): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $planId   = (int)($_POST['plan_id']         ?? 0);
        $tipo_fac = $_POST['tipo_facturacion']       ?? 'mensual';
        $metodo   = $_POST['metodo_pago']            ?? 'efectivo';
        $referencia = trim($_POST['referencia']      ?? '');

        $plan = $this->planModel->find($planId);
        if (!$plan) {
            $this->flash(FLASH_ERROR, 'Plan no válido.');
            $this->redirect('/superadmin/instituciones/' . $instId);
            return;
        }

        $monto     = $tipo_fac === 'anual' ? $plan['precio_anual'] : $plan['precio_mensual'];
        $hoy       = date('Y-m-d');
        $fechaFin  = $tipo_fac === 'anual'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Desactivar suscripción anterior si existe
            $db->prepare(
                "UPDATE suscripciones SET estado = 'cancelada'
                 WHERE institucion_id = :id AND estado IN ('activa','vencida','suspendida')"
            )->execute([':id' => $instId]);

            // Nueva suscripción
            $suscId = $this->suscModel->create([
                'institucion_id'    => (int)$instId,
                'plan_id'           => $planId,
                'tipo_facturacion'  => $tipo_fac,
                'monto'             => $monto,
                'fecha_inicio'      => $hoy,
                'fecha_vencimiento' => $fechaFin,
                'estado'            => SUSCRIPCION_ACTIVA,
                'creado_por'        => $_SESSION['usuario_id'],
            ]);

            // Registrar pago
            $this->pagoModel->create([
                'institucion_id' => (int)$instId,
                'suscripcion_id' => $suscId,
                'numero_factura' => $this->pagoModel->generarNumeroFactura(),
                'monto'          => $monto,
                'fecha_pago'     => $hoy,
                'metodo_pago'    => $metodo,
                'referencia'     => $referencia,
                'periodo_desde'  => $hoy,
                'periodo_hasta'  => $fechaFin,
                'estado'         => 'confirmado',
                'registrado_por' => $_SESSION['usuario_id'],
            ]);

            // Reactivar institución si estaba suspendida
            $this->instModel->update((int)$instId, ['activo' => 1]);

            $db->commit();

            // Enviar correo de confirmación automáticamente
            try {
                $emailService = new EmailService();
                $instData     = $this->instModel->find((int)$instId);
                $suscData     = $this->suscModel->find($suscId);
                $planData     = $this->planModel->find($planId);
                $pagoData     = $this->pagoModel->findBy(['suscripcion_id' => $suscId]);
                if ($instData && $pagoData && !empty($instData['email'])) {
                    $suscData['plan_nombre'] = $planData['nombre'] ?? '';
                    $emailOk = $emailService->confirmacionPago($instData, $pagoData, $suscData);
                    (new NotificacionModel())->create([
                        'institucion_id' => (int)$instId,
                        'tipo'           => 'plan_renovado',
                        'destinatario'   => $instData['email'],
                        'asunto'         => '✅ Confirmación de pago',
                        'estado'         => $emailOk ? 'enviado' : 'error',
                        'error_detalle'  => $emailOk ? null : $emailService->getLastError(),
                        'enviado_por'    => $_SESSION['usuario_id'],
                    ]);
                }
            } catch (Exception $ignored) {
                // El correo falló pero el pago ya fue registrado, no abortar
            }

            $instData2 = $this->instModel->find((int)$instId);
            ActivityLog::registrar('cobros', 'pago', "Pago registrado: " . ($instData2['nombre'] ?? "#{$instId}") . " — hasta " . date('d/m/Y', strtotime($fechaFin)), ['entidad_tipo'=>'institucion','entidad_id'=>(int)$instId]);
            $this->flash(FLASH_SUCCESS, '✅ Pago registrado y suscripción renovada hasta ' . date('d/m/Y', strtotime($fechaFin)) . '.');

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash(FLASH_ERROR, 'Error: ' . $e->getMessage());
        }

        $this->redirect('/superadmin/instituciones/' . $instId);
    }

    // --------------------------------------------------
    // PLANES
    // --------------------------------------------------

    public function planes(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->render('superadmin/planes/index', [
            'planes'     => $this->planModel->all('orden ASC'),
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Planes');
    }

    public function crearPlanForm(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->render('superadmin/planes/crear', [
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Nuevo Plan');
    }

    public function crearPlan(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $this->flash(FLASH_ERROR, 'El nombre del plan es obligatorio.');
            $this->redirect('/superadmin/planes/crear');
            return;
        }

        // Calcular orden: al final de los existentes
        $db    = Database::getInstance();
        $orden = (int)$db->query("SELECT COALESCE(MAX(orden),0)+1 FROM planes")->fetchColumn();

        $this->planModel->create([
            'nombre'              => $nombre,
            'descripcion'         => trim($_POST['descripcion'] ?? ''),
            'precio_mensual'      => (float)($_POST['precio_mensual'] ?? 0),
            'precio_anual'        => (float)($_POST['precio_anual']   ?? 0),
            'max_estudiantes'     => (int)($_POST['max_estudiantes']  ?? 0),
            'max_profesores'      => (int)($_POST['max_profesores']   ?? 0),
            'max_secciones'       => (int)($_POST['max_secciones']    ?? 0),
            'incluye_pagos'       => isset($_POST['incluye_pagos'])       ? 1 : 0,
            'incluye_reportes'    => isset($_POST['incluye_reportes'])    ? 1 : 0,
            'incluye_comunicados' => isset($_POST['incluye_comunicados']) ? 1 : 0,
            'incluye_api'         => isset($_POST['incluye_api'])         ? 1 : 0,
            'color'               => $_POST['color'] ?? '#1a56db',
            'icono'               => $_POST['icono'] ?? 'bi-box',
            'orden'               => $orden,
            'activo'              => 1,
        ]);

        ActivityLog::registrar('planes', 'crear', "Plan creado: {$nombre}", ['entidad_tipo'=>'plan']);
        $this->flash(FLASH_SUCCESS, "✅ Plan <strong>{$nombre}</strong> creado.");
        $this->redirect('/superadmin/planes');
    }

    public function togglePlan(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $plan = $this->planModel->find((int)$id);
        if (!$plan) { $this->error404(); return; }

        // No desactivar si hay colegios activos en este plan
        $db = Database::getInstance();
        $n  = (int)$db->prepare(
            "SELECT COUNT(*) FROM suscripciones WHERE plan_id = :id AND estado = 'activa'"
        )->execute([':id' => $id]) ?
            $db->query("SELECT COUNT(*) FROM suscripciones WHERE plan_id = {$id} AND estado = 'activa'")->fetchColumn() : 0;

        if ($n > 0 && $plan['activo']) {
            $this->flash(FLASH_ERROR,
                "No puedes desactivar este plan — tiene <strong>{$n} colegio(s) activo(s)</strong>. " .
                "Migra esos colegios a otro plan primero."
            );
            $this->redirect('/superadmin/planes');
            return;
        }

        $nuevo = $plan['activo'] ? 0 : 1;
        $this->planModel->update((int)$id, ['activo' => $nuevo]);
        $msg = $nuevo ? "✅ Plan <strong>{$plan['nombre']}</strong> activado." 
                      : "Plan <strong>{$plan['nombre']}</strong> desactivado.";
        $this->flash($nuevo ? FLASH_SUCCESS : FLASH_WARNING, $msg);
        $this->redirect('/superadmin/planes');
    }

    public function editarPlanForm(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $plan = $this->planModel->find((int)$id);
        if (!$plan) { $this->error404(); return; }

        $this->render('superadmin/planes/editar', [
            'plan'       => $plan,
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Editar Plan: ' . $plan['nombre']);
    }

    public function editarPlan(string $id): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $this->flash(FLASH_ERROR, 'El nombre es obligatorio.');
            $this->redirect('/superadmin/planes/' . $id . '/editar');
            return;
        }

        $this->planModel->update((int)$id, [
            'nombre'              => $nombre,
            'descripcion'         => trim($_POST['descripcion'] ?? ''),
            'precio_mensual'      => (float)($_POST['precio_mensual'] ?? 0),
            'precio_anual'        => (float)($_POST['precio_anual']   ?? 0),
            'max_estudiantes'     => (int)($_POST['max_estudiantes']  ?? 0),
            'max_profesores'      => (int)($_POST['max_profesores']   ?? 0),
            'max_secciones'       => (int)($_POST['max_secciones']    ?? 0),
            'incluye_pagos'       => isset($_POST['incluye_pagos'])       ? 1 : 0,
            'incluye_reportes'    => isset($_POST['incluye_reportes'])    ? 1 : 0,
            'incluye_comunicados' => isset($_POST['incluye_comunicados']) ? 1 : 0,
            'incluye_api'         => isset($_POST['incluye_api'])         ? 1 : 0,
            'color'               => $_POST['color'] ?? '#1a56db',
            'icono'               => $_POST['icono'] ?? 'bi-box',
        ]);

        // Invalida caché de plan en sesiones activas
        // (se recargará en el próximo request del colegio)

        $this->flash(FLASH_SUCCESS, '✅ Plan actualizado.');
        $this->redirect('/superadmin/planes');
    }

    // --------------------------------------------------
    // LOG DE EMAILS ENVIADOS
    // --------------------------------------------------

    public function emailsLog(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $filtros = [
            'tipo'    => $_GET['tipo']    ?? '',
            'estado'  => $_GET['estado']  ?? '',
            'buscar'  => $_GET['buscar']  ?? '',
            'desde'   => $_GET['desde']   ?? '',
        ];

        $notifModel = new NotificacionModel();
        $historial  = $notifModel->getFiltrado($filtros, 200);
        $stats      = $notifModel->getStats();

        $this->render('superadmin/emails/index', [
            'historial' => $historial,
            'stats'     => $stats,
            'filtros'   => $filtros,
        ], 'Log de Emails');
    }

    // --------------------------------------------------
    // LOG DE ACTIVIDAD GLOBAL
    // --------------------------------------------------

    public function logActividad(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $filtros = [
            'modulo'  => $_GET['modulo']  ?? '',
            'buscar'  => $_GET['buscar']  ?? '',
            'desde'   => $_GET['desde']   ?? '',
            'limite'  => (int)($_GET['limite'] ?? 50),
        ];

        $registros  = ActivityLog::getRecientes($filtros['limite'], $filtros);
        $contadores = ActivityLog::getContadoresPorModulo();

        $this->render('superadmin/log/index', [
            'registros'  => $registros,
            'contadores' => $contadores,
            'filtros'    => $filtros,
        ], 'Log de Actividad');
    }

    // --------------------------------------------------
    // DESBLOQUEAR USUARIO (desde panel de salud)
    // --------------------------------------------------

    public function desbloquearUsuario(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $username = trim($_POST['username'] ?? '');
        if (!$username) { $this->redirect('/superadmin/salud'); return; }

        $db = Database::getInstance();
        $db->prepare(
            "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE username = :u"
        )->execute([':u' => $username]);

        ActivityLog::registrar('usuarios', 'desbloquear',
            "Usuario desbloqueado manualmente: {$username}");

        $this->flash(FLASH_SUCCESS, "✅ Usuario <strong>{$username}</strong> desbloqueado.");
        $this->redirect('/superadmin/salud');
    }

    // --------------------------------------------------
    // EXPORT DE INSTITUCIONES (CSV)
    // --------------------------------------------------

    public function exportarInstituciones(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT i.nombre, i.tipo, i.email, i.telefono, i.municipio, i.provincia,
                    i.subdomain, i.activo,
                    p.nombre AS plan_nombre,
                    s.tipo_facturacion, s.monto, s.fecha_vencimiento, s.estado AS susc_estado,
                    s.es_trial,
                    (SELECT COUNT(*) FROM estudiantes e WHERE e.institucion_id = i.id) AS total_estudiantes,
                    i.created_at
             FROM instituciones i
             LEFT JOIN suscripciones s ON s.institucion_id = i.id
               AND s.estado IN ('activa','suspendida')
             LEFT JOIN planes p ON s.plan_id = p.id
             ORDER BY i.nombre ASC"
        );
        $rows = $stmt->fetchAll();

        $moneda = ConfigModel::get('factura_moneda', 'RD$');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="instituciones_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM UTF-8

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Nombre', 'Tipo', 'Email', 'Teléfono', 'Municipio', 'Provincia',
            'Subdominio', 'Estado', 'Plan', 'Facturación', 'Monto ('.$moneda.')',
            'Vencimiento', 'Estado Suscripción', 'Trial', 'Estudiantes', 'Registro'
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['nombre'],
                ucfirst($r['tipo']),
                $r['email'],
                $r['telefono'] ?? '',
                $r['municipio'] ?? '',
                $r['provincia'] ?? '',
                $r['subdomain'],
                $r['activo'] ? 'Activo' : 'Suspendido',
                $r['plan_nombre'] ?? 'Sin plan',
                ucfirst($r['tipo_facturacion'] ?? ''),
                number_format((float)($r['monto'] ?? 0), 2, '.', ''),
                $r['fecha_vencimiento'] ?? '',
                ucfirst($r['susc_estado'] ?? ''),
                $r['es_trial'] ? 'Sí' : 'No',
                (int)$r['total_estudiantes'],
                date('d/m/Y', strtotime($r['created_at'])),
            ], ';');
        }

        fclose($out);
        ActivityLog::registrar('instituciones', 'exportar', 'Export CSV de instituciones ('  . count($rows) . ' registros)');
        exit;
    }

    // --------------------------------------------------
    // DASHBOARD DE SALUD DEL SISTEMA
    // --------------------------------------------------

    public function saludSistema(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $db = Database::getInstance();

        // ── Base de datos ──────────────────────────────────────────────────
        $dbSize = $db->query(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb
             FROM information_schema.tables
             WHERE table_schema = DATABASE()"
        )->fetchColumn();

        $tablas = $db->query(
            "SELECT table_name AS nombre,
                    ROUND((data_length + index_length) / 1024, 1) AS kb,
                    table_rows AS filas
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             ORDER BY (data_length + index_length) DESC"
        )->fetchAll();

        // ── Usuarios bloqueados ahora ──────────────────────────────────────
        try {
            $bloqueados = $db->query(
                "SELECT username, bloqueado_hasta
                 FROM usuarios
                 WHERE bloqueado_hasta > NOW()
                 ORDER BY bloqueado_hasta DESC"
            )->fetchAll();
        } catch (Exception $e) {
            $bloqueados = [];
        }

        // ── Emails fallidos últimos 7 días ─────────────────────────────────
        try {
            $emailsError = (int)$db->query(
                "SELECT COUNT(*) FROM notificaciones_email
                 WHERE estado = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )->fetchColumn();
        } catch (Exception $e) { $emailsError = 0; }

        // ── Suscripciones vencidas sin actualizar ─────────────────────────
        try {
            $suscVencidas = (int)$db->query(
                "SELECT COUNT(*) FROM suscripciones
                 WHERE estado = 'activa' AND fecha_vencimiento < CURDATE()"
            )->fetchColumn();
        } catch (Exception $e) { $suscVencidas = 0; }

        // ── Últimas ejecuciones del cron ───────────────────────────────────
        try {
            $cronLogs = $db->query(
                "SELECT * FROM cron_log ORDER BY ejecutado_en DESC LIMIT 10"
            )->fetchAll();
        } catch (Exception $e) { $cronLogs = []; }

        // ── Versiones ─────────────────────────────────────────────────────
        $phpVersion  = PHP_VERSION;
        $mysqlVersion = $db->query("SELECT VERSION()")->fetchColumn();

        // ── Preregistros pendientes ────────────────────────────────────────
        try {
            $preregistrosPendientes = (int)$db->query(
                "SELECT COUNT(*) FROM preregistro_colegios WHERE estado = 'pendiente'"
            )->fetchColumn();
        } catch (Exception $e) { $preregistrosPendientes = 0; }

        // ── Actividad reciente (log) ───────────────────────────────────────
        $actividadReciente = ActivityLog::getRecientes(5);

        $this->render('superadmin/salud/index', [
            'csrf_token'            => $this->generateCsrfToken(),
            'dbSize'                => $dbSize,
            'tablas'               => $tablas,
            'bloqueados'           => $bloqueados,
            'emailsError'          => $emailsError,
            'suscVencidas'         => $suscVencidas,
            'cronLogs'             => $cronLogs,
            'phpVersion'           => $phpVersion,
            'mysqlVersion'         => $mysqlVersion,
            'preregistrosPendientes'=> $preregistrosPendientes,
            'actividadReciente'    => $actividadReciente,
        ], 'Salud del Sistema');
    }

    // --------------------------------------------------
    // RECORDATORIO AUTOMÁTICO DE VENCIMIENTO (Cron-safe)
    // --------------------------------------------------

    /**
     * GET /superadmin/cron/avisos-vencimiento
     * Puede llamarse desde un cron job con token de seguridad:
     *   curl /superadmin/cron/avisos-vencimiento?token=CRON_SECRET
     * O manualmente desde el panel de salud.
     */
    public function cronAvisosVencimiento(): void
    {
        // Verificar que sea superadmin O que traiga el token de cron
        $token       = $_GET['token'] ?? '';
        $cronSecret  = defined('CRON_SECRET') ? CRON_SECRET : null;
        $esCron      = $cronSecret && hash_equals($cronSecret, $token);
        $esManual    = isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === ROL_SUPER_ADMIN;

        if (!$esCron && !$esManual) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $emailService = new EmailService();
        $notifModel   = new NotificacionModel();
        $db           = Database::getInstance();

        $enviados = 0;
        $errores  = 0;
        $detalle  = [];

        foreach ([7, 3, 0] as $dias) {
            $instituciones = $this->suscModel->getPorVencer($dias);

            foreach ($instituciones as $item) {
                if ((int)$item['dias_restantes'] !== $dias) continue;
                if ($item['es_trial'] ?? false) continue; // Trial tiene su propio aviso

                $tipo = match($dias) {
                    7 => 'vencimiento_7dias',
                    3 => 'vencimiento_3dias',
                    0 => 'vencimiento_hoy',
                };

                if ($notifModel->yaEnviadaHoy((int)$item['institucion_id'], $tipo)) continue;

                $inst = $this->instModel->find((int)$item['institucion_id']);
                if (!$inst || empty($inst['email'])) continue;

                $ok = $emailService->avisoVencimiento($inst, $item, $dias);

                $notifModel->create([
                    'institucion_id' => $inst['id'],
                    'tipo'           => $tipo,
                    'destinatario'   => $inst['email'],
                    'asunto'         => "Aviso de vencimiento ({$dias} días)",
                    'estado'         => $ok ? 'enviado' : 'error',
                    'enviado_por'    => $esManual ? ($_SESSION['usuario_id'] ?? null) : null,
                ]);

                if ($ok) { $enviados++; $detalle[] = $inst['nombre'] . " ({$dias}d)"; }
                else     { $errores++;  }
            }
        }

        // Log en cron_log
        try {
            $resultado = ($enviados === 0 && $errores === 0) ? 'sin_trabajo' : ($errores > 0 ? 'error' : 'ok');
            $db->prepare(
                "INSERT INTO cron_log (tarea, resultado, detalle, enviados, errores)
                 VALUES ('avisos_vencimiento', :r, :d, :e, :err)"
            )->execute([
                ':r'   => $resultado,
                ':d'   => $detalle ? implode(', ', $detalle) : null,
                ':e'   => $enviados,
                ':err' => $errores,
            ]);
        } catch (Exception $ignored) {}

        // Si vino de cron (sin sesión) responder JSON
        if ($esCron && !$esManual) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'       => true,
                'enviados' => $enviados,
                'errores'  => $errores,
                'detalle'  => $detalle,
            ]);
            exit;
        }

        // Si vino manual, flash y redirigir
        $msg = "✅ Avisos enviados: {$enviados}.";
        if ($errores) $msg .= " ⚠️ Errores: {$errores}.";
        if ($enviados === 0 && $errores === 0) $msg = "ℹ️ No hay instituciones que requieran aviso hoy.";

        ActivityLog::registrar('notificaciones', 'cron_avisos', "Avisos vencimiento: {$enviados} enviados, {$errores} errores");
        $this->flash(FLASH_SUCCESS, $msg);
        $this->redirect('/superadmin/salud');
    }

    // --------------------------------------------------
    // CONFIGURACIÓN GENERAL DEL SISTEMA
    // --------------------------------------------------

    public function configuracion(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->render('superadmin/configuracion/index', [
            'grupos'     => ConfigModel::getTodo(),
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Configuración del Sistema');
    }

    public function guardarConfiguracion(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);
        $this->verifyCsrfToken();

        $datos = [];
        foreach ($_POST as $k => $v) {
            if ($k === '_csrf_token') continue;
            $datos[$k] = is_string($v) ? trim($v) : $v;
        }

        // Checkboxes no enviados = false
        $booleans = ['sistema_modo_mantenimiento', 'sistema_registro_publico'];
        foreach ($booleans as $b) {
            $datos[$b] = isset($_POST[$b]) ? '1' : '0';
        }

        // Manejar subida de logo
        if (!empty($_FILES['marca_logo_archivo']['tmp_name'])) {
            $file = $_FILES['marca_logo_archivo'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','svg','webp']) && $file['size'] < 2 * 1024 * 1024) {
                $imgDir = __DIR__ . '/../../public/assets/img/';
                if (!is_dir($imgDir)) {
                    mkdir($imgDir, 0755, true);
                }
                // Eliminar logo anterior si existe
                foreach (glob($imgDir . 'logo_sistema.*') as $old_logo) {
                    @unlink($old_logo);
                }
                $dest = $imgDir . 'logo_sistema.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $datos['marca_logo_url'] = '/assets/img/logo_sistema.' . $ext;
                } else {
                    $this->flash(FLASH_ERROR, '⚠️ No se pudo guardar el logo. Verifica permisos de escritura en public/assets/img/');
                    $this->redirect('/superadmin/configuracion');
                    return;
                }
            } else {
                $this->flash(FLASH_ERROR, '⚠️ Logo inválido. Usa PNG, JPG, SVG o WebP (máx. 2MB).');
                $this->redirect('/superadmin/configuracion');
                return;
            }
        }
        unset($datos['marca_logo_archivo']);

        ConfigModel::setMultiple($datos);
        ConfigModel::limpiarCache();

        ActivityLog::registrar('configuracion', 'editar', 'Configuración del sistema actualizada');
        $this->flash(FLASH_SUCCESS, '✅ Configuración guardada correctamente.');
        $this->redirect('/superadmin/configuracion');
    }

    // --------------------------------------------------
    // REPORTE DE INGRESOS
    // --------------------------------------------------

    public function ingresos(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $anio = (int)($_GET['anio'] ?? date('Y'));
        $mes  = (int)($_GET['mes']  ?? 0);

        $pagos       = $this->pagoModel->getAllConDetalle($anio, $mes);
        $ingresosMes = $this->pagoModel->getIngresosPorMes($anio);

        $this->render('superadmin/pagos/ingresos', [
            'pagos'       => $pagos,
            'ingresosMes' => $ingresosMes,
            'anioActual'  => $anio,
            'mesActual'   => $mes,
        ], 'Ingresos');
    }

    /**
     * GET /superadmin/ingresos/exportar?formato=csv|pdf&anio=X&mes=Y
     */
    public function exportarIngresos(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        $formato = $_GET['formato'] ?? 'csv';
        $anio    = (int)($_GET['anio'] ?? date('Y'));
        $mes     = (int)($_GET['mes']  ?? 0);
        $pagos   = $this->pagoModel->getAllConDetalle($anio, $mes);

        $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                         'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $periodoLabel = $mes > 0
            ? $mesesNombres[$mes] . ' ' . $anio
            : 'Año ' . $anio;

        $totalGeneral = array_sum(array_column($pagos, 'monto'));

        if ($formato === 'pdf') {
            // Renderizar sin layout — página standalone para imprimir
            extract([
                'pagos'        => $pagos,
                'periodoLabel' => $periodoLabel,
                'anioActual'   => $anio,
                'mesActual'    => $mes,
                'totalGeneral' => $totalGeneral,
            ]);
            require_once __DIR__ . '/../../views/superadmin/pagos/ingresos_pdf.php';
            exit;
        }

        // CSV — abre directo en Excel
        $nombreArchivo = 'ingresos_edusaas_' . str_replace(' ', '_', strtolower($periodoLabel)) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para que Excel abra con UTF-8 correctamente
        echo "\xEF\xBB\xBF"; // BOM UTF-8 correcto

        $out = fopen('php://output', 'w');

        // Cabecera del reporte
        fputcsv($out, ['Reporte de Ingresos — EduSaaS RD'], ';');
        fputcsv($out, ['Período:', $periodoLabel], ';');
        fputcsv($out, ['Generado:', date('d/m/Y H:i')], ';');
        fputcsv($out, [], ';');

        // Columnas
        fputcsv($out, [
            'Factura', 'Institución', 'Plan',
            'Método de pago', 'Referencia',
            'Período desde', 'Período hasta',
            'Fecha de pago', 'Monto (RD$)'
        ], ';');

        // Datos
        foreach ($pagos as $p) {
            fputcsv($out, [
                $p['numero_factura'],
                $p['institucion_nombre'],
                $p['plan_nombre'],
                ucfirst($p['metodo_pago']),
                $p['referencia'] ?? '',
                date('d/m/Y', strtotime($p['periodo_desde'])),
                date('d/m/Y', strtotime($p['periodo_hasta'])),
                date('d/m/Y', strtotime($p['fecha_pago'])),
                number_format($p['monto'], 2, '.', ''),
            ], ';');
        }

        // Total
        fputcsv($out, [], ';');
        fputcsv($out, ['', '', '', '', '', '', '', 'TOTAL:', number_format($totalGeneral, 2, '.', '')], ';');

        fclose($out);
        exit;
    }
}