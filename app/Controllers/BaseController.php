<?php
// =====================================================
// EduSaaS RD - Controlador Base
// =====================================================

abstract class BaseController
{
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    // --------------------------------------------------
    // VISTAS
    // --------------------------------------------------

    /**
     * Renderiza una vista pasándole variables.
     *
     * Ejemplo de uso en un controlador hijo:
     *   $this->view('students/index', ['estudiantes' => $lista]);
     *
     * Dentro de la vista puedes usar $estudiantes directamente.
     */
    protected function view(string $vista, array $datos = []): void
    {
        // Hace disponibles las variables dentro de la vista
        extract($datos);

        $rutaVista = __DIR__ . '/../../views/' . $vista . '.php';

        if (!file_exists($rutaVista)) {
            $this->error404("Vista no encontrada: {$vista}");
            return;
        }

        require_once $rutaVista;
    }

    /**
     * Renderiza una vista dentro del layout principal.
     * La mayoría de páginas usarán este método.
     *
     * Ejemplo:
     *   $this->render('students/index', ['estudiantes' => $lista], 'Estudiantes');
     */
    protected function render(string $vista, array $datos = [], string $tituloPagina = ''): void
    {
        $datos['_titulo_pagina'] = $tituloPagina ?: $this->config['name'];
        $datos['_vista']         = $vista;

        // El layout se encarga de incluir la vista correcta
        extract($datos);
        require_once __DIR__ . '/../../views/layouts/main.php';
    }

    // --------------------------------------------------
    // REDIRECCIONES
    // --------------------------------------------------

    /**
     * Redirige a una URL.
     * Ejemplo: $this->redirect('/dashboard');
     */
    protected function redirect(string $url): void
    {
        $base = $this->config['url'];
        // Si la URL ya es absoluta, redirigir directamente
        if (str_starts_with($url, 'http')) {
            header("Location: {$url}");
        } else {
            header("Location: {$base}{$url}");
        }
        exit;
    }

    // --------------------------------------------------
    // MENSAJES FLASH (se muestran una sola vez)
    // --------------------------------------------------

    /**
     * Guarda un mensaje para mostrar en la próxima página.
     * Ejemplo: $this->flash(FLASH_SUCCESS, '¡Estudiante guardado!');
     */
    protected function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /**
     * Recupera y elimina el mensaje flash.
     * Se llama desde las vistas/layout.
     */
    public static function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    // --------------------------------------------------
    // RESPUESTAS JSON (para peticiones AJAX)
    // --------------------------------------------------

