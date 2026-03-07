<?php
// ── Variables disponibles: $inst, $asunto, $mensaje ──
?>
<h2 style="margin:0 0 1rem;color:#1a56db;font-size:1.3rem">
    <?= htmlspecialchars($asunto) ?>
</h2>

<p>Hola, <strong><?= htmlspecialchars($inst['nombre']) ?></strong></p>

<div style="color:#1e293b;line-height:1.8;white-space:pre-line"><?= htmlspecialchars($mensaje) ?></div>

<p style="margin-top:1.75rem;color:#64748b;font-size:.88rem">
  Si tienes alguna pregunta, responde a este correo o escríbenos a
  <a href="mailto:soporte@edusaas.do" style="color:#1a56db">soporte@edusaas.do</a>.
</p>
