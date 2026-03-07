<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($asunto ?? '') ?></title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:2rem 1rem">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:600px">

        <!-- ── Header ── -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a56db,#1240a8);padding:1.75rem 2rem;">
            <h1 style="margin:0;color:#fff;font-size:1.6rem;letter-spacing:-.5px;font-family:Georgia,serif">
              Edu<span style="color:#f59e0b">SaaS</span>
              <span style="font-size:.9rem;opacity:.75;font-family:'Segoe UI',Arial,sans-serif;font-weight:400"> RD</span>
            </h1>
            <p style="margin:.3rem 0 0;color:rgba(255,255,255,.65);font-size:.78rem">
              Sistema de Gestión Educativa &nbsp;🇩🇴
            </p>
          </td>
        </tr>

        <!-- ── Cuerpo (inyectado por cada plantilla) ── -->
        <tr>
          <td style="padding:2rem 2.25rem;color:#1e293b;font-size:.94rem;line-height:1.75">
            <?= $contenido ?>
          </td>
        </tr>

        <!-- ── Footer ── -->
        <tr>
          <td style="background:#f8fafc;padding:1.1rem 2rem;border-top:1px solid #e2e8f0;">
            <p style="margin:0;color:#94a3b8;font-size:.76rem;text-align:center;line-height:1.6">
              <strong style="color:#64748b">EduSaaS RD</strong> — Sistema de Gestión Educativa para República Dominicana<br>
              <a href="mailto:soporte@edusaas.do" style="color:#1a56db;text-decoration:none">soporte@edusaas.do</a>
              &nbsp;·&nbsp;
              Este correo fue generado automáticamente, por favor no respondas directamente.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
