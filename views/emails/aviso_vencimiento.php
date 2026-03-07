<?php
// ── Variables disponibles: $inst, $susc, $diasRestantes, $color, $emoji, $msgDias, $fechaFmt ──
?>
<h2 style="margin:0 0 1rem;color:<?= $color ?>;font-size:1.3rem">
    <?= $emoji ?> Tu suscripción <?= $msgDias ?>
</h2>

<p>Hola, <strong><?= htmlspecialchars($inst['nombre']) ?></strong></p>

<p>Te recordamos que tu suscripción al plan
   <strong><?= htmlspecialchars($susc['plan_nombre']) ?></strong>
   <?= $msgDias ?>.
   Una vez vencida, el acceso al sistema quedará bloqueado automáticamente.</p>

<!-- Detalle del plan -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="background:#fff7ed;border-radius:10px;border-left:4px solid <?= $color ?>;
              margin:1.5rem 0;overflow:hidden">
  <tr>
    <td style="padding:1.25rem 1.5rem">
      <p style="margin:0 0 .75rem;font-weight:700;color:<?= $color ?>">
          📋 Detalles de tu suscripción
      </p>
      <table cellpadding="5" cellspacing="0">
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1rem">Plan</td>
          <td style="font-weight:600"><?= htmlspecialchars($susc['plan_nombre']) ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1rem">Vence</td>
          <td style="font-weight:600;color:<?= $color ?>"><?= $fechaFmt ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1rem">Monto</td>
          <td style="font-weight:600">RD$<?= number_format($susc['monto'], 2) ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- CTA renovar -->
<table width="100%" cellpadding="0" cellspacing="0" style="margin:1.5rem 0">
  <tr>
    <td style="background:#1a56db;border-radius:8px;text-align:center;padding:.875rem">
      <a href="mailto:soporte@edusaas.do?subject=Renovaci%C3%B3n%20de%20suscripci%C3%B3n%20-%20<?= urlencode($inst['nombre']) ?>"
         style="color:#fff;font-weight:700;text-decoration:none;font-size:.95rem">
        💳 Quiero renovar mi suscripción →
      </a>
    </td>
  </tr>
</table>

<p style="color:#64748b;font-size:.85rem">
    También puedes contactarnos directamente a
    <a href="mailto:soporte@edusaas.do" style="color:#1a56db">soporte@edusaas.do</a>.
    Si ya realizaste el pago, por favor ignora este mensaje.
</p>
