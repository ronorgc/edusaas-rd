<?php
// vars: $sol (array preinscripcion), $inst (array institucion), $codigo (string EST-XXXXX)
?>
<p style="margin:0 0 1rem;color:#64748b;font-size:.85rem">
    <?= htmlspecialchars($inst['nombre']) ?> &nbsp;·&nbsp; <?= date('d/m/Y') ?>
</p>

<!-- Encabezado verde -->
<div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;
            padding:1.5rem;text-align:center;margin-bottom:1.5rem">
    <div style="font-size:2.5rem;margin-bottom:.5rem">✅</div>
    <h2 style="margin:0;color:#fff;font-size:1.3rem;font-weight:700">¡Solicitud Aprobada!</h2>
    <p style="margin:.4rem 0 0;color:rgba(255,255,255,.85);font-size:.88rem">
        La pre-inscripción ha sido revisada y aceptada.
    </p>
</div>

<p>
    Estimado/a <strong><?= htmlspecialchars($sol['tutor_nombres'] . ' ' . $sol['tutor_apellidos']) ?></strong>,
</p>

<p>
    Nos complace informarle que la solicitud de pre-inscripción de
    <strong><?= htmlspecialchars($sol['nombres'] . ' ' . $sol['apellidos']) ?></strong>
    en <strong><?= htmlspecialchars($inst['nombre']) ?></strong> ha sido
    <span style="color:#10b981;font-weight:700">aprobada</span>.
</p>

<!-- Código del estudiante -->
<div style="background:#f0fdf4;border:2px dashed #86efac;border-radius:10px;
            padding:1.25rem;text-align:center;margin:1.5rem 0">
    <p style="margin:0 0 .3rem;font-size:.75rem;font-weight:700;color:#16a34a;
              text-transform:uppercase;letter-spacing:.08em">
        Código del Estudiante
    </p>
    <p style="margin:0;font-family:monospace;font-size:1.5rem;font-weight:700;color:#15803d">
        <?= htmlspecialchars($codigo) ?>
    </p>
    <p style="margin:.4rem 0 0;font-size:.78rem;color:#6b7280">
        Guarde este código — lo necesitará para trámites en el colegio.
    </p>
</div>

<!-- Datos confirmados -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:1.5rem">
    <tr style="background:#f8fafc">
        <td colspan="2" style="padding:.65rem 1rem;font-size:.78rem;font-weight:700;
                               color:#64748b;text-transform:uppercase;letter-spacing:.05em">
            Datos confirmados
        </td>
    </tr>
    <?php
    $filas = [
        ['Estudiante',    $sol['nombres'] . ' ' . $sol['apellidos']],
        ['Código',        $codigo],
        ['Colegio',       $inst['nombre']],
        ['Grado asignado', $sol['grado_solicitado'] ?: 'Pendiente de asignación'],
    ];
    foreach ($filas as $i => [$k, $v]):
        $bg = $i % 2 === 0 ? '#fff' : '#f8fafc';
    ?>
    <tr style="background:<?= $bg ?>">
        <td style="padding:.6rem 1rem;font-size:.88rem;color:#64748b;width:45%"><?= $k ?></td>
        <td style="padding:.6rem 1rem;font-size:.88rem;font-weight:600;color:#0f172a"><?= htmlspecialchars($v) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Próximos pasos -->
<div style="background:#eff6ff;border-left:4px solid #1a56db;border-radius:0 8px 8px 0;
            padding:1rem 1.25rem;margin-bottom:1.5rem">
    <p style="margin:0 0 .5rem;font-weight:700;color:#1e40af;font-size:.9rem">
        📋 Próximos pasos
    </p>
    <ul style="margin:0;padding-left:1.25rem;font-size:.88rem;color:#374151;line-height:2">
        <li>Presentarse en la secretaría del colegio con la cédula del tutor.</li>
        <li>Completar el proceso de matrícula y firmar el contrato.</li>
        <li>Realizar el pago de matrícula correspondiente.</li>
        <li>Retirar el uniforme y lista de útiles escolares.</li>
    </ul>
</div>

<?php if ($inst['telefono'] || $inst['email']): ?>
<p style="font-size:.88rem;color:#64748b">
    Para más información puede contactar al colegio:
    <?php if ($inst['telefono']): ?>
    <a href="tel:<?= htmlspecialchars($inst['telefono']) ?>"
       style="color:#1a56db"><?= htmlspecialchars($inst['telefono']) ?></a>
    <?php endif; ?>
    <?php if ($inst['email']): ?>
    · <a href="mailto:<?= htmlspecialchars($inst['email']) ?>"
         style="color:#1a56db"><?= htmlspecialchars($inst['email']) ?></a>
    <?php endif; ?>
</p>
<?php endif; ?>

<p style="font-size:.88rem;color:#64748b;margin-top:1.5rem">
    Atentamente,<br>
    <strong><?= htmlspecialchars($inst['nombre']) ?></strong>
</p>
