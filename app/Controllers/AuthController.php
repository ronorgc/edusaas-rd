<?php
// =====================================================
// EduSaaS RD - AuthController
// Maneja login, logout y verificación de sesión
// =====================================================

class AuthController extends BaseController
{
    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * GET /
     * Redirige al dashboard si ya tiene sesión, si no al login.
     */
    public function index(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $destino = ($_SESSION['rol_id'] === ROL_SUPER_ADMIN)
                ? '/superadmin'
                : '/dashboard';
            $this->redirect($destino);
        } else {
            $this->redirect('/auth/login');
        }
    }

    public function loginForm(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $destino = ($_SESSION['rol_id'] === ROL_SUPER_ADMIN)
                ? '/superadmin'
                : '/dashboard';
            $this->redirect($destino);
        }

        $this->view('auth/login', [
            'csrf_token' => $this->generateCsrfToken(),
            'error'      => $_SESSION['login_error'] ?? null,
        ]);

        unset($_SESSION['login_error']);
    }

    /**
     * POST /auth/login
     * Procesa el formulario de login.
     */
    public function login(): void
    {
        $this->verifyCsrfToken();

        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validación básica
        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Por favor completa todos los campos.';
            $this->redirect('/auth/login');
            return;
        }

        // Buscar usuario por username o email
        $usuario = $this->usuarioModel->findByUsernameOrEmail($username);

        // ── Bloqueo por intentos fallidos ─────────────────────────────────
        if ($usuario) {
            // ¿Está bloqueado actualmente?
            if (!empty($usuario['bloqueado_hasta']) &&
                strtotime($usuario['bloqueado_hasta']) > time()) {
                $minutos = (int)ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
                $_SESSION['login_error'] =
                    "Cuenta bloqueada por demasiados intentos fallidos. " .
                    "Intenta nuevamente en {$minutos} minuto" . ($minutos !== 1 ? 's' : '') . ".";
                $this->redirect('/auth/login');
                return;
            }
        }

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            // Registrar intento fallido
            if ($usuario) {
                try {
                    $maxIntentos = (int)ConfigModel::get('sistema_max_intentos', '5');
                    $nuevosIntentos = (int)($usuario['intentos_fallidos'] ?? 0) + 1;
                    $bloqueoHasta = null;

                    if ($nuevosIntentos >= $maxIntentos) {
                        // Bloquear 30 minutos
                        $bloqueoHasta = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                        $nuevosIntentos = 0; // Resetear contador al bloquear
                        ActivityLog::registrar('usuarios', 'bloqueo_login',
                            "Cuenta bloqueada por intentos: {$usuario['username']}",
                            ['entidad_tipo' => 'usuario', 'entidad_id' => $usuario['id']]
                        );
                    }

                    $db = Database::getInstance();
                    $db->prepare(
                        "UPDATE usuarios SET intentos_fallidos = :n, bloqueado_hasta = :b WHERE id = :id"
                    )->execute([
                        ':n'  => $nuevosIntentos,
                        ':b'  => $bloqueoHasta,
                        ':id' => $usuario['id'],
                    ]);

                    if ($bloqueoHasta) {
                        $_SESSION['login_error'] =
                            "Cuenta bloqueada temporalmente por demasiados intentos fallidos. " .
                            "Intenta nuevamente en 30 minutos.";
                        $this->redirect('/auth/login');
                        return;
                    }

                    $restantes = $maxIntentos - $nuevosIntentos;
                    if ($restantes <= 2) {
                        $_SESSION['login_error'] =
                            "Usuario o contraseña incorrectos. " .
                            "Te quedan {$restantes} intento" . ($restantes !== 1 ? 's' : '') . " antes del bloqueo.";
                        $this->redirect('/auth/login');
                        return;
                    }
                } catch (Exception $ignored) {}
            }

            $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            $this->redirect('/auth/login');
            return;
        }

        if (!$usuario['activo']) {
            $_SESSION['login_error'] = 'Tu cuenta está desactivada. Contacta al administrador.';
            $this->redirect('/auth/login');
            return;
        }

        // ✅ Login exitoso — resetear intentos fallidos
        $this->crearSesion($usuario);

        // Actualizar último acceso y limpiar bloqueo
        $this->usuarioModel->update($usuario['id'], [
            'ultimo_acceso'    => date('Y-m-d H:i:s'),
            'intentos_fallidos'=> 0,
            'bloqueado_hasta'  => null,
        ]);

        // Redirigir según rol
        $destino = ($usuario['rol_id'] === ROL_SUPER_ADMIN)
            ? '/superadmin'
            : '/dashboard';

        $this->redirect($destino);
    }

    /**
     * GET /auth/logout
     * Cierra la sesión del usuario.
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/auth/login');
    }

    /**
     * GET /auth/salir-visor
     * El super admin sale del modo visor y vuelve a su panel.
     */
    public function salirVisor(): void
    {
        $this->requireRole([ROL_SUPER_ADMIN]);

        unset(
            $_SESSION['visor_institucion_id'],
            $_SESSION['visor_institucion_nombre'],
            $_SESSION['visor_activo']
        );

        $this->flash(FLASH_INFO, 'Has salido del modo visor.');
        $this->redirect('/superadmin/instituciones');
    }

    // --------------------------------------------------
    // MÉTODOS PRIVADOS
    // --------------------------------------------------

    /**
     * Guarda los datos del usuario en la sesión.
     * Nunca guardar la contraseña en sesión.
     */
    private function crearSesion(array $usuario): void
    {
        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario']       = [
            'id'       => $usuario['id'],
            'nombres'  => $usuario['nombres'],
            'apellidos'=> $usuario['apellidos'],
            'username' => $usuario['username'],
            'email'    => $usuario['email'],
            'foto'     => $usuario['foto'],
        ];
        $_SESSION['rol_id']        = $usuario['rol_id'];
        $_SESSION['institucion_id']= $usuario['institucion_id'];
    }
}