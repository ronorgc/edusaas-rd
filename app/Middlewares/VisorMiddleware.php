<?php
// =====================================================
// EduSaaS RD - VisorMiddleware
//
// Cuando el super admin entra en "modo visor" de un
// colegio, este middleware:
//   1. Inyecta el institucion_id del colegio revisado
//      para que los controladores vean los datos correctos
//   2. Bloquea CUALQUIER petición POST (escritura)
//   3. El banner visible en el layout alerta al super
//      admin que está en modo visor
//
// Uso en controladores del colegio (estudiantes, califs, etc.):
//   VisorMiddleware::aplicar();
// =====================================================

class VisorMiddleware
{
    /**
     * Aplica el modo visor si está activo.
     * - Si el super admin está revisando un colegio:
     *     · Inyecta el institucion_id del colegio en $_SESSION
     *     · Bloquea peticiones POST
     * - Si es un usuario normal: no hace nada.
     */
    public static function aplicar(): void
    {
        // Solo aplica al super admin
        if (($_SESSION['rol_id'] ?? 0) !== ROL_SUPER_ADMIN) {
            return;
        }

        // Si no está en modo visor, redirigir a su panel
        if (empty($_SESSION['visor_activo'])) {
            $appUrl = (require __DIR__ . '/../../config/app.php')['url'];
            header("Location: {$appUrl}/superadmin");
            exit;
        }

        // Bloquear escritura: el super admin no puede hacer POST en el colegio
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(403);
            self::mostrarBloqueoEscritura();
            exit;
        }

        // Inyectar el institucion_id del colegio visitado
        // Los controladores usarán $this->getInstitucionId() con normalidad
        $_SESSION['institucion_id'] = $_SESSION['visor_institucion_id'];
    }

    /**
     * Activa el modo visor para una institución específica.
     * Se llama desde SuperAdminController::revisarColegio()
     */
    public static function activar(int $institucionId, string $nombreColegio): void
    {
        $_SESSION['visor_activo']             = true;
        $_SESSION['visor_institucion_id']     = $institucionId;
        $_SESSION['visor_institucion_nombre'] = $nombreColegio;
    }

    /**
     * Verifica si el modo visor está activo.
     * Se usa en el layout para mostrar/ocultar el banner.
     */
    public static function estaActivo(): bool
    {
        return !empty($_SESSION['visor_activo'])
            && ($_SESSION['rol_id'] ?? 0) === ROL_SUPER_ADMIN;
    }

    /**
     * Página de bloqueo cuando intenta escribir en modo visor.
     */
    private static function mostrarBloqueoEscritura(): void
    {
        $appUrl = (require __DIR__ . '/../../config/app.php')['url'];
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="refresh" content="3;url=' . $appUrl . '/auth/salir-visor">
            <title>Acción bloqueada</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
            <style>
                body { font-family: "Plus Jakarta Sans", sans-serif; background:#0f172a; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
                .box { background:#1e293b; border-radius:16px; padding:2.5rem; max-width:420px; text-align:center; border:2px solid #ef4444; }
                .icon { font-size:3rem; margin-bottom:1rem; }
                h1 { color:#f1f5f9; font-size:1.3rem; }
                p { color:#94a3b8; font-size:.9rem; }
            </style>
        </head>
        <body>
            <div class="box">
                <div class="icon">🚫</div>
                <h1>Acción bloqueada</h1>
                <p>Estás en <strong>modo visor</strong>.<br>
                No tienes permiso para modificar datos de este colegio.<br><br>
                Redirigiendo en 3 segundos...</p>
            </div>
        </body>
        </html>';
    }
}
