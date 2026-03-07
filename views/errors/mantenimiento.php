<?php
// Vista de mantenimiento — sin layout, standalone
try {
    $_nombre = ConfigModel::get('marca_nombre_sistema', 'EduSaaS RD');
    $_email  = ConfigModel::get('empresa_email', 'soporte@edusaas.do');
    $_color  = ConfigModel::get('marca_color_primario', '#1a56db');
} catch (Exception $_e) {
    $_nombre = 'EduSaaS RD';
    $_email  = 'soporte@edusaas.do';
    $_color  = '#1a56db';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mantenimiento — <?= htmlspecialchars($_nombre) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #e2e8f0;
            padding: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: block;
            animation: spin 8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: .75rem;
            color: #f1f5f9;
        }

        p {
            font-size: .95rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .badge {
            display: inline-block;
            background: <?= htmlspecialchars($_color) ?>22;
            color: <?= htmlspecialchars($_color) ?>;
            border: 1px solid <?= htmlspecialchars($_color) ?>44;
            border-radius: 20px;
            padding: .35rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .contact {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, .08);
            font-size: .85rem;
            color: #64748b;
        }

        .contact a {
            color: <?= htmlspecialchars($_color) ?>;
            text-decoration: none;
        }

        .brand {
            font-size: .9rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="brand"><?= htmlspecialchars($_nombre) ?></div>
        <span class="icon">⚙️</span>
        <h1>Sistema en mantenimiento</h1>
        <span class="badge">🔧 Mantenimiento programado</span>
        <p>
            Estamos realizando mejoras en el sistema para ofrecerte una mejor experiencia.
            Estaremos de vuelta muy pronto.
        </p>
        <p>
            Disculpa las molestias. Gracias por tu paciencia.
        </p>
        <div class="contact">
            ¿Necesitas ayuda urgente? Contáctanos:<br>
            <a href="mailto:<?= htmlspecialchars($_email) ?>"><?= htmlspecialchars($_email) ?></a>
        </div>
    </div>
</body>

</html>