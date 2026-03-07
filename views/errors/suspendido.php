<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Suspendido — EduSaaS RD</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: #1e293b; border-radius: 16px; padding: 3rem; max-width: 480px; text-align: center; border: 1px solid #334155; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #f1f5f9; font-size: 1.5rem; margin-bottom: .5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        .motivo { background: #0f172a; border-radius: 8px; padding: 1rem; margin: 1.5rem 0; color: #fbbf24; font-size: .9rem; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔒</div>
        <h1>Acceso Suspendido</h1>
        <p>El acceso a este sistema ha sido temporalmente suspendido.</p>
        <div class="motivo">
            <?= htmlspecialchars($motivo ?? 'Contacta al equipo de EduSaaS RD para más información.') ?>
        </div>
        <p style="font-size:.85rem">
            ¿Necesitas ayuda? Escríbenos a
            <a href="mailto:soporte@edusaas.do">soporte@edusaas.do</a>
        </p>
    </div>
</body>
</html>
