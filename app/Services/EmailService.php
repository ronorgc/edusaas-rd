<?php
// =====================================================
// EduSaaS RD - EmailService v2
//
// Soporta tres drivers:
//   smtp → SmtpClient (Gmail, Outlook, Brevo…)
//   mail → mail() nativo PHP (solo XAMPP con Mercury)
//   log  → no envía, escribe en storage/logs/mail.log
//
// Las plantillas HTML viven en views/emails/*.php
// =====================================================

class EmailService
{
    private string $fromName;
    private string $fromEmail;
    private string $replyTo;
    private string $driver;
    private array  $smtpCfg;
    private string $tplPath;

    /** Último error detallado (para mostrarlo en el log) */
    private string $lastError = '';

    public function __construct()
    {
        $app  = require __DIR__ . '/../../config/app.php';
        $mail = file_exists(__DIR__ . '/../../config/mail.php')
            ? require __DIR__ . '/../../config/mail.php'
            : [];

        $this->fromName  = $mail['from_name']  ?? ($app['name'] ?? 'EduSaaS RD');
        $this->fromEmail = $mail['from_email'] ?? 'noreply@edusaas.do';
        $this->replyTo   = $mail['reply_to']   ?? 'soporte@edusaas.do';
        $this->driver    = $mail['driver']     ?? 'mail';
        $this->smtpCfg   = $mail['smtp']       ?? [];
        $this->tplPath   = __DIR__ . '/../../views/emails/';
    }

    // ──────────────────────────────────────────────────
    // MÉTODO PRINCIPAL
    // ──────────────────────────────────────────────────

    /**
     * Envía un email usando el driver configurado.
     * Retorna true si se envió, false si falló.
     * El error detallado queda en getLastError().
     */
    public function enviar(string $para, string $asunto, string $plantilla, array $vars = []): bool
    {
        $this->lastError = '';

        try {
            $contenido = $this->renderPlantilla($plantilla, $vars);
            $html      = $this->renderLayout($asunto, $contenido);

            return match ($this->driver) {
                'smtp' => $this->enviarSmtp($para, $asunto, $html),
                'log'  => $this->enviarLog($para, $asunto, $html),
                default => $this->enviarNativo($para, $asunto, $html),
            };
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->escribirLog("[ERROR] Para:{$para} | {$e->getMessage()}");
            return false;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    // ──────────────────────────────────────────────────
    // DRIVERS
    // ──────────────────────────────────────────────────

    private function enviarSmtp(string $para, string $asunto, string $html): bool
    {
        $smtp = new SmtpClient($this->smtpCfg);
        $smtp->send($this->fromEmail, $this->fromName, $para, $asunto, $html);
        // Si llegamos aquí sin excepción, se envió
        $this->escribirLog("[OK-SMTP] Para:{$para} | {$asunto}");
        return true;
    }

    private function enviarNativo(string $para, string $asunto, string $html): bool
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->replyTo}\r\n";
        $headers .= "X-Mailer: EduSaaS-RD/2.0\r\n";

        $ok = mail(
            $para,
            '=?UTF-8?B?' . base64_encode($asunto) . '?=',
            $html,
            $headers
        );

        if ($ok) {
            $this->escribirLog("[OK-MAIL] Para:{$para} | {$asunto}");
        } else {
            $this->lastError = 'mail() retornó false — revisa la configuración de sendmail en XAMPP.';
            $this->escribirLog("[ERROR-MAIL] Para:{$para} | " . $this->lastError);
        }

        return $ok;
    }

    private function enviarLog(string $para, string $asunto, string $html): bool
    {
        $this->escribirLog("[LOG] Para:{$para} | {$asunto} | " . strlen($html) . " bytes");
        return true; // Simula éxito en modo log
    }

    // ──────────────────────────────────────────────────
    // MÉTODOS SEMÁNTICOS
    // ──────────────────────────────────────────────────

    public function bienvenida(array $inst, string $username, string $password): bool
    {
        return $this->enviar(
            $inst['email'],
            '¡Bienvenido a EduSaaS RD! — Tus credenciales de acceso',
            'bienvenida',
            compact('inst', 'username', 'password')
        );
    }

    public function avisoVencimiento(array $inst, array $susc, int $diasRestantes): bool
    {
        $color = $diasRestantes <= 3 ? '#ef4444' : '#f59e0b';
        $emoji = $diasRestantes <= 3 ? '🚨' : '⚠️';

        $msgDias = match(true) {
            $diasRestantes === 0 => '<strong>¡vence HOY!</strong>',
            $diasRestantes === 1 => 'vence <strong>mañana</strong>',
            default              => "vence en <strong>{$diasRestantes} días</strong>",
        };

        $fechaFmt     = date('d/m/Y', strtotime($susc['fecha_vencimiento']));
        $asuntoLimpio = strip_tags("{$emoji} EduSaaS RD — Tu suscripción {$msgDias} ({$fechaFmt})");

        return $this->enviar(
            $inst['email'],
            $asuntoLimpio,
            'aviso_vencimiento',
            compact('inst', 'susc', 'diasRestantes', 'color', 'emoji', 'msgDias', 'fechaFmt')
        );
    }

