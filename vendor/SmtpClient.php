<?php
// =====================================================
// EduSaaS RD - SmtpClient
//
// Cliente SMTP puro en PHP — sin dependencias externas.
// Soporta: STARTTLS (puerto 587), SSL (puerto 465) y
// texto plano (puerto 25).
//
// Compatible con: Gmail, Outlook, Brevo, Mailgun, etc.
// =====================================================

class SmtpClient
{
    private string $host;
    private int    $port;
    private string $encryption; // 'tls' | 'ssl' | ''
    private string $username;
    private string $password;
    private int    $timeout;

    /** @var resource|false */
    private $socket = false;

    private array  $log = [];

    public function __construct(array $cfg)
    {
        $this->host       = $cfg['host']       ?? 'smtp.gmail.com';
        $this->port       = (int)($cfg['port'] ?? 587);
        $this->encryption = strtolower($cfg['encryption'] ?? 'tls');
        $this->username   = $cfg['username']   ?? '';
        $this->password   = $cfg['password']   ?? '';
        $this->timeout    = (int)($cfg['timeout'] ?? 15);
    }

    // ──────────────────────────────────────────────────
    // API PÚBLICA
    // ──────────────────────────────────────────────────

    /**
     * Envía un email HTML.
     *
     * @throws RuntimeException con mensaje detallado si falla cualquier paso
     */
    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $htmlBody
    ): void {
        $this->conectar();
        $this->autenticar();
        $this->enviarMail($fromEmail, $fromName, $toEmail, $subject, $htmlBody);
        $this->cerrar();
    }

    /** Retorna el log de la conversación SMTP (útil para debug) */
    public function getLog(): array
    {
        return $this->log;
    }

    // ──────────────────────────────────────────────────
    // CONEXIÓN
    // ──────────────────────────────────────────────────

    private function conectar(): void
    {
        $errNo  = 0;
        $errStr = '';

        if ($this->encryption === 'ssl') {
            // SSL directo (puerto 465)
            $uri = "ssl://{$this->host}:{$this->port}";
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ]);
            $this->socket = stream_socket_client(
                $uri, $errNo, $errStr, $this->timeout, STREAM_CLIENT_CONNECT, $ctx
            );
        } else {
            // Texto plano primero (para STARTTLS en puerto 587)
            $this->socket = fsockopen($this->host, $this->port, $errNo, $errStr, $this->timeout);
        }

        if (!$this->socket) {
            throw new RuntimeException(
                "SMTP: No se pudo conectar a {$this->host}:{$this->port} — {$errStr} (#{$errNo})"
            );
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Leer saludo del servidor
        $this->leer(220);

        // EHLO
        $this->escribir("EHLO " . gethostname());
        $this->leer(250);

        // STARTTLS si aplica
        if ($this->encryption === 'tls') {
            $this->escribir("STARTTLS");
            $this->leer(220);

            $ok = stream_socket_enable_crypto(
                $this->socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );

            if (!$ok) {
                throw new RuntimeException("SMTP: Falló el handshake TLS con {$this->host}");
            }

            // Re-EHLO después de TLS
            $this->escribir("EHLO " . gethostname());
            $this->leer(250);
        }
    }

    // ──────────────────────────────────────────────────
    // AUTENTICACIÓN
    // ──────────────────────────────────────────────────

    private function autenticar(): void
    {
        if (empty($this->username)) return;

        $this->escribir("AUTH LOGIN");
        $this->leer(334);

        $this->escribir(base64_encode($this->username));
        $this->leer(334);

        $this->escribir(base64_encode($this->password));
        $this->leer(235);
    }

    // ──────────────────────────────────────────────────
    // ENVÍO
    // ──────────────────────────────────────────────────

    private function enviarMail(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $htmlBody
    ): void {
        $this->escribir("MAIL FROM: <{$fromEmail}>");
        $this->leer(250);

        $this->escribir("RCPT TO: <{$toEmail}>");
        $this->leer(250);

        $this->escribir("DATA");
        $this->leer(354);

        $boundary = 'edusaas_' . md5(uniqid('', true));
        $from     = $this->encodeHeader($fromName) . " <{$fromEmail}>";
        $subj     = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $date     = date('r');
        $msgId    = '<' . time() . '.' . rand() . '@edusaas.do>';

        $mensaje  = "Date: {$date}\r\n";
        $mensaje .= "From: {$from}\r\n";
        $mensaje .= "To: <{$toEmail}>\r\n";
        $mensaje .= "Subject: {$subj}\r\n";
        $mensaje .= "Message-ID: {$msgId}\r\n";
        $mensaje .= "MIME-Version: 1.0\r\n";
        $mensaje .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $mensaje .= "X-Mailer: EduSaaS-RD/2.0\r\n";
        $mensaje .= "\r\n";

        // Parte texto plano (fallback)
        $textPlano = strip_tags(str_replace(['<br>','<br/>','</p>','</div>'], "\n", $htmlBody));
        $mensaje .= "--{$boundary}\r\n";
        $mensaje .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mensaje .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mensaje .= chunk_split(base64_encode($textPlano)) . "\r\n";

        // Parte HTML
        $mensaje .= "--{$boundary}\r\n";
        $mensaje .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mensaje .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mensaje .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $mensaje .= "--{$boundary}--\r\n";

        // Punto final de DATA (escapar líneas que empiezan con ".")
        $mensaje .= "\r\n.\r\n";

        fwrite($this->socket, $mensaje);
        $this->log[] = ">> [mensaje completo omitido del log]";

        $this->leer(250);
    }

    private function cerrar(): void
    {
        if ($this->socket) {
            $this->escribir("QUIT");
            fclose($this->socket);
            $this->socket = false;
        }
    }

    // ──────────────────────────────────────────────────
    // COMUNICACIÓN BAJO NIVEL
    // ──────────────────────────────────────────────────

    private function escribir(string $cmd): void
    {
        // No loguear la contraseña
        $logCmd = str_starts_with($cmd, 'AUTH') ? $cmd : (
            strlen($cmd) === 24 && base64_decode($cmd) !== false
                ? '[credencial base64]'
                : $cmd
        );
        $this->log[] = ">> {$logCmd}";
        fwrite($this->socket, $cmd . "\r\n");
    }

    /**
     * Lee la respuesta del servidor y valida el código esperado.
     * @throws RuntimeException si el código no coincide.
     */
    private function leer(int $codigoEsperado): string
    {
        $respuesta = '';
        while ($linea = fgets($this->socket, 512)) {
            $respuesta .= $linea;
            // Las líneas multi-respuesta tienen guión después del código: "250-..."
            // La última línea tiene espacio: "250 ..."
            if (strlen($linea) >= 4 && $linea[3] === ' ') {
                break;
            }
        }

        $this->log[] = "<< " . trim($respuesta);

        $codigo = (int) substr(trim($respuesta), 0, 3);
        if ($codigo !== $codigoEsperado) {
            throw new RuntimeException(
                "SMTP: Código {$codigo} inesperado (esperaba {$codigoEsperado}): " . trim($respuesta)
            );
        }

        return $respuesta;
    }

    private function encodeHeader(string $valor): string
    {
        if (mb_detect_encoding($valor, 'ASCII', true)) return $valor;
        return '=?UTF-8?B?' . base64_encode($valor) . '?=';
    }
}
