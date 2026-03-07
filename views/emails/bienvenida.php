<?php
// ── Variables disponibles: $inst, $username, $password ──
?>
<h2 style="margin:0 0 1rem;color:#1a56db;font-size:1.3rem">
    ¡Bienvenido a EduSaaS RD! 🎉
</h2>

<p>Hola, <strong><?= htmlspecialchars($inst['nombre']) ?></strong></p>

<p>Tu institución ha sido registrada exitosamente en nuestro sistema.
   A partir de ahora puedes gestionar estudiantes, calificaciones,
   asistencia y más desde un solo lugar.</p>

<!-- Credenciales -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="background:#f0f6ff;border-radius:10px;border-left:4px solid #1a56db;
              padding:0;margin:1.5rem 0;overflow:hidden">
  <tr>
    <td style="padding:1.25rem 1.5rem">
      <p style="margin:0 0 .75rem;font-weight:700;color:#1a56db">
        🔐 Tus credenciales de acceso
      </p>
      <table cellpadding="4" cellspacing="0">
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:.75rem">🌐 URL</td>
          <td style="font-weight:600">
            <?php
              $appUrl = (require __DIR__ . '/../../config/app.php')['url'];
              $loginUrl = $appUrl . '/auth/login';
            ?>
            <a href="<?= htmlspecialchars($loginUrl) ?>"
               style="color:#1a56db;text-decoration:none">
              <?= htmlspecialchars($loginUrl) ?>
            </a>
          </td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:.75rem">👤 Usuario</td>
          <td style="font-weight:600;font-family:monospace,monospace"><?= htmlspecialchars($username) ?></td>
        </tr>
        <tr>
          <td style="color:#64748b;font-size:.88rem;white-space:nowrap;padding-right:.75rem">🔑 Contraseña</td>
          <td style="font-weight:600;font-family:monospace,monospace"><?= htmlspecialchars($password) ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- Aviso de seguridad -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="background:#fef9c3;border-radius:8px;border-left:4px solid #f59e0b;margin-bottom:1.25rem">
  <tr>
    <td style="padding:1rem 1.25rem;font-size:.88rem;color:#854d0e">
      <strong>⚠️ Importante:</strong> Por seguridad, cambia tu contraseña la primera vez
      que inicies sesión. Ve a tu perfil → Cambiar contraseña.
    </td>
  </tr>
</table>

<p style="margin-bottom:.5rem">
  Si tienes alguna duda durante la configuración, estamos aquí para ayudarte:
</p>
<p>
  📧 <a href="mailto:soporte@edusaas.do" style="color:#1a56db">soporte@edusaas.do</a>
</p>

<p style="margin-top:1.5rem;color:#64748b;font-size:.88rem">
  Gracias por confiar en EduSaaS RD. 🙏
</p>