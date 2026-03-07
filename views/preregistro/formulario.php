<?php
// Esta vista se renderiza en main.php pero es acceso público (sin sidebar de admin)
// El layout detectará que no hay sesión y mostrará versión simplificada.
$appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
try {
    $_nombre_sistema = ConfigModel::get('marca_nombre_sistema', 'EduSaaS RD');
    $_color          = ConfigModel::get('marca_color_primario', '#1a56db');
    $_email_soporte  = ConfigModel::get('empresa_email', 'soporte@edusaas.do');
} catch (Exception $_e) {
    $_nombre_sistema = 'EduSaaS RD';
    $_color          = '#1a56db';
    $_email_soporte  = 'soporte@edusaas.do';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitar acceso — <?= htmlspecialchars($_nombre_sistema) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f6ff; }
        .hero {
            background: linear-gradient(135deg, <?= htmlspecialchars($_color) ?>, #1e3a8a);
            color: #fff;
            padding: 3rem 1rem 2rem;
            text-align: center;
        }
        .hero h1  { font-size: 2rem; font-weight: 800; }
        .hero p   { opacity: .85; max-width: 520px; margin: .75rem auto 0; }
        .card-form { border-radius: 16px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .paso { width: 28px; height: 28px; border-radius: 50%; background: <?= htmlspecialchars($_color) ?>22;
                color: <?= htmlspecialchars($_color) ?>; font-weight: 700; font-size: .85rem;
                display:inline-flex; align-items:center; justify-content:center; margin-right: .5rem; }
        .section-title { font-size: .7rem; font-weight: 700; text-transform: uppercase;
                         letter-spacing: .1em; color: #94a3b8; margin: 1.5rem 0 .75rem; }
    </style>
</head>
<body>

<div class="hero">
    <h1><?= htmlspecialchars($_nombre_sistema) ?></h1>
    <p>Completa este formulario para solicitar acceso a la plataforma. Revisaremos tu solicitud y te contactaremos en menos de 24 horas.</p>
</div>

<div class="container py-4" style="max-width:680px">

    <?php foreach (['success','error','warning','info'] as $t):
        if ($msg = ($_SESSION['flash'][$t] ?? '')): ?>
    <div class="alert alert-<?= $t === 'error' ? 'danger' : $t ?> alert-dismissible">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash'][$t]); endif; endforeach; ?>

    <div class="card card-form p-4">
        <form action="<?= $appUrl ?>/registro" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Datos del colegio -->
            <div class="section-title"><span class="paso">1</span> Datos del colegio</div>
            <div class="row g-3 mb-2">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nombre del colegio <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required
                           placeholder="Colegio San José del Sur">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="privado">Privado</option>
                        <option value="publico">Público</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Código MINERD</label>
                    <input type="text" name="codigo_minerd" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Municipio</label>
                    <input type="text" name="municipio" class="form-control" placeholder="Santo Domingo">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Provincia</label>
                    <input type="text" name="provincia" class="form-control" placeholder="D.N.">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Estudiantes aprox.</label>
                    <select name="cant_estudiantes" class="form-select">
                        <option value="">No sé / Prefiero no decir</option>
                        <option value="50">Menos de 50</option>
                        <option value="150">50 – 150</option>
                        <option value="300">150 – 300</option>
                        <option value="500">300 – 500</option>
                        <option value="999">Más de 500</option>
                    </select>
                </div>
            </div>

            <!-- Contacto -->
            <div class="section-title"><span class="paso">2</span> Datos de contacto</div>
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nombre del director / contacto <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_director" class="form-control" required
                           placeholder="Juan Pérez">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Cargo</label>
                    <input type="text" name="cargo_contacto" class="form-control"
                           placeholder="Director, Administrador...">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email institucional <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           placeholder="director@colegio.do">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" placeholder="809-000-0000">
                </div>
            </div>

            <!-- Plan de interés -->
            <?php if (!empty($planes)): ?>
            <div class="section-title"><span class="paso">3</span> Plan de interés (opcional)</div>
            <div class="row g-3 mb-2">
                <?php foreach ($planes as $pl): ?>
                <div class="col-md-4">
                    <input type="radio" class="btn-check" name="plan_interes"
                           id="plan_<?= $pl['id'] ?>" value="<?= $pl['id'] ?>">
                    <label class="btn btn-outline-secondary w-100 text-start p-3"
                           for="plan_<?= $pl['id'] ?>"
                           style="border-color: <?= htmlspecialchars($pl['color']) ?>33">
                        <div class="fw-bold" style="color:<?= htmlspecialchars($pl['color']) ?>">
                            <i class="bi <?= htmlspecialchars($pl['icono']) ?> me-1"></i>
                            <?= htmlspecialchars($pl['nombre']) ?>
                        </div>
                        <div class="small text-muted mt-1">
                            RD$<?= number_format($pl['precio_mensual'], 0) ?>/mes
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Mensaje -->
            <div class="section-title"><span class="paso"><?= !empty($planes) ? '4' : '3' ?></span> Mensaje (opcional)</div>
            <textarea name="mensaje" class="form-control mb-4" rows="3"
                      placeholder="Cuéntanos un poco más sobre tu colegio o cualquier pregunta que tengas..."></textarea>

            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-lock-fill me-1"></i>
                    Tus datos están seguros y no serán compartidos.
                </div>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-send me-2"></i>Enviar solicitud
                </button>
            </div>
        </form>
    </div>

    <div class="text-center text-muted small mt-3">
        ¿Ya tienes cuenta? <a href="<?= $appUrl ?>/auth/login">Inicia sesión aquí</a>
        &nbsp;·&nbsp;
        ¿Preguntas? <a href="mailto:<?= htmlspecialchars($_email_soporte) ?>"><?= htmlspecialchars($_email_soporte) ?></a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php exit; // No pasar por main.php layout ?>