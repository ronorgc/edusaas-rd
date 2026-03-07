<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $_nombre_sist = ConfigModel::get('marca_nombre_sistema','EduSaaS RD'); ?>
    <title><?= htmlspecialchars($_titulo_pagina ?? $_nombre_sist) ?> — <?= htmlspecialchars($_nombre_sist) ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <?php
    $_color_primary = ConfigModel::get('marca_color_primario', '#1a56db');
    $_color_accent  = ConfigModel::get('marca_color_acento',   '#10b981');
    ?>
    <style>
    :root {
        --primary: <?= htmlspecialchars($_color_primary) ?>;
        --accent:  <?= htmlspecialchars($_color_accent) ?>;
    }
        :root {
            --sidebar-width: 260px;
            --primary:   #1a56db;
            --primary-dk:#1240a8;
            --accent:    #f59e0b;
            --success:   #10b981;
            --danger:    #ef4444;
            --sidebar-bg:#0f172a;
            --sidebar-tx:#94a3b8;
            --sidebar-active-bg: rgba(26,86,219,0.18);
            --sidebar-active-tx: #60a5fa;
            --body-bg:   #f1f5f9;
            --card-bg:   #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            color: #1e293b;
            margin: 0;
        }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform .3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .sidebar-brand h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            color: #fff;
            margin: 0;
            letter-spacing: -.5px;
        }
        .sidebar-brand span { color: var(--accent); }
        .sidebar-brand small {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .7rem;
            color: var(--sidebar-tx);
            display: block;
            margin-top: 2px;
        }

        .sidebar-section {
            padding: .75rem 1.25rem .25rem;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #475569;
        }

        .sidebar-menu { list-style: none; padding: 0 .75rem; margin: 0; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .6rem .75rem;
            border-radius: 8px;
            color: var(--sidebar-tx);
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            transition: all .15s;
            margin-bottom: 2px;
        }
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
        }
        .sidebar-menu li a.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-tx);
        }
        .sidebar-menu li a i { font-size: 1rem; width: 18px; text-align: center; }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.07);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .sidebar-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; overflow: hidden; }
        .sidebar-user-name {
            font-size: .8rem;
            font-weight: 600;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: .7rem;
            color: var(--sidebar-tx);
        }

        /* ── MAIN CONTENT ── */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ── */
        #topbar {
            background: var(--card-bg);
            border-bottom: 1px solid #e2e8f0;
            padding: .875rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 500;
        }
        #topbar .page-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        #topbar .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        /* ── PAGE BODY ── */
        .page-body {
            padding: 1.75rem;
            flex: 1;
        }

        /* ── CARDS ── */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        /* ── STAT CARDS ── */
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: .8rem; color: #64748b; margin-top: 3px; }

        /* ── ALERTS FLASH ── */
        .flash-alert {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 9999;
            min-width: 300px;
            max-width: 420px;
            border-radius: 10px;
            padding: .875rem 1.125rem;
            font-size: .875rem;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,.12);
            animation: slideIn .3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(40px); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }

        /* ── TABLES ── */
        .table th {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        .table td { vertical-align: middle; font-size: .875rem; }

        /* ── BADGES ── */
        .badge-activo    { background: #dcfce7; color: #166534; }
        .badge-inactivo  { background: #fee2e2; color: #991b1b; }
        .badge-pendiente { background: #fef9c3; color: #854d0e; }
        .badge-pagada    { background: #dcfce7; color: #166534; }
        .badge-vencida   { background: #fee2e2; color: #991b1b; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php
// Obtener usuario de sesión
$_usuario_sesion = $_SESSION['usuario'] ?? null;
$_rol_id         = $_SESSION['rol_id']  ?? null;
$_url_base       = (require __DIR__ . '/../../config/app.php')['url'];

// Función helper para URL activa
function isActive(string $path): string {
    $urlActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return str_contains($urlActual, $path) ? 'active' : '';
}

// Iniciales del usuario para el avatar
$_iniciales = '';
if ($_usuario_sesion) {
    $_iniciales = strtoupper(
        substr($_usuario_sesion['nombres'] ?? '', 0, 1) .
        substr($_usuario_sesion['apellidos'] ?? '', 0, 1)
    );
}
?>

<!-- ═══ SIDEBAR ═══ -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <?php
        $_brand     = ConfigModel::get('marca_nombre_sistema', 'EduSaaS RD');
        $_slogan    = ConfigModel::get('marca_slogan', 'Sistema Educativo RD 🇩🇴');
        $_logo_url  = ConfigModel::get('marca_logo_url', '');
        // Dividir en primera parte y resto para el color de acento
        $_bp = strlen($_brand) > 4 ? substr($_brand, 0, (int)(strlen($_brand)/2)) : $_brand;
        $_bs = substr($_brand, strlen($_bp));
        ?>
        <?php if ($_logo_url): ?>
        <img src="<?= htmlspecialchars($_url_base . $_logo_url) ?>"
             alt="<?= htmlspecialchars($_brand) ?>"
             style="max-height:40px; max-width:160px; object-fit:contain; margin-bottom:4px; display:block;">
        <?php endif; ?>
        <h1><?= htmlspecialchars($_bp) ?><span><?= htmlspecialchars($_bs) ?></span></h1>
        <?php if (VisorMiddleware::estaActivo()): ?>
        <small style="color:#f59e0b">👁️ Modo Visor — Solo lectura</small>
        <?php else: ?>
        <small><?= htmlspecialchars($_slogan) ?></small>
        <?php endif; ?>
    </div>

    <?php if ($_rol_id === ROL_SUPER_ADMIN && !VisorMiddleware::estaActivo()): ?>
    <!-- ── Menú exclusivo del Super Admin ── -->
    <div class="sidebar-section">Panel Global</div>
    <ul class="sidebar-menu">
        <li><a href="<?= $_url_base ?>/superadmin" class="<?= isActive('/superadmin') ?>">
            <i class="bi bi-shield-fill-check"></i> Dashboard
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/cobros" class="<?= isActive('/superadmin/cobros') ?>"
               style="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/superadmin/cobros') !== false ? '' : '' ?>">
            <i class="bi bi-cash-coin"></i> Caja de Cobros
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/instituciones" class="<?= isActive('/superadmin/instituciones') ?>">
            <i class="bi bi-building"></i> Instituciones
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/planes" class="<?= isActive('/superadmin/planes') ?>">
            <i class="bi bi-star-fill"></i> Planes
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/ingresos" class="<?= isActive('/superadmin/ingresos') ?>">
            <i class="bi bi-cash-stack"></i> Ingresos
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/preregistros" class="<?= isActive('/superadmin/preregistros') ?>">
            <i class="bi bi-building-add"></i> Solicitudes
            <?php
            try {
                $__pend = (int)Database::getInstance()
                    ->query("SELECT COUNT(*) FROM preregistro_colegios WHERE estado='pendiente'")
                    ->fetchColumn();
                if ($__pend > 0) echo "<span class='badge bg-warning text-dark ms-1'>{$__pend}</span>";
            } catch (Exception $__e) {}
            ?>
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/salud" class="<?= isActive('/superadmin/salud') ?>">
            <i class="bi bi-heart-pulse"></i> Salud del sistema
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/emails" class="<?= isActive('/superadmin/emails') ?>">
            <i class="bi bi-envelope-open-text"></i> Log de emails
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/log" class="<?= isActive('/superadmin/log') ?>">
            <i class="bi bi-journal-text"></i> Log de actividad
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/configuracion" class="<?= isActive('/superadmin/configuracion') ?>">
            <i class="bi bi-gear-fill"></i> Configuración
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/usuarios" class="<?= isActive('/superadmin/usuarios') ?>">
            <i class="bi bi-people-fill"></i> Usuarios SA
        </a></li>
        <li><a href="<?= $_url_base ?>/superadmin/notificaciones" class="<?= isActive('/superadmin/notificaciones') ?>">
            <i class="bi bi-bell-fill"></i> Notificaciones
        </a></li>
    </ul>

    <?php else: ?>
    <!-- ── Menú del colegio (admin, profesor, padre, estudiante, o super admin en modo visor) ── -->
    <div class="sidebar-section">Principal</div>
    <ul class="sidebar-menu">
        <li><a href="<?= $_url_base ?>/dashboard" class="<?= isActive('/dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a></li>
    </ul>

    <div class="sidebar-section">Académico</div>
    <ul class="sidebar-menu">
        <li><a href="<?= $_url_base ?>/estudiantes" class="<?= isActive('/estudiantes') ?>">
            <i class="bi bi-people-fill"></i> Estudiantes
        </a></li>
        <li><a href="<?= $_url_base ?>/admin/preinscripciones" class="<?= isActive('/admin/preinscripciones') ?>">
            <i class="bi bi-inbox-fill"></i> Pre-inscripciones
        </a></li>
        <li><a href="<?= $_url_base ?>/profesores" class="<?= isActive('/profesores') ?>">
            <i class="bi bi-person-workspace"></i> Profesores
        </a></li>
        <li><a href="<?= $_url_base ?>/matriculas" class="<?= isActive('/matriculas') ?>">
            <i class="bi bi-journal-check"></i> Matrículas
        </a></li>
        <li><a href="<?= $_url_base ?>/calificaciones" class="<?= isActive('/calificaciones') ?>">
            <i class="bi bi-star-fill"></i> Calificaciones
        </a></li>
        <li><a href="<?= $_url_base ?>/asistencia" class="<?= isActive('/asistencia') ?>">
            <i class="bi bi-calendar-check-fill"></i> Asistencia
        </a></li>
    </ul>

    <?php if (in_array($_rol_id, [ROL_ADMIN]) || VisorMiddleware::estaActivo()): ?>
    <div class="sidebar-section">Administración</div>
    <ul class="sidebar-menu">
        <li><a href="<?= $_url_base ?>/pagos" class="<?= isActive('/pagos') ?>">
            <i class="bi bi-cash-stack"></i> Pagos y Cuotas
        </a></li>
        <li><a href="<?= $_url_base ?>/comunicados" class="<?= isActive('/comunicados') ?>">
            <i class="bi bi-megaphone-fill"></i> Comunicados
        </a></li>
        <li><a href="<?= $_url_base ?>/reportes" class="<?= isActive('/reportes') ?>">
            <i class="bi bi-bar-chart-fill"></i> Reportes
        </a></li>
        <?php if (!VisorMiddleware::estaActivo()): ?>
        <li><a href="<?= $_url_base ?>/admin/anos-escolares" class="<?= isActive('/admin') ?>">
            <i class="bi bi-gear-fill"></i> Configuración
        </a></li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>

    <?php endif; // fin super admin vs colegio ?>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= htmlspecialchars($_iniciales) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">
                    <?= htmlspecialchars(($_usuario_sesion['nombres'] ?? '') . ' ' . ($_usuario_sesion['apellidos'] ?? '')) ?>
                </div>
                <div class="sidebar-user-role">
                    <?php
                    $roles = ['', 'Super Admin', 'Administrador', 'Profesor', 'Padre', 'Estudiante'];
                    echo $roles[$_rol_id] ?? 'Usuario';
                    ?>
                </div>
            </div>
            <a href="<?= $_url_base ?>/auth/logout" class="text-danger ms-1" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>

<!-- ═══ CONTENIDO PRINCIPAL ═══ -->
<div id="main-content">
    <!-- Topbar -->
    <header id="topbar">
        <button class="btn btn-sm btn-light d-md-none" id="toggle-sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h2 class="page-title"><?= htmlspecialchars($_titulo_pagina ?? '') ?></h2>
        <div class="topbar-right">
            <span class="text-muted small">
                <?= date('d/m/Y') ?>
            </span>
        </div>
    </header>

    <!-- Banner MODO VISOR (solo aparece cuando super admin revisa un colegio) -->
    <?php if (VisorMiddleware::estaActivo()): ?>
    <div id="visor-banner" style="
        background: linear-gradient(90deg, #92400e, #b45309);
        color: #fff;
        padding: .6rem 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .85rem;
        font-weight: 600;
        position: sticky;
        top: 64px;
        z-index: 490;
        border-bottom: 2px solid #f59e0b;
    ">
        <span>
            👁️ &nbsp;MODO VISOR —
            <strong><?= htmlspecialchars($_SESSION['visor_institucion_nombre'] ?? '') ?></strong>
            &nbsp;·&nbsp; Solo lectura. No puedes modificar ni eliminar datos.
        </span>
        <a href="<?= $_url_base ?>/auth/salir-visor"
           style="background:#fff;color:#92400e;padding:.3rem .9rem;border-radius:6px;text-decoration:none;font-size:.8rem;font-weight:700;">
            ✕ &nbsp;Salir del visor
        </a>
    </div>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php
    $flash = BaseController::getFlash();
    if ($flash):
        $tipoClase = [
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info',
        ][$flash['tipo']] ?? 'alert-info';
    ?>
    <div class="flash-alert alert <?= $tipoClase ?>" id="flash-msg" role="alert">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
    <?php endif; ?>

    <!-- Banner de Trial -->
    <?php if (!empty($_SESSION['aviso_trial'])): ?>
    <?php
        $_td = (int)$_SESSION['aviso_trial'];
        $_tt = (int)($_SESSION['trial_dias_total'] ?? 14);
        $_pct = $_tt > 0 ? max(0, min(100, round(100 * (1 - $_td / $_tt)))) : 100;
        $_color = $_td <= 3 ? '#dc2626' : ($_td <= 7 ? '#d97706' : '#7c3aed');
    ?>
    <div style="background:<?= $_color ?>11;border-bottom:2px solid <?= $_color ?>33;padding:.6rem 1.5rem;font-size:.85rem">
        <div class="d-flex align-items-center gap-3">
            <span style="font-size:1.1rem">🧪</span>
            <div class="flex-grow-1">
                <strong style="color:<?= $_color ?>">Período de prueba</strong>
                — Te quedan <strong><?= $_td ?> día<?= $_td!=1?'s':'' ?></strong> de acceso gratuito.
                <div class="progress mt-1" style="height:4px;max-width:200px;display:inline-flex;width:200px;vertical-align:middle;margin-left:.5rem">
                    <div class="progress-bar" style="width:<?= $_pct ?>%;background:<?= $_color ?>"></div>
                </div>
            </div>
            <?php if (($_SESSION['rol_id']??0) !== ROL_SUPER_ADMIN): ?>
            <a href="mailto:<?= htmlspecialchars(ConfigModel::get('empresa_email','soporte@edusaas.do')) ?>"
               class="btn btn-sm" style="background:<?= $_color ?>;color:#fff;white-space:nowrap">
                Contratar plan →
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenido de la vista -->
    <main class="page-body">
        <?php require_once __DIR__ . '/../../views/' . ($_vista ?? 'dashboard/index') . '.php'; ?>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
    // Cerrar flash automáticamente
    const flash = document.getElementById('flash-msg');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity .5s';
            setTimeout(() => flash.remove(), 500);
        }, 4000);
    }

    // Toggle sidebar en móvil
    document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>
</body>
</html>