<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitud Enviada — <?= htmlspecialchars($preinsc['inst_nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 2rem 1rem;
  }
  .confirm-card {
    background: #fff;
    border-radius: 24px;
    max-width: 560px;
    width: 100%;
    padding: 3rem 2.5rem;
    text-align: center;
    box-shadow: 0 30px 80px rgba(0,0,0,.45);
  }
  .check-anim {
    width: 90px; height: 90px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2.5rem; color: #fff;
    margin-bottom: 1.5rem;
    animation: popIn .5s cubic-bezier(.175,.885,.32,1.275);
    box-shadow: 0 8px 30px rgba(16,185,129,.35);
  }
  @keyframes popIn {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
  }
  h1 { font-family: 'DM Serif Display', serif; font-size: 1.75rem; color: #0f172a; }
  .token-box {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
  }
  .token-label { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .08em; }
  .token-value { font-family: monospace; font-size: 1.1rem; color: #1a56db; font-weight: 700; word-break: break-all; }
  .steps-list { text-align: left; }
  .steps-list li { padding: .4rem 0; font-size: .9rem; color: #475569; }
  .steps-list li i { color: #10b981; margin-right: .5rem; }
</style>
</head>
<body>
<div class="confirm-card">
  <div class="check-anim"><i class="bi bi-check-lg"></i></div>

  <h1>¡Solicitud Enviada!</h1>
  <p class="text-muted mt-2">
    La pre-inscripción de <strong><?= htmlspecialchars($preinsc['nombres'].' '.$preinsc['apellidos']) ?></strong>
    en <strong><?= htmlspecialchars($preinsc['inst_nombre']) ?></strong> fue recibida correctamente.
  </p>

  <div class="token-box">
    <div class="token-label"><i class="bi bi-ticket-perforated me-1"></i>Código de seguimiento</div>
    <div class="token-value mt-1"><?= htmlspecialchars($preinsc['token']) ?></div>
    <div class="text-muted small mt-1">Guarda este código — lo necesitarás para consultar el estado.</div>
  </div>

  <ul class="steps-list list-unstyled mt-3">
    <li><i class="bi bi-check-circle-fill"></i>Documentos recibidos y almacenados de forma segura.</li>
    <li><i class="bi bi-clock-history text-warning"></i>El equipo del colegio revisará tu solicitud en los próximos días hábiles.</li>
    <li><i class="bi bi-envelope-fill text-primary"></i>Recibirás una notificación en <strong><?= htmlspecialchars($preinsc['tutor_email']) ?></strong>.</li>
  </ul>

  <p class="text-muted small mt-4">
    ¿Tienes dudas? Contacta directamente al colegio con tu código de seguimiento.
  </p>
</div>
</body>
</html>
