<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitud recibida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="text-center p-4" style="max-width:480px">
    <div style="font-size:4rem" class="mb-3">🎉</div>
    <h1 class="fw-bold mb-2">¡Solicitud recibida!</h1>
    <p class="text-muted">
        Hemos recibido tu solicitud correctamente. Revisaremos los datos y nos pondremos en contacto contigo
        en menos de <strong>24 horas hábiles</strong>.
    </p>
    <div class="alert alert-info mt-3 text-start small">
        <i class="bi bi-envelope me-1"></i>
        Recibirás un email de confirmación. Si no lo ves, revisa tu carpeta de spam.<br>
        <strong>Soporte:</strong> <a href="mailto:<?= htmlspecialchars($email_soporte) ?>"><?= htmlspecialchars($email_soporte) ?></a>
    </div>
</div>
</body>
</html>
<?php exit; ?>