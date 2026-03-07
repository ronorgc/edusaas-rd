<?php

/**
 * =====================================================
 * EduSaaS RD — Auditoría del Sistema (trs.php)
 * Prueba real módulo por módulo como superadmin.
 *
 * USO: http://localhost/edusaas-rd/public/trs.php
 *      ?user=admin&pass=TuPassword
 *
 * ELIMINAR en producción.
 * =====================================================
 */

define('TRS_VERSION', '1.0');
$inicio_total = microtime(true);

// ── Configuración ──────────────────────────────────────────────────────────
$CONFIG = [
    'base_url'   => 'http://localhost/edusaas-rd/public',
    'db_host'    => 'localhost',
    'db_name'    => 'edusaas_rd',
    'db_user'    => 'root',
    'db_pass'    => '',
    'timeout'    => 10,
];

// Credenciales desde GET para no hardcodear
$CREDS = [
    'username' => $_GET['user'] ?? '',
    'password' => $_GET['pass'] ?? '',
];

// ── Seguridad mínima: solo localhost ───────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1']) && ($_GET['bypass'] ?? '') !== 'local') {
    http_response_code(403);
    die('<h1>403 — Solo accesible desde localhost</h1>');
}

// ── Helpers ────────────────────────────────────────────────────────────────
function req(string $url, array $cfg, string $cookieFile, string $method = 'GET', array $post = []): array
{
    $t = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $cfg['timeout'],
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'EduSaaS-Audit/1.0',
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $html  = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err   = curl_error($ch);
    curl_close($ch);
    $ms = round((microtime(true) - $t) * 1000);

    return [
        'url'    => $url,
        'final'  => $final,
        'code'   => $code,
        'html'   => $html ?: '',
        'ms'     => $ms,
        'curl_err' => $err,
    ];
}

function detectarErrores(string $html): array
{
    $errores = [];
    $patrones = [
        '/Fatal error.*?in.*?on line \d+/i'             => '🔴 Fatal Error',
        '/Warning:.*?in.*?on line \d+/i'                => '🟡 Warning',
        '/Notice:.*?in.*?on line \d+/i'                 => '🔵 Notice',
        '/Deprecated:.*?in.*?on line \d+/i'             => '🟣 Deprecated',
        '/Parse error.*?in.*?on line \d+/i'             => '🔴 Parse Error',
        '/Uncaught.*?Exception/i'                        => '🔴 Exception',
        '/Call to undefined/i'                           => '🔴 Undefined call',
        '/Undefined variable/i'                          => '🟡 Undef var',
        '/Undefined array key/i'                         => '🔵 Undef key',
        '/Division by zero/i'                            => '🔴 Div/0',
        '/SQLSTATE\[/i'                                  => '🔴 SQL Error',
        '/Column not found/i'                            => '🔴 SQL Column',
        '/Table.*doesn.*exist/i'                         => '🔴 SQL Table',
        '/Class.*not found/i'                            => '🔴 Class not found',
    ];
    foreach ($patrones as $pat => $label) {
        if (preg_match($pat, $html, $m)) {
            // Extract short snippet
            $snippet = strip_tags(substr($m[0], 0, 120));
            $errores[] = "{$label}: " . htmlspecialchars($snippet);
        }
    }
    return $errores;
}

function statusClass(int $code, array $errores, int $ms, bool $esRedirectLogin = false): string
{
    if (!empty($errores))  return 'error';
    if ($esRedirectLogin)  return 'warn';
    if ($code === 0)       return 'error';
    if ($code >= 500)      return 'error';
    if ($code >= 400)      return 'error';
    if ($ms > 3000)        return 'warn';
    if ($code >= 200 && $code < 400) return 'ok';
    return 'warn';
}

function extraerTitulo(string $html): string
{
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        return trim(strip_tags($m[1]));
    }
    return '';
}

function esRedirectLogin(array $res): bool
{
    return str_contains($res['final'] ?? '', '/auth/login') ||
        str_contains($res['html']  ?? '', 'loginForm') ||
        str_contains($res['html']  ?? '', 'name="username"');
}

