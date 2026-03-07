<?php
// ── Variables disponibles: $inst, $motivo ──
?>
<h2 style="margin:0 0 1rem;color:#ef4444;font-size:1.3rem">
    🔒 Acceso suspendido temporalmente
</h2>

<p>Hola, <strong><?= htmlspecialchars($inst['nombre']) ?></strong></p>

<p>El acceso de tu institución al Sistema EduSaaS RD ha sido
   <strong>suspendido de forma temporal</strong>.</p>

<!-- Motivo -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="background:#fef2f2;border-radius:10px;border-left:4px solid #ef4444;
              margin:1.5rem 0;overflow:hidden">
  <tr>
    <td style="padding:1.1rem 1.5rem">
      <p style="margin:0 0 .4rem;font-weight:700;color:#b91c1c;font-size:.88rem;
                text-transform:uppercase;letter-spacing:.5px">Motivo</p>
      <p style="margin:0;color:#7f1d1d"><?= htmlspecialchars($motivo) ?></p>
    </td>
  </tr>
</table>

<p>Para reactivar el acceso a tu sistema, contáctanos lo antes posible:</p>

<table cellpadding="0" cellspacing="0" style="margin:1rem 0">
  <tr>
    <td style="padding:.35rem 0;color:#64748b;font-size:.9rem">
      📧 &nbsp;<a href="mailto:soporte@edusaas.do"
                 style="color:#1a56db;font-weight:600;text-decoration:none">soporte@edusaas.do</a>
    </td>
  </tr>
</table>

<p style="color:#64748b;font-size:.88rem;margin-top:1.25rem">
  Una vez regularizada la situación, tu acceso será reactivado de inmediato
  y podrás continuar usando el sistema sin pérdida de datos.
</p>
