<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — EduSaaS RD</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a56db;
            --primary-dk: #1240a8;
            --accent: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            background: #0f172a;
            display: flex;
            align-items: stretch;
        }

        /* ── PANEL IZQUIERDO (decorativo) ── */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #1a56db 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(245,158,11,.12);
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
        }
        .login-left-content { position: relative; z-index: 1; }

        .brand-logo {
            font-family: 'DM Serif Display', serif;
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: .25rem;
        }
        .brand-logo span { color: var(--accent); }
        .brand-tagline {
            color: rgba(255,255,255,.6);
            font-size: .95rem;
            margin-bottom: 3rem;
        }

        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            color: rgba(255,255,255,.75);
            font-size: .875rem;
            padding: .5rem 0;
            display: flex;
            align-items: center;
            gap: .625rem;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list li i { color: var(--accent); font-size: 1rem; width: 18px; }

        .flag-badge {
            margin-top: 3rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(255,255,255,.08);
            border-radius: 50px;
            padding: .375rem .875rem;
            color: rgba(255,255,255,.7);
            font-size: .78rem;
        }

        /* ── PANEL DERECHO (formulario) ── */
        .login-right {
            width: 440px;
            flex-shrink: 0;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .25rem;
        }
        .login-subtitle {
            color: #64748b;
            font-size: .875rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .375rem;
        }
        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: .625rem .875rem;
            font-size: .875rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
            outline: none;
        }

        .input-group .form-control { border-right: none; }
        .input-group .btn-outline-secondary {
            border: 1.5px solid #e2e8f0;
            border-left: none;
            border-radius: 0 8px 8px 0;
            color: #94a3b8;
        }

        .btn-login {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: .75rem;
            font-size: .9rem;
            font-weight: 600;
            width: 100%;
            margin-top: .5rem;
            transition: background .15s, transform .1s;
        }
        .btn-login:hover {
            background: var(--primary-dk);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: .75rem 1rem;
            color: #b91c1c;
            font-size: .85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            color: #94a3b8;
            font-size: .75rem;
        }

        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

<!-- Panel Izquierdo -->
<div class="login-left">
    <div class="login-left-content">
        <div class="brand-logo">Edu<span>SaaS</span></div>
        <p class="brand-tagline">Sistema de Gestión Educativa</p>

        <ul class="feature-list">
            <li><i class="bi bi-people-fill"></i> Gestión de estudiantes y matrículas</li>
            <li><i class="bi bi-star-fill"></i> Calificaciones sistema MINERD (0-100)</li>
            <li><i class="bi bi-calendar-check-fill"></i> Control de asistencia diaria</li>
            <li><i class="bi bi-cash-stack"></i> Gestión de pagos y cuotas</li>
            <li><i class="bi bi-megaphone-fill"></i> Comunicados a padres</li>
            <li><i class="bi bi-bar-chart-fill"></i> Reportes y estadísticas</li>
        </ul>

        <div class="flag-badge">
            🇩🇴 Adaptado al sistema MINERD — República Dominicana
        </div>
    </div>
</div>

<!-- Panel Derecho (Formulario) -->
<div class="login-right">
    <h1 class="login-title">Bienvenido de nuevo</h1>
    <p class="login-subtitle">Inicia sesión para acceder al sistema.</p>

    <?php if (!empty($error)): ?>
    <div class="error-box">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php
    $appConfig = require __DIR__ . '/../../config/app.php';
    $urlBase   = $appConfig['url'];
    ?>

    <form action="<?= $urlBase ?>/auth/login" method="POST" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="mb-3">
            <label class="form-label" for="username">Usuario o correo</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                placeholder="usuario o correo@dominio.com"
                autocomplete="username"
                required
            >
        </div>

        <div class="mb-4">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                    <i class="bi bi-eye" id="eye-icon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
        </button>
    </form>

    <div class="login-footer">
        EduSaaS RD v1.0.0 &mdash; <?= date('Y') ?> &mdash; 🇩🇴
    </div>
</div>

<script>
    // Mostrar/ocultar contraseña
    document.getElementById('toggle-password').addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
</body>
</html>
