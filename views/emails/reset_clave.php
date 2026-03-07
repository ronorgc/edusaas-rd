<?php
// vars: $usuario (array), $nuevaClave (string), $inst (array)
?>
<p style="margin:0 0 1rem;color:#64748b;font-size:.85rem">
    <?= htmlspecialchars($inst['nombre']) ?> &nbsp;·&nbsp; <?= date('d/m/Y H:i') ?>
</p>

<div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;
            padding:1.5rem;text-align:center;margin-bottom:1.5rem">
    <div style="font-size:2.5rem;margin-bottom:.5rem">🔑</div>
    <h2 style="margin:0;color:#fff;font-size:1.2rem;font-weight:700">Contraseña restablecida</h2>
    <p style="margin:.3rem 0 0;color:rgba(255,255,255,.85);font-size:.85rem">
        Tu contraseña de acceso ha sido cambiada por un administrador.
    </p>
</div>

<p>
    Hola <strong><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></strong>,
</p>

<p>
    El administrador del sistema ha restablecido tu contraseña de acceso a
    <strong><?= htmlspecialchars($inst['nombre']) ?></strong>.
    Tus nuevas credenciales son:
</p>

<div style="background:#fefce8;border:2px dashed #fbbf24;border-radius:10px;
            padding:1.25rem 1.5rem;margin:1.5rem 0">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:.3rem 0;font-size:.88rem;color:#92400e;width:40%">
                <strong>Usuario:</strong>
            </td>
            <td style="padding:.3rem 0;font-family:monospace;font-size:.95rem;color:#1e293b;font-weight:700">
                <?= htmlspecialchars($usuario['username']) ?>
            </td>
        </tr>
        <tr>
            <td style="padding:.3rem 0;font-size:.88rem;color:#92400e">
                <strong>Nueva contraseña:</strong>
            </td>
            <td style="padding:.3rem 0;font-family:monospace;font-size:1.1rem;color:#b45309;
                       font-weight:700;letter-spacing:.05em">
                <?= htmlspecialchars($nuevaClave) ?>
            </td>
        </tr>
    </table>
</div>

<div style="background:#fef2f2;border-left:4px solid #ef4444;border-radius:0 8px 8px 0;
            padding:1rem 1.25rem;margin-bottom:1.5rem">
    <p style="margin:0;font-size:.88rem;color:#991b1b;font-weight:600">
        ⚠️ Por seguridad, cambia tu contraseña después de iniciar sesión.
    </p>
</div>

<p style="font-size:.88rem;color:#64748b">
    Si no solicitaste este cambio o tienes dudas, contacta al administrador de tu institución.
</p>

<p style="font-size:.88rem;color:#64748b;margin-top:1.5rem">
    Atentamente,<br>
    <strong><?= htmlspecialchars($inst['nombre']) ?></strong>
</p>