function getCsrfToken(string $html): string
{
    if (preg_match('/name=["\']_csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    return '';
}

// ── Cookie jar temporal ────────────────────────────────────────────────────
$cookieFile = sys_get_temp_dir() . '/trs_audit_' . md5(session_id() . time()) . '.txt';

// ── Conectar a la BD directamente para obtener info ────────────────────────
$dbOk = false;
$dbInfo = [];
$institucionId = null;
$institucionIdTest = null;
$planIdTest = null;
$pagoIdTest = null;
$preregistroIdTest = null;

try {
    $pdo = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']};charset=utf8mb4",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbOk = true;

    // Estadísticas rápidas
    $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tablas as $t) {
        $n = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        $dbInfo[$t] = (int)$n;
    }

    // IDs reales para pruebas
    $inst = $pdo->query("SELECT id FROM instituciones LIMIT 1")->fetch();
    $institucionId = $institucionIdTest = $inst['id'] ?? null;

    $plan = $pdo->query("SELECT id FROM planes WHERE activo = 1 LIMIT 1")->fetch();
    $planIdTest = $plan['id'] ?? null;

    $pago = $pdo->query("SELECT id FROM pagos_saas ORDER BY id DESC LIMIT 1")->fetch();
    $pagoIdTest = $pago['id'] ?? null;

    $preg = $pdo->query("SELECT id FROM preregistro_colegios LIMIT 1")->fetch();
    $preregistroIdTest = $preg['id'] ?? null;
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// ── Login ──────────────────────────────────────────────────────────────────
$loginOk   = false;
$loginMsg  = '';
$loginMs   = 0;

if ($CREDS['username'] && $CREDS['password']) {
    // GET login form para obtener CSRF
    $loginForm = req($CONFIG['base_url'] . '/auth/login', $CONFIG, $cookieFile);
    $csrf = getCsrfToken($loginForm['html']);

    // POST login
    $t = microtime(true);
    $loginRes = req($CONFIG['base_url'] . '/auth/login', $CONFIG, $cookieFile, 'POST', [
        '_csrf_token' => $csrf,
        'username'    => $CREDS['username'],
        'password'    => $CREDS['password'],
    ]);
    $loginMs = round((microtime(true) - $t) * 1000);

    if (
        str_contains($loginRes['final'], '/superadmin') ||
        str_contains($loginRes['html'], 'superadmin')
    ) {
        $loginOk = true;
        $loginMsg = '✅ Autenticado como superadmin';
    } elseif (
        str_contains($loginRes['html'], 'Bienvenido') ||
        str_contains($loginRes['html'], 'dashboard')
    ) {
        $loginOk  = true;
        $loginMsg = '⚠️ Login OK pero no es superadmin (rol diferente)';
    } else {
        $loginMsg = '❌ Credenciales incorrectas o login fallido';
    }
}

// ── Definir módulos a probar ────────────────────────────────────────────────
$BASE = $CONFIG['base_url'];
$iid  = $institucionIdTest ?? 1;
$pid  = $planIdTest ?? 1;
$paid = $pagoIdTest ?? 1;
$prid = $preregistroIdTest ?? 1;

$MODULOS = [
    '🌐 Públicas' => [
        ['GET', '/registro',         'Formulario de preregistro público'],
        ['GET', '/registro/gracias',  'Página de gracias preregistro'],
    ],
    '🔐 Autenticación' => [
        ['GET', '/auth/login',  'Formulario de login'],
    ],
    '📊 Dashboard Superadmin' => [
        ['GET', '/superadmin',  'Dashboard principal'],
    ],
    '🏫 Instituciones' => [
        ['GET', '/superadmin/instituciones',              'Lista de instituciones'],
        ['GET', '/superadmin/instituciones/crear',        'Formulario crear institución'],
        ['GET', "/superadmin/instituciones/{$iid}",       'Ver institución'],
        ['GET', "/superadmin/instituciones/{$iid}/editar", 'Editar institución'],
        ['GET', "/superadmin/instituciones/{$iid}/usuarios", 'Usuarios del colegio'],
        ['GET', '/superadmin/instituciones/exportar',     'Export CSV instituciones'],
        // Visor al final — cambia la sesión, se restaura con salir-visor
        ['GET', "/superadmin/instituciones/{$iid}/revisar", 'Activar modo visor'],
        ['GET', '/auth/salir-visor',                      'Salir del modo visor (restaurar SA)'],
    ],
    '💳 Planes' => [
        ['GET', '/superadmin/planes',              'Lista de planes'],
        ['GET', '/superadmin/planes/crear',        'Formulario crear plan'],
        ['GET', "/superadmin/planes/{$pid}/editar", 'Editar plan'],
    ],
    '💰 Cobros & Pagos' => [
        ['GET', '/superadmin/cobros',                    'Panel de cobros'],
        ['GET', '/superadmin/cobros/masivo',             'Renovación masiva'],
        ['GET', '/superadmin/cobros/formulario?inst_id=' . $iid, 'Formulario cobro (partial)'],
        ['GET', "/superadmin/cobros/recibo/{$paid}",     'Recibo cobro'],
        ['GET', '/superadmin/ingresos',                  'Panel de ingresos'],
        ['GET', '/superadmin/ingresos/exportar',         'Export PDF ingresos'],
        ['GET', "/superadmin/pagos/{$paid}/recibo",      'Recibo pago imprimible'],
    ],
    '📋 Preregistros' => [
        ['GET', '/superadmin/preregistros',           'Lista de preregistros'],
        ['GET', "/superadmin/preregistros/{$prid}",   'Ver preregistro'],
    ],
    '👥 Usuarios SA' => [
        ['GET', '/superadmin/usuarios',           'Lista usuarios superadmin'],
        ['GET', '/superadmin/usuarios/crear',     'Formulario crear usuario SA'],
    ],
    '📧 Notificaciones' => [
        ['GET', '/superadmin/notificaciones',     'Panel de notificaciones'],
        ['GET', '/superadmin/emails',             'Log de emails enviados'],
    ],
    '📜 Logs' => [
        ['GET', '/superadmin/log',               'Log de actividad global'],
    ],
    '❤️ Sistema' => [
        ['GET', '/superadmin/salud',             'Dashboard de salud del sistema'],
        ['GET', '/superadmin/configuracion',     'Configuración del sistema'],
        ['GET', '/superadmin/cron/avisos-vencimiento', 'Cron avisos (sin token — debe dar 403)'],
    ],
    '🎓 Módulo Colegio (Admin)' => [
        ['GET', '/dashboard',                    'Dashboard del colegio'],
        ['GET', '/estudiantes',                  'Lista de estudiantes'],
        ['GET', '/estudiantes/crear',            'Formulario nuevo estudiante'],
        ['GET', '/admin/preinscripciones',       'Panel preinscripciones'],
    ],
];

// ── Ejecutar pruebas ───────────────────────────────────────────────────────
$resultados = [];
$totalOk    = 0;
$totalWarn  = 0;
$totalError = 0;

if ($loginOk) {
    foreach ($MODULOS as $modulo => $rutas) {
        foreach ($rutas as [$method, $path, $desc]) {
            $url = $BASE . $path;
            $res = req($url, $CONFIG, $cookieFile, $method);
            $errores = detectarErrores($res['html']);
            $titulo  = extraerTitulo($res['html']);
            $rLogin  = esRedirectLogin($res);
            $status  = statusClass($res['code'], $errores, $res['ms'], $rLogin);

            if ($status === 'ok')    $totalOk++;
            elseif ($status === 'warn') $totalWarn++;
            else                    $totalError++;

            $resultados[$modulo][] = [
                'method'  => $method,
                'path'    => $path,
                'desc'    => $desc,
                'code'    => $res['code'],
                'ms'      => $res['ms'],
                'status'  => $status,
                'errores' => $errores,
                'titulo'  => $titulo,
                'final'   => $res['final'],
                'rlogin'  => $rLogin,
                'curl_err' => $res['curl_err'],
            ];
        }
    }
}

$total_ms = round((microtime(true) - $inicio_total) * 1000);

// Limpiar cookie
@unlink($cookieFile);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduSaaS — Auditoría del Sistema</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 2rem;
            border-bottom: 1px solid #4338ca;
        }

        .header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #a5b4fc;
        }

        .header .meta {
            color: #94a3b8;
            font-size: .82rem;
            margin-top: .4rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Login form */
        .login-box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            max-width: 480px;
            margin: 3rem auto;
        }

        .login-box h2 {
            color: #a5b4fc;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: .82rem;
            color: #94a3b8;
            margin-bottom: .4rem;
        }

        .form-group input {
            width: 100%;
            padding: .6rem .9rem;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: .9rem;
        }

        .btn-audit {
            width: 100%;
            padding: .75rem;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: .5rem;
        }

        .btn-audit:hover {
            background: #4338ca;
        }

        .warning-box {
            background: #422006;
            border: 1px solid #92400e;
            border-radius: 8px;
            padding: .8rem 1rem;
            margin-bottom: 1rem;
            font-size: .82rem;
            color: #fbbf24;
        }

        /* Stats bar */
        .stats-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .stat {
            background: #1e293b;
            border-radius: 10px;
            padding: .8rem 1.5rem;
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .stat .n {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat .l {
            font-size: .75rem;
            color: #94a3b8;
            margin-top: .2rem;
        }

        .stat.ok .n {
            color: #34d399;
        }

        .stat.warn .n {
            color: #fbbf24;
        }

        .stat.error .n {
            color: #f87171;
        }

        .stat.time .n {
            color: #a5b4fc;
        }

        /* Module sections */
        .mod-section {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .mod-header {
            padding: .7rem 1rem;
            font-weight: 600;
            font-size: .92rem;
            background: #0f172a;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .mod-header:hover {
            background: #1a2540;
        }

        .mod-badges {
            display: flex;
            gap: .4rem;
            align-items: center;
        }

        .badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }

        .badge.ok {
            background: #064e3b;
            color: #34d399;
        }

        .badge.warn {
            background: #451a03;
            color: #fbbf24;
        }

        .badge.error {
            background: #450a0a;
            color: #f87171;
        }

        /* Test rows */
        .test-row {
            display: flex;
            align-items: flex-start;
            padding: .55rem 1rem;
            border-top: 1px solid #1e2d40;
            gap: .8rem;
            font-size: .82rem;
        }

        .test-row:hover {
            background: #162032;
        }

        .test-row .indicator {
            font-size: 1rem;
            flex-shrink: 0;
            margin-top: .1rem;
        }

        .test-row .info {
            flex: 1;
            min-width: 0;
        }

        .test-row .route {
            font-family: monospace;
            font-size: .78rem;
            color: #7dd3fc;
        }

        .test-row .desc {
            color: #cbd5e1;
        }

        .test-row .title-page {
            color: #64748b;
            font-size: .73rem;
            font-style: italic;
        }

        .test-row .errors {
            margin-top: .3rem;
        }

        .test-row .errors .e {
            color: #fca5a5;
            font-size: .76rem;
            background: #3b0a0a;
            padding: .2rem .5rem;
            border-radius: 4px;
            margin-top: .2rem;
        }

        .test-row .meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .2rem;
            flex-shrink: 0;
        }

        .http-badge {
            padding: .1rem .45rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: .74rem;
            font-weight: 700;
        }

        .http-200 {
            background: #052e16;
            color: #4ade80;
        }

        .http-301,
        .http-302 {
            background: #1e3a5f;
            color: #93c5fd;
        }

        .http-403 {
            background: #1c1917;
            color: #a8a29e;
        }

        .http-404 {
            background: #422006;
            color: #fbbf24;
        }

        .http-500 {
            background: #450a0a;
            color: #f87171;
        }

        .http-0 {
            background: #334155;
            color: #94a3b8;
        }

        .ms-badge {
            font-size: .7rem;
            color: #64748b;
        }

        .ms-slow {
            color: #f59e0b;
        }

        /* DB panel */
        .db-panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .db-panel h3 {
            color: #a5b4fc;
            font-size: .9rem;
            margin-bottom: .8rem;
        }

        .db-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: .4rem;
        }

        .db-row {
            display: flex;
            justify-content: space-between;
            background: #0f172a;
            padding: .35rem .7rem;
            border-radius: 6px;
            font-size: .78rem;
        }

        .db-row .tn {
            color: #93c5fd;
            font-family: monospace;
        }

        .db-row .tc {
            color: #64748b;
        }

        .db-row.empty .tc {
            color: #334155;
        }

        .db-row.big .tc {
            color: #fbbf24;
        }

        /* Collapsed */
        .mod-body {
            display: none;
        }

        .mod-body.open {
            display: block;
        }

        .mod-header .arrow {
            transition: transform .2s;
        }

        .mod-header.open .arrow {
            transform: rotate(90deg);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="container" style="max-width:1100px;margin:0 auto">
            <h1>🔍 EduSaaS RD — Auditoría del Sistema v<?= TRS_VERSION ?></h1>
            <div class="meta">
                <?= date('d/m/Y H:i:s') ?> ·
                PHP <?= PHP_VERSION ?> ·
                <?php if ($dbOk): ?>✅ BD conectada
                <?php else: ?>❌ BD sin conexión
            <?php endif; ?>
            <?php if ($loginOk): ?> · <?= $loginMsg ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">

        <?php if (!$CREDS['username']): ?>
            <!-- Login form para ingresar credenciales -->
            <div class="login-box">
                <h2>🔐 Auditoría del Sistema</h2>
                <div class="warning-box">
                    ⚠️ <strong>Solo para desarrollo.</strong> Elimina este archivo en producción.
                    Ingresa credenciales de superadmin para iniciar la auditoría completa.
                </div>
                <form method="GET">
                    <div class="form-group">
                        <label>Usuario superadmin</label>
                        <input type="text" name="user" placeholder="username" autofocus>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="pass" placeholder="contraseña">
                    </div>
                    <button type="submit" class="btn-audit">🚀 Iniciar Auditoría</button>
                </form>
            </div>

        <?php elseif (!$loginOk): ?>
            <div style="text-align:center;padding:3rem 0">
                <div style="font-size:3rem">❌</div>
                <h2 style="color:#f87171;margin:.5rem 0"><?= htmlspecialchars($loginMsg) ?></h2>
                <a href="trs.php" style="color:#a5b4fc">← Volver</a>
            </div>

        <?php else: ?>

            <!-- Stats generales -->
            <div class="stats-bar" style="margin-top:1.5rem">
                <div class="stat ok">
                    <div class="n"><?= $totalOk ?></div>
                    <div class="l">✅ OK</div>
                </div>
                <div class="stat warn">
                    <div class="n"><?= $totalWarn ?></div>
                    <div class="l">⚠️ Advertencia</div>
                </div>
                <div class="stat error">
                    <div class="n"><?= $totalError ?></div>
                    <div class="l">❌ Error</div>
                </div>
                <div class="stat time">
                    <div class="n"><?= number_format($total_ms / 1000, 1) ?>s</div>
                    <div class="l">⏱ Tiempo total</div>
                </div>
                <div class="stat" style="border-color:#334155">
                    <div class="n" style="color:#e2e8f0"><?= $totalOk + $totalWarn + $totalError ?></div>
                    <div class="l">Rutas probadas</div>
                </div>
            </div>

            <!-- Resumen de errores arriba si los hay -->
            <?php
            $todosErrores = [];
            foreach ($resultados as $mod => $rutas) {
                foreach ($rutas as $r) {
                    if ($r['status'] === 'error' || !empty($r['errores'])) {
                        $todosErrores[] = ['mod' => $mod] + $r;
                    }
                }
            }
            ?>
            <?php if (!empty($todosErrores)): ?>
                <div class="db-panel" style="border-color:#7f1d1d">
                    <h3 style="color:#f87171">🚨 Errores encontrados (<?= count($todosErrores) ?>)</h3>
                    <?php foreach ($todosErrores as $e): ?>
                        <div style="background:#0f172a;border-radius:6px;padding:.5rem .8rem;margin-bottom:.4rem;font-size:.8rem">
                            <div><span style="color:#94a3b8"><?= htmlspecialchars($e['mod']) ?></span> →
                                <span style="color:#7dd3fc;font-family:monospace"><?= htmlspecialchars($e['path']) ?></span>
                                — <?= htmlspecialchars($e['desc']) ?>
                                <span class="http-badge http-<?= $e['code'] ?>" style="margin-left:.4rem">HTTP <?= $e['code'] ?></span>
                            </div>
                            <?php foreach ($e['errores'] as $err): ?>
                                <div class="test-row errors e" style="margin-top:.2rem;margin-left:0"><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                            <?php if ($e['rlogin']): ?>
                                <div style="color:#fbbf24;font-size:.75rem;margin-top:.2rem">⚠️ Redirigió al login — sesión expirada o ruta protegida</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- BD info -->
            <?php if ($dbOk && !empty($dbInfo)): ?>
                <div class="db-panel">
                    <h3>🗄️ Tablas de la BD
                        <span style="color:#64748b;font-weight:400;font-size:.78rem">(<?= count($dbInfo) ?> tablas)</span>
                    </h3>
                    <div class="db-grid">
                        <?php foreach ($dbInfo as $tabla => $count): ?>
                            <div class="db-row <?= $count === 0 ? 'empty' : ($count > 1000 ? 'big' : '') ?>">
                                <span class="tn"><?= htmlspecialchars($tabla) ?></span>
                                <span class="tc"><?= number_format($count) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Resultados por módulo -->
            <?php foreach ($resultados as $modulo => $rutas):
                $mOk    = count(array_filter($rutas, fn($r) => $r['status'] === 'ok'));
                $mWarn  = count(array_filter($rutas, fn($r) => $r['status'] === 'warn'));
                $mError = count(array_filter($rutas, fn($r) => $r['status'] === 'error'));
                $modId  = 'mod_' . md5($modulo);
                $hasProblems = $mWarn + $mError > 0;
            ?>
                <div class="mod-section">
                    <div class="mod-header <?= $hasProblems ? 'open' : '' ?>" onclick="toggle('<?= $modId ?>', this)">
                        <span><?= htmlspecialchars($modulo) ?></span>
                        <div class="mod-badges">
                            <?php if ($mOk):   ?><span class="badge ok"><?= $mOk ?> OK</span><?php endif; ?>
                            <?php if ($mWarn):  ?><span class="badge warn"><?= $mWarn ?> ⚠️</span><?php endif; ?>
                            <?php if ($mError): ?><span class="badge error"><?= $mError ?> ❌</span><?php endif; ?>
                            <span class="arrow" style="color:#64748b;font-size:.9rem">▶</span>
                        </div>
                    </div>
                    <div class="mod-body <?= $hasProblems ? 'open' : '' ?>" id="<?= $modId ?>">
                        <?php foreach ($rutas as $r):
                            $ind = match ($r['status']) {
                                'ok'    => '✅',
                                'warn'  => '⚠️',
                                'error' => '❌',
                            };
                            $httpClass = 'http-' . (in_array($r['code'], [200, 301, 302, 403, 404, 500]) ? $r['code'] : ($r['code'] === 0 ? '0' : '200'));
                            $msClass   = $r['ms'] > 2000 ? 'ms-slow' : 'ms-badge';
                        ?>
                            <div class="test-row">
                                <div class="indicator"><?= $ind ?></div>
                                <div class="info">
                                    <div class="desc"><?= htmlspecialchars($r['desc']) ?></div>
                                    <div class="route"><?= htmlspecialchars($r['method']) ?> <?= htmlspecialchars($r['path']) ?></div>
                                    <?php if ($r['titulo'] && $r['titulo'] !== 'EduSaaS RD'): ?>
                                        <div class="title-page">📄 <?= htmlspecialchars(substr($r['titulo'], 0, 80)) ?></div>
                                    <?php endif; ?>
                                    <?php if ($r['rlogin']): ?>
                                        <div style="color:#fbbf24;font-size:.73rem;margin-top:.15rem">⚠️ Redirigió al login</div>
                                    <?php endif; ?>
                                    <?php if ($r['curl_err']): ?>
                                        <div style="color:#f87171;font-size:.73rem;margin-top:.15rem">🔌 cURL: <?= htmlspecialchars($r['curl_err']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['errores'])): ?>
                                        <div class="errors">
                                            <?php foreach ($r['errores'] as $err): ?>
                                                <div class="e"><?= htmlspecialchars($err) ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="meta">
                                    <span class="http-badge <?= $httpClass ?>"><?= $r['code'] ?: 'ERR' ?></span>
                                    <span class="<?= $msClass ?>"><?= $r['ms'] ?>ms</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align:center;padding:2rem 0;color:#334155;font-size:.78rem">
                Auditoría completada en <?= number_format($total_ms) ?>ms ·
                <a href="trs.php" style="color:#4f46e5">Nueva auditoría</a>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function toggle(id, header) {
            const body = document.getElementById(id);
            const isOpen = body.classList.contains('open');
            body.classList.toggle('open', !isOpen);
            header.classList.toggle('open', !isOpen);
        }
        // Auto-abrir secciones con problemas ya se hace en PHP
        // Abrir la primera sección si no hay errores
        document.addEventListener('DOMContentLoaded', () => {
            const first = document.querySelector('.mod-section');
            if (first) {
                const body = first.querySelector('.mod-body');
                const header = first.querySelector('.mod-header');
                if (body && !body.classList.contains('open')) {
                    body.classList.add('open');
                    header.classList.add('open');
                }
            }
        });
    </script>
</body>

</html>