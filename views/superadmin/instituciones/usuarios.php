<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$rolesLabel = [
    'super_admin' => ['Super Admin',    'bg-danger text-white',  'bi-shield-lock'],
    'admin'       => ['Admin Colegio',  'bg-primary text-white', 'bi-person-gear'],
    'profesor'    => ['Profesor',       'bg-info text-dark',     'bi-mortarboard'],
    'estudiante'  => ['Estudiante',     'bg-light text-dark border','bi-person'],
    'padre'       => ['Padre/Tutor',    'bg-light text-dark border','bi-people'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <nav><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/superadmin/instituciones">Instituciones</a></li>
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nombre']) ?></a></li>
        <li class="breadcrumb-item active">Usuarios</li>
    </ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/editar"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Editar colegio
        </a>
        <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Info del colegio -->
<div class="alert alert-light border d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi bi-building-fill text-primary fs-4"></i>
    <div>
        <span class="fw-bold"><?= htmlspecialchars($inst['nombre']) ?></span>
        <span class="text-muted small ms-2 font-monospace">/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?></span>
        <span class="badge ms-2 <?= $inst['activo'] ? 'badge-activo' : 'badge-vencida' ?>">
            <?= $inst['activo'] ? 'Activo' : 'Suspendido' ?>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people-fill me-2 text-primary"></i>
              Usuarios registrados
              <span class="badge bg-primary ms-1"><?= count($usuarios) ?></span>
        </span>
        <span class="text-muted small">Solo el super admin puede restablecer contraseñas</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x fs-1 opacity-25 d-block mb-2"></i>
            No hay usuarios registrados para este colegio.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:48px"></th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Email</th>
                        <th>Último acceso</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u):
                    $rolKey    = strtolower(str_replace(' ', '_', $u['rol_nombre'] ?? ''));
                    $rolCfg    = $rolesLabel[$rolKey] ?? [$u['rol_nombre'], 'bg-secondary text-white', 'bi-person'];
                    $iniciales = mb_strtoupper(mb_substr($u['nombres'],0,1).mb_substr($u['apellidos'],0,1));
                    $colores   = ['#1a56db','#7c3aed','#db2777','#ea580c','#16a34a'];
                    $color     = $colores[crc32($u['id']) % count($colores)];
                ?>
                <tr>
                    <td>
                        <?php if ($u['foto']): ?>
                        <img src="<?= htmlspecialchars($u['foto']) ?>" class="rounded-circle"
                             width="36" height="36" style="object-fit:cover">
                        <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center
                                    fw-bold text-white"
                             style="width:36px;height:36px;background:<?= $color ?>;font-size:.75rem">
                            <?= $iniciales ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?></div>
                        <div class="text-muted small">@<?= htmlspecialchars($u['username']) ?></div>
                    </td>
                    <td>
                        <span class="badge <?= $rolCfg[1] ?>">
                            <i class="bi <?= $rolCfg[2] ?> me-1"></i><?= $rolCfg[0] ?>
                        </span>
                    </td>
                    <td class="small"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td class="small text-muted">
                        <?= $u['ultimo_acceso']
                            ? date('d/m/Y H:i', strtotime($u['ultimo_acceso']))
                            : '<span class="opacity-50">Nunca</span>' ?>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                        <span class="badge badge-activo">Activo</span>
                        <?php else: ?>
                        <span class="badge bg-secondary text-white">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center">

                            <!-- Reset contraseña -->
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    title="Restablecer contraseña"
                                    onclick="abrirResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombres'].' '.$u['apellidos'])) ?>', '<?= htmlspecialchars($u['email'] ?? '') ?>')">
                                <i class="bi bi-key-fill"></i>
                            </button>

                            <!-- Toggle activo/inactivo -->
                            <form method="POST"
                                  action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/usuarios/<?= $u['id'] ?>/toggle"
                                  onsubmit="return confirm('¿<?= $u['activo'] ? 'Desactivar' : 'Activar' ?> este usuario?')">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit"
                                        class="btn btn-sm <?= $u['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                    <i class="bi <?= $u['activo'] ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Reset Contraseña -->
<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="frmReset">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header border-warning" style="border-top:4px solid #f59e0b">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="bi bi-key-fill text-warning me-2"></i>Restablecer contraseña
                    </h5>
                    <div class="text-muted small" id="resetUserName"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Si dejas el campo vacío, se generará una contraseña aleatoria segura y
                    se enviará por email al usuario.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nueva contraseña</label>
                    <div class="input-group">
                        <input type="text" name="nueva_clave" id="inputNuevaClave"
                               class="form-control font-monospace"
                               placeholder="Dejar vacío = generar automático"
                               minlength="8" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="generarClave()" title="Generar contraseña">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="toggleVer()" title="Mostrar/ocultar">
                            <i class="bi bi-eye" id="iconVer"></i>
                        </button>
                    </div>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <div class="small text-muted" id="resetEmailHint"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning fw-bold">
                    <i class="bi bi-key me-1"></i>Restablecer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirResetModal(userId, nombre, email) {
    const appUrl = '<?= $appUrl ?>';
    const instId = '<?= $inst['id'] ?>';

    document.getElementById('frmReset').action =
        `${appUrl}/superadmin/instituciones/${instId}/usuarios/${userId}/reset`;
    document.getElementById('resetUserName').textContent = nombre;
    document.getElementById('resetEmailHint').textContent =
        email ? `Se enviará notificación a: ${email}` : 'Este usuario no tiene email registrado.';
    document.getElementById('inputNuevaClave').value = '';

    new bootstrap.Modal(document.getElementById('modalReset')).show();
}

function generarClave() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$';
    let clave = '';
    // Asegurar: 1 mayúscula, 1 número, 1 especial
    clave += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random()*26)];
    clave += '0123456789'[Math.floor(Math.random()*10)];
    clave += '!@#$'[Math.floor(Math.random()*4)];
    for (let i = 3; i < 10; i++) {
        clave += chars[Math.floor(Math.random()*chars.length)];
    }
    // Mezclar
    clave = clave.split('').sort(() => Math.random() - 0.5).join('');
    document.getElementById('inputNuevaClave').value = clave;
    document.getElementById('inputNuevaClave').type = 'text';
    document.getElementById('iconVer').className = 'bi bi-eye-slash';
}

function toggleVer() {
    const input = document.getElementById('inputNuevaClave');
    const icon  = document.getElementById('iconVer');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>