    /**
     * Devuelve una respuesta JSON.
     * Ejemplo: $this->json(['ok' => true, 'data' => $registro]);
     */
    protected function json(array $datos, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // SEGURIDAD
    // --------------------------------------------------

    /**
     * Verifica que el usuario tenga sesión activa.
     * Si no, redirige al login.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Verifica que el usuario tenga uno de los roles permitidos.
     * Ejemplo: $this->requireRole([ROL_ADMIN, ROL_SUPER_ADMIN]);
     */
    protected function requireRole(array $rolesPermitidos): void
    {
        $this->requireAuth();

        if (!in_array($_SESSION['rol_id'] ?? 0, $rolesPermitidos)) {
            $this->error403();
        }
    }

    /**
     * Verifica que el tenant (institución) del usuario coincida con el recurso.
     * Previene que un admin de colegio A vea datos del colegio B.
     */
    protected function requireSameTenant(int $institucionId): void
    {
        $esSuper = ($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN;

        if (!$esSuper && ($_SESSION['institucion_id'] ?? 0) !== $institucionId) {
            $this->error403();
        }
    }

    /**
     * Genera y verifica tokens CSRF para formularios.
     */
    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken(): void
    {
        $tokenEnviado = $_POST['_csrf_token'] ?? '';
        $tokenSession = $_SESSION['csrf_token'] ?? '';

        if (!hash_equals($tokenSession, $tokenEnviado)) {
            $this->error403('Token CSRF inválido.');
        }
    }

    // --------------------------------------------------
    // ERRORES HTTP
    // --------------------------------------------------

    protected function error403(string $mensaje = 'Acceso denegado.'): void
    {
        http_response_code(403);
        // Si la sesión ya fue cerrada (rutas públicas), usar render simple sin layout
        if (PHP_SESSION_NONE === session_status()) {
            $this->renderError(403, $mensaje);
        } else {
            $this->render('errors/403', ['mensaje' => $mensaje], '403 - Acceso Denegado');
        }
        exit;
    }

    protected function error404(string $mensaje = 'Página no encontrada.'): void
    {
        http_response_code(404);
        if (PHP_SESSION_NONE === session_status()) {
            $this->renderError(404, $mensaje);
        } else {
            $this->render('errors/404', ['mensaje' => $mensaje], '404 - No Encontrado');
        }
        exit;
    }

    /** Error simple sin layout — para rutas públicas donde la sesión está cerrada */
    private function renderError(int $code, string $mensaje): void
    {
        $titulo = $code === 404 ? 'Página no encontrada' : 'Acceso denegado';
        $emoji  = $code === 404 ? '🔍' : '🔒';
        $appUrl = $this->config['url'] ?? '';
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width,initial-scale=1'>
        <title>{$code} — {$titulo}</title>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css' rel='stylesheet'>
        </head><body class='bg-light d-flex align-items-center justify-content-center' style='min-height:100vh'>
        <div class='text-center py-5'>
          <div style='font-size:4rem'>{$emoji}</div>
          <h1 class='h3 mt-3 fw-bold'>{$titulo}</h1>
          <p class='text-muted'>" . htmlspecialchars($mensaje) . "</p>
          <a href='{$appUrl}/auth/login' class='btn btn-primary mt-2'>Volver al inicio</a>
        </div></body></html>";
    }

    // --------------------------------------------------
    // HELPERS
    // --------------------------------------------------

    /** Sanitiza un string para evitar XSS */
    protected function sanitize(string $valor): string
    {
        return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
    }

    /** Devuelve el ID de la institución del usuario logueado */
    protected function getInstitucionId(): ?int
    {
        return $_SESSION['institucion_id'] ?? null;
    }

    /**
     * Igual que getInstitucionId() pero redirige si el super admin
     * intenta acceder sin tener el modo visor activo.
     * Úsalo en todos los controladores del colegio.
     */

    /**
     * Bloquea el acceso a acciones de escritura cuando el super admin
     * está en modo visor. Úsalo en crear/editar/guardar/actualizar/eliminar.
     */
    protected function requireModoEscritura(): void
    {
        if (($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN) {
            $appUrl = $this->config['url'] ?? '';
            $_SESSION['flash'] = [
                'tipo'    => 'error',
                'mensaje' => '🚫 El super administrador no puede realizar cambios directos en los colegios. ' .
                             'Usa el panel de super admin para gestionar instituciones.',
            ];
            // Salir del modo visor si está activo
            $instId = $_SESSION['visor_institucion_id'] ?? null;
            if ($instId) {
                header("Location: {$appUrl}/superadmin/instituciones/{$instId}");
            } else {
                header("Location: {$appUrl}/superadmin");
            }
            exit;
        }
    }

    // --------------------------------------------------
    // CONTROL DE PLAN / SUSCRIPCIÓN
    // --------------------------------------------------

    /**
     * Verifica suscripción activa y carga el plan en sesión.
     * Llamar al inicio de cada método de controladores del colegio.
     *
     *   $this->requireSuscripcion();
     */
    protected function requireSuscripcion(): void
    {
        SuscripcionMiddleware::verificar();
    }

    /**
     * Bloquea el acceso si el plan no incluye el módulo.
     * $modulo: 'pagos' | 'reportes' | 'comunicados' | 'api'
     *
     *   $this->requireModulo('pagos');
     */
    protected function requireModulo(string $modulo): void
    {
        if (!PlanHelper::tieneModulo($modulo)) {
            $plan = PlanHelper::getNombrePlan();
            $this->flash('warning',
                "🔒 El módulo de <strong>" . ucfirst($modulo) . "</strong> no está incluido " .
                "en tu plan <strong>{$plan}</strong>. Contacta a EduSaaS RD para actualizar."
            );
            $this->redirect('/dashboard');
        }
    }

    /**
     * Verifica que el colegio no haya alcanzado el límite de su plan.
     * $recurso: 'estudiante' | 'profesor' | 'seccion'
     * Si llegó al límite, redirige con un mensaje claro.
     *
     *   $this->verificarLimite('estudiante', $instId);
     */
    protected function verificarLimite(string $recurso, int $instId): void
    {
        $puede = match($recurso) {
            'estudiante' => PlanHelper::puedeCrearEstudiante($instId),
            'profesor'   => PlanHelper::puedeCrearProfesor($instId),
            'seccion'    => PlanHelper::puedeCrearSeccion($instId),
            default      => true,
        };

        if (!$puede) {
            $max  = PlanHelper::getLimite($recurso . 's');
            $plan = PlanHelper::getNombrePlan();
            $redir = match($recurso) {
                'estudiante' => '/estudiantes',
                'profesor'   => '/profesores',
                'seccion'    => '/admin/secciones',
                default      => '/dashboard',
            };
            $this->flash('warning',
                "🔒 Has alcanzado el límite de <strong>{$max} " . ucfirst($recurso) . "s</strong> " .
                "de tu plan <strong>{$plan}</strong>. " .
                "Contacta a EduSaaS RD para actualizar tu suscripción."
            );
            $this->redirect($redir);
        }
    }

    protected function getInstitucionIdOrRedirect(): int
    {
        $id = (int)($_SESSION['institucion_id'] ?? 0);

        if ($id === 0 && ($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN) {
            $_SESSION['flash'] = [
                'tipo'    => 'warning',
                'mensaje' => '⚠️ Para ver datos de un colegio usa el <strong>Modo Visor</strong>: ' .
                             'Instituciones → selecciona el colegio → "Revisar datos del colegio".',
            ];
            header('Location: ' . ($this->config['url'] ?? '') . '/superadmin/instituciones');
            exit;
        }

        if ($id === 0) {
            $this->error403('No tienes una institución asignada.');
        }

        return $id;
    }

    /** Devuelve el usuario actualmente logueado */
    protected function getUsuarioActual(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }
}