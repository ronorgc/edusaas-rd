<?php
$appUrl  = (require __DIR__ . '/../../../../config/app.php')['url'];
// Leer config SMTP actual del .env
$mailCfg  = file_exists(__DIR__ . '/../../../../config/mail.php')
    ? require __DIR__ . '/../../../../config/mail.php'
    : [];
$smtpHost = $mailCfg['smtp']['host']       ?? '';
$smtpPort = $mailCfg['smtp']['port']       ?? 587;
$smtpEnc  = $mailCfg['smtp']['encryption'] ?? 'tls';
$smtpUser = $mailCfg['smtp']['username']   ?? '';
$smtpPass = $mailCfg['smtp']['password']   ?? '';
$driver   = $mailCfg['driver']             ?? 'mail';
$configured = $driver === 'smtp' && !empty($smtpUser) && !empty($smtpPass);
?>

<!-- Banner de estado SMTP -->
<div class="alert <?= $configured ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi <?= $configured ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
    <div class="flex-grow-1">
        <?php if ($configured): ?>
            <strong>SMTP configurado</strong> — Driver: <code>smtp</code> ·
            Servidor: <code><?= htmlspecialchars($smtpHost) ?>:<?= $smtpPort ?></code> ·
            Usuario: <code><?= htmlspecialchars($smtpUser) ?></code>
        <?php elseif ($driver === 'log'): ?>
            <strong>Modo LOG activo</strong> — Los correos no se envían, solo se registran en
            <code>storage/logs/mail.log</code>
        <?php else: ?>
            <strong>SMTP no configurado</strong> — Los correos usan <code>mail()</code> nativo
            (no funciona en XAMPP sin Mercury). Configura SMTP abajo.
        <?php endif; ?>
    </div>
    <button class="btn btn-sm <?= $configured ? 'btn-outline-success' : 'btn-outline-warning' ?>"
            type="button" data-bs-toggle="collapse" data-bs-target="#smtpPanel">
        <i class="bi bi-gear me-1"></i>Configurar SMTP
    </button>
</div>

<!-- Panel colapsable de configuración SMTP -->
<div class="collapse mb-4" id="smtpPanel">
<div class="card border-primary">
    <div class="card-header fw-semibold text-primary">
        <i class="bi bi-envelope-at me-2"></i>Configuración SMTP — Email
    </div>
    <div class="card-body">
        <form action="<?= $appUrl ?>/superadmin/notificaciones/smtp-guardar" method="POST" class="row g-3">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="col-md-4">
                <label class="form-label fw-semibold small">Driver</label>
                <select name="driver" class="form-select form-select-sm" id="driverSelect"
                        onchange="toggleSmtpFields(this.value)">
                    <option value="smtp"  <?= $driver==='smtp' ?'selected':'' ?>>SMTP (recomendado)</option>
                    <option value="mail"  <?= $driver==='mail' ?'selected':'' ?>>mail() nativo PHP</option>
                    <option value="log"   <?= $driver==='log'  ?'selected':'' ?>>Log (sin envío)</option>
                </select>
            </div>

            <div id="smtpFields" class="col-12 row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Servidor SMTP</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="smtp_host" class="form-control"
                               value="<?= htmlspecialchars($smtpHost) ?>" placeholder="smtp.gmail.com">
                        <input type="number" name="smtp_port" class="form-control" style="max-width:90px"
                               value="<?= $smtpPort ?>" placeholder="587">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Cifrado</label>
                    <select name="smtp_encryption" class="form-select form-select-sm">
                        <option value="tls" <?= $smtpEnc==='tls' ?'selected':'' ?>>TLS (587)</option>
                        <option value="ssl" <?= $smtpEnc==='ssl' ?'selected':'' ?>>SSL (465)</option>
                        <option value=""    <?= $smtpEnc===''    ?'selected':'' ?>>Ninguno</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Usuario SMTP</label>
                    <input type="email" name="smtp_user" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($smtpUser) ?>" placeholder="tu@gmail.com"
                           autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Contraseña / App Password</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="smtp_password" id="smtpPass" class="form-control"
                               value="<?= htmlspecialchars($smtpPass) ?>"
                               placeholder="<?= $smtpPass ? '(guardada)' : 'xxxx xxxx xxxx xxxx' ?>"
                               autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePass()"><i class="bi bi-eye" id="eyeIcon"></i></button>
                    </div>
                    <div class="form-text">Gmail: usa <strong>Contraseña de aplicación</strong>, no tu clave normal.</div>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save me-1"></i>Guardar configuración
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalTestEmail">
                    <i class="bi bi-send me-1"></i>Enviar email de prueba
                </button>
                <a href="https://myaccount.google.com/apppasswords" target="_blank"
                   class="btn btn-outline-danger btn-sm ms-auto">
                    <i class="bi bi-google me-1"></i>Crear App Password Gmail
                </a>
            </div>
        </form>

        <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;font-size:.8rem">
            <strong>Guía rápida Gmail:</strong>
            Activa <em>Verificación en 2 pasos</em> →
            <a href="https://myaccount.google.com/apppasswords" target="_blank">Contraseñas de aplicaciones</a> →
            crea una para "EduSaaS" → copia los 16 dígitos aquí.
            El campo "Usuario" debe ser tu cuenta de Gmail completa.
        </div>
    </div>
</div>
</div>

<!-- Modal test email -->
<div class="modal fade" id="modalTestEmail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="<?= $appUrl ?>/superadmin/notificaciones/smtp-test" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-send-fill me-2 text-primary"></i>Prueba de Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Envía un correo de prueba para verificar que la configuración SMTP funciona.</p>
                <label class="form-label fw-semibold">Enviar a</label>
                <input type="email" name="email_prueba" class="form-control" required
                       placeholder="tu@correo.com" value="<?= htmlspecialchars($smtpUser) ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Enviar prueba
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSmtpFields(val) {
    document.getElementById('smtpFields').style.display = val === 'smtp' ? '' : 'none';
}
function togglePass() {
    const el = document.getElementById('smtpPass');
    const ic = document.getElementById('eyeIcon');
    el.type = el.type === 'password' ? 'text' : 'password';
    ic.className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
// Mostrar panel si hay error de email
<?php if (str_contains($_SESSION['flash']['mensaje'] ?? '', 'Error al enviar')): ?>
document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Collapse(document.getElementById('smtpPanel'), {show: true});
});
<?php endif; ?>
// Toggle inicial
toggleSmtpFields(document.getElementById('driverSelect').value);
</script>

<?php
$tipoLabels = [
    'vencimiento_7dias' => '⚠️ Vence en 7 días',
    'vencimiento_3dias' => '🔴 Vence en 3 días',
    'vencimiento_hoy'   => '🚨 Vence hoy',
    'plan_vencido'      => '❌ Plan vencido',
    'plan_renovado'     => '✅ Plan renovado',
    'bienvenida'        => '👋 Bienvenida',
    'suspension'        => '🔒 Suspensión',
    'personalizado'     => '✉️ Personalizado',
];
?>

<div class="row g-3 mb-4">
    <!-- Stats -->
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe"><i class="bi bi-envelope-fill text-primary fs-4"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Correos últimos 30 días</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7"><i class="bi bi-check-circle-fill fs-4" style="color:#16a34a"></i></div>
            <div>
                <div class="stat-value"><?= $stats['enviados'] ?></div>
                <div class="stat-label">Enviados correctamente</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2"><i class="bi bi-x-circle-fill fs-4" style="color:#dc2626"></i></div>
            <div>
                <div class="stat-value"><?= $stats['errores'] ?></div>
                <div class="stat-label">Con errores</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel izquierdo: acciones -->
    <div class="col-lg-5">

        <!-- Envío masivo de avisos de vencimiento -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-send-fill me-2 text-warning"></i>Avisos de Vencimiento Automáticos
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Envía correos a todas las instituciones que vencen
                    en <strong>7 días</strong>, <strong>3 días</strong> o <strong>hoy</strong>.
                    No se repiten si ya se enviaron hoy.
                </p>

                <?php if (!empty($porVencer)): ?>
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong><?= count($porVencer) ?></strong> institución(es) vencen en los próximos 7 días.
                </div>
                <?php else: ?>
                <div class="alert alert-success py-2 small">
                    <i class="bi bi-check-circle me-1"></i>Sin vencimientos próximos.
                </div>
                <?php endif; ?>

                <form action="<?= $appUrl ?>/superadmin/notificaciones/enviar-vencimientos" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn btn-warning w-100 fw-semibold">
                        <i class="bi bi-bell-fill me-2"></i>Enviar avisos ahora
                    </button>
                </form>

                <p class="text-muted mt-2 mb-0" style="font-size:.75rem">
                    💡 <strong>Tip:</strong> Para automatizar esto, configura un cron job que llame a esta ruta cada día a las 8:00 AM.
                </p>
            </div>
        </div>

        <!-- Correo personalizado -->
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-pencil-square me-2 text-primary"></i>Correo Personalizado
            </div>
            <div class="card-body">
                <form action="<?= $appUrl ?>/superadmin/notificaciones/enviar-individual" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Institución</label>
                        <select name="institucion_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php
                            // Cargar lista de instituciones
                            $db = Database::getInstance();
                            $insts = $db->query("SELECT id, nombre, email FROM instituciones WHERE activo = 1 ORDER BY nombre")->fetchAll();
                            foreach ($insts as $i):
                            ?>
                            <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nombre']) ?> — <?= htmlspecialchars($i['email'] ?? 'sin email') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asunto</label>
                        <input type="text" name="asunto" class="form-control" required placeholder="Ej: Información importante sobre su cuenta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="5" required
                                  placeholder="Escribe el mensaje aquí. Puedes usar saltos de línea."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-envelope-fill me-2"></i>Enviar correo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel derecho: historial -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Historial de envíos (últimos 50)</span>
            </div>
            <div class="card-body p-0" style="max-height:620px;overflow-y:auto">
                <table class="table table-hover mb-0">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1">
                        <tr>
                            <th>Institución</th>
                            <th>Tipo</th>
                            <th>Destinatario</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($historial)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin notificaciones enviadas aún.</td></tr>
                    <?php else: ?>
                    <?php foreach ($historial as $n): ?>
                    <tr>
                        <td class="fw-semibold small"><?= htmlspecialchars($n['institucion_nombre']) ?></td>
                        <td>
                            <span class="small"><?= $tipoLabels[$n['tipo']] ?? $n['tipo'] ?></span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($n['destinatario']) ?></td>
                        <td>
                            <?php if ($n['estado'] === 'enviado'): ?>
                                <span class="badge badge-activo">✓ Enviado</span>
                            <?php elseif ($n['estado'] === 'error'): ?>
                                <span class="badge badge-vencida" title="<?= htmlspecialchars($n['error_detalle'] ?? '') ?>">✗ Error</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                            <?php if ($n['enviado_por']): ?>
                            <div style="font-size:.7rem">
                                <?= htmlspecialchars($n['enviado_nombres'] . ' ' . $n['enviado_apellidos']) ?>
                            </div>
                            <?php else: ?>
                            <div style="font-size:.7rem;color:#94a3b8">Automático</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>