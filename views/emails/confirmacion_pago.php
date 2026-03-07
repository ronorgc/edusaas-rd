<?php
// ── Variables disponibles: $inst, $pago, $susc, $fechaPago, $fechaHasta ──
?>
<h2 style="margin:0 0 1rem;color:#10b981;font-size:1.3rem">
    ✅ Pago confirmado
</h2>

<p>Hola, <strong><?= htmlspecialchars($inst['nombre']) ?></strong></p>

<p>Hemos registrado tu pago exitosamente.
   Tu suscripción ha sido renovada y el acceso al sistema está activo.</p>

<!-- Resumen del pago -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="background:#f0fdf4;border-radius:10px;border-left:4px solid #10b981;
              margin:1.5rem 0;overflow:hidden">
  <tr>
    <td style="padding:1.25rem 1.5rem">
      <p style="margin:0 0 .75rem;font-weight:700;color:#10b981">
        🧾 Resumen del pago
      </p>
      <table cellpadding="5" cellspacing="0">
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Factura</td>
          <td style="font-weight:600;font-family:monospace,monospace">
            <?= htmlspecialchars($pago['numero_factura']) ?>
          </td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Plan</td>
          <td style="font-weight:600"><?= htmlspecialchars($susc['plan_nombre'] ?? '—') ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Monto pagado</td>
          <td style="font-weight:700;font-size:1.05rem">RD$<?= number_format($pago['monto'], 2) ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Método</td>
          <td><?= ucfirst($pago['metodo_pago']) ?></td>
        </tr>
        <?php if (!empty($pago['referencia'])): ?>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Referencia</td>
          <td><?= htmlspecialchars($pago['referencia']) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Fecha de pago</td>
          <td><?= $fechaPago ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:1.25rem">Activo hasta</td>
          <td style="font-weight:700;color:#10b981;font-size:1rem"><?= $fechaHasta ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- Banner activo -->
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:1.25rem">
  <tr>
    <td style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;
               text-align:center;padding:.875rem;color:#166534;font-weight:600;font-size:.92rem">
      🎉 Tu sistema está activo y listo para usar
    </td>
  </tr>
</table>

<p style="color:#64748b;font-size:.88rem">
  Guarda este correo como comprobante de pago.
  Si tienes alguna duda, escríbenos a
  <a href="mailto:soporte@edusaas.do" style="color:#1a56db">soporte@edusaas.do</a>.
</p>

<p style="margin-top:1.25rem">¡Gracias por tu confianza en EduSaaS RD! 🙏</p>