    public function confirmacionPago(array $inst, array $pago, array $susc): bool
    {
        $fechaPago  = date('d/m/Y', strtotime($pago['fecha_pago']));
        $fechaHasta = date('d/m/Y', strtotime($pago['periodo_hasta']));

        return $this->enviar(
            $inst['email'],
            "✅ EduSaaS RD — Pago confirmado ({$pago['numero_factura']})",
            'confirmacion_pago',
            compact('inst', 'pago', 'susc', 'fechaPago', 'fechaHasta')
        );
    }

    public function avisoSuspension(array $inst, string $motivo): bool
    {
        return $this->enviar(
            $inst['email'],
            '🔒 EduSaaS RD — Acceso suspendido',
            'suspension',
            compact('inst', 'motivo')
        );
    }

    public function personalizado(array $inst, string $asunto, string $mensaje): bool
    {
        return $this->enviar(
            $inst['email'],
            $asunto,
            'personalizado',
            compact('inst', 'asunto', 'mensaje')
        );
    }

    /**
     * Notifica al superadmin que llegó una nueva solicitud de registro.
     */
    public function notificarNuevoPreregistro(string $nombreColegio, string $emailColegio): bool
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->query(
                "SELECT email FROM usuarios WHERE rol_id = 1 AND activo = 1 LIMIT 5"
            );
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($admins)) return true; // Sin superadmins activos, no pasa nada

            $asunto  = "Nueva solicitud de registro: {$nombreColegio}";
            $mensaje = "<p>Se recibió una nueva solicitud de acceso al sistema.</p>"
                     . "<p><strong>Colegio:</strong> {$nombreColegio}<br>"
                     . "<strong>Email:</strong> {$emailColegio}</p>"
                     . "<p>Ingresa al panel de SuperAdmin para revisarla.</p>";

            foreach ($admins as $emailAdmin) {
                $this->enviar($emailAdmin, $asunto, 'personalizado', [
                    'asunto'  => $asunto,
                    'titulo'  => 'Nueva solicitud de registro',
                    'mensaje' => $mensaje,
                    'inst'    => ['nombre' => 'EduSaaS RD'],
                ]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Envía credenciales al colegio aprobado desde preregistro.
     */
    public function bienvenidaPreregistro(array $solicitud, string $username, string $password, string $planNombre): bool
    {
        try {
            $asunto  = "¡Tu cuenta en EduSaaS RD ha sido activada!";
            $mensaje = "<p>Hola <strong>" . htmlspecialchars($solicitud['nombre']) . "</strong>,</p>"
                     . "<p>Tu solicitud de acceso ha sido <strong>aprobada</strong>. "
                     . "Ya puedes ingresar al sistema con las siguientes credenciales:</p>"
                     . "<table style='border-collapse:collapse;margin:1rem 0'>"
                     . "<tr><td style='padding:6px 12px;font-weight:600'>Usuario:</td>"
                     . "<td style='padding:6px 12px;font-family:monospace'>" . htmlspecialchars($username) . "</td></tr>"
                     . "<tr><td style='padding:6px 12px;font-weight:600'>Contraseña:</td>"
                     . "<td style='padding:6px 12px;font-family:monospace'>" . htmlspecialchars($password) . "</td></tr>"
                     . "<tr><td style='padding:6px 12px;font-weight:600'>Plan:</td>"
                     . "<td style='padding:6px 12px'>" . htmlspecialchars($planNombre) . "</td></tr>"
                     . "</table>"
                     . "<p>Te recomendamos cambiar tu contraseña al iniciar sesión por primera vez.</p>";

            return $this->enviar($solicitud['email'], $asunto, 'personalizado', [
                'asunto'  => $asunto,
                'titulo'  => '¡Bienvenido a EduSaaS RD!',
                'mensaje' => $mensaje,
                'inst'    => ['nombre' => $solicitud['nombre']],
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function preinscripcionAprobada(array $sol, array $inst, string $codigo): bool
    {
        return $this->enviar(
            $sol['tutor_email'],
            "✅ {$inst['nombre']} — Solicitud de {$sol['nombres']} {$sol['apellidos']} aprobada",
            'preinscripcion_aprobada',
            compact('sol', 'inst', 'codigo')
        );
    }

    // ──────────────────────────────────────────────────
    // MOTOR DE PLANTILLAS
    // ──────────────────────────────────────────────────

    private function renderPlantilla(string $nombre, array $vars): string
    {
        $archivo = $this->tplPath . $nombre . '.php';

        if (!file_exists($archivo)) {
            throw new RuntimeException("Plantilla de email no encontrada: {$nombre}.php");
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        include $archivo;
        return ob_get_clean();
    }

    private function renderLayout(string $asunto, string $contenido): string
    {
        $archivo = $this->tplPath . 'layout.php';
        ob_start();
        include $archivo;
        return ob_get_clean();
    }

    // ──────────────────────────────────────────────────
    // LOG INTERNO
    // ──────────────────────────────────────────────────

    private function escribirLog(string $msg): void
    {
        $dir = __DIR__ . '/../../storage/logs/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        @file_put_contents($dir . 'mail.log', $linea, FILE_APPEND | LOCK_EX);
    }
}