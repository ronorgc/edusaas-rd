<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>404 - Página no encontrada</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { text-align: center; }
        .code { font-size: 6rem; font-weight: 700; color: #1a56db; line-height: 1; }
        h1 { font-size: 1.5rem; color: #0f172a; margin: .5rem 0; }
        p { color: #64748b; }
        a { color: #1a56db; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">404</div>
        <h1>Página no encontrada</h1>
        <p><?= htmlspecialchars($mensaje ?? 'La página que buscas no existe.') ?></p>
        <a href="javascript:history.back()">← Volver atrás</a>
    </div>
</body>
</html>
