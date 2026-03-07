<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Solicitud Enviada — <?= htmlspecialchars($inst['nombre']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{background:#f0f4ff;font-family:'Segoe UI',sans-serif;min-height:100vh;
       display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem}
  .card-ok{background:#fff;border-radius:20px;box-shadow:0 4px 32px rgba(0,0,0,.1);
            padding:3rem 2.5rem;max-width:560px;width:100%;text-align:center;border:1px solid #e2e8f0}
  .check-icon{width:80px;height:80px;background:#dcfce7;border-radius:50%;
              display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;
              font-size:2.5rem;color:#16a34a}
  .codigo-box{background:#f0f4ff;border:2px solid #1a56db;border-radius:12px;
              padding:1rem 1.5rem;display:inline-block;margin:1rem 0}
  .codigo-box span{font-family:monospace;font-size:1.6rem;font-weight:800;color:#1a56db;
                   letter-spacing:.1em}
  .info-row{display:flex;justify-content:space-between;padding:.5rem 0;
            border-bottom:1px solid #f1f5f9;font-size:.88rem}
  .info-row:last-child{border-bottom:none}
</style>
</head>
<body>
<div class="card-ok">
  <div class="check-icon"><i class="bi bi-check-lg"></i></div>
  <h1 style="font-size:1.6rem;font-weight:800;color:#1e293b;margin-bottom:.5rem">
    ¡Solicitud enviada!
  </h1>
  <p class="text-muted mb-0">Su preinscripción fue recibida correctamente.</p>
  <p class="text-muted">Guarde el número de solicitud para darle seguimiento.</p>

  <div class="codigo-box">
    <div style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">
      Número de solicitud
    </div>
    <span><?= htmlspecialchars($pre['codigo_solicitud']) ?></span>
  </div>

  <div class="mt-3 text-start">
    <div class="info-row">
      <span class="text-muted">Estudiante</span>
      <span class="fw-semibold"><?= htmlspecialchars($pre['nombres'].' '.$pre['apellidos']) ?></span>
    </div>
    <div class="info-row">
      <span class="text-muted">Grado solicitado</span>
      <span class="fw-semibold"><?= htmlspecialchars($pre['grado_nombre'] ?? '—') ?></span>
    </div>
    <div class="info-row">
      <span class="text-muted">Contacto</span>
      <span class="fw-semibold"><?= htmlspecialchars($pre['tutor_email']) ?></span>
    </div>
    <div class="info-row">
      <span class="text-muted">Fecha de envío</span>
      <span class="fw-semibold"><?= date('d/m/Y H:i', strtotime($pre['created_at'])) ?></span>
    </div>
    <div class="info-row">
      <span class="text-muted">Estado</span>
      <span class="badge" style="background:#fef9c3;color:#92400e;font-size:.8rem;padding:.3rem .7rem;border-radius:20px">
        ⏳ En revisión
      </span>
    </div>
  </div>

  <div class="alert alert-info mt-4 text-start" style="font-size:.85rem">
    <i class="bi bi-envelope-fill me-2"></i>
    <strong>¿Qué sigue?</strong> El equipo del colegio revisará su solicitud y la documentación
    enviada. Recibirá una respuesta al correo <strong><?= htmlspecialchars($pre['tutor_email']) ?></strong>
    en un plazo de 2 a 5 días hábiles.
  </div>

  <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-printer me-1"></i> Imprimir comprobante
    </button>
  </div>
  <div class="text-muted mt-3" style="font-size:.75rem">
    <?= htmlspecialchars($inst['nombre']) ?> · <?= $inst['telefono'] ?? '' ?>
  </div>
</div>
</body>
</html>
