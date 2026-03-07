<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0 small">
        <?= count($usuarios) ?> usuario<?= count($usuarios) != 1 ? 's' : '' ?> con acceso total al sistema
    </p>
    <a href="<?= $appUrl ?>/superadmin/usuarios/crear" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Super Admin
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Usuario</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): $esYo = (int)$u['id'] === $yo; ?>
            <tr class="<?= !$u['activo'] ? 'opacity-50' : '' ?>">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                             style="width:38px;height:38px;background:#1a56db;font-size:.85rem;flex-shrink:0">
                            <?= strtoupper(substr($u['nombres'], 0, 1) . substr($u['apellidos'] ?? '', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?>
                                <?php if ($esYo): ?>
                                <span class="badge bg-primary ms-1" style="font-size:.65rem">Tú</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted" style="font-size:.75rem">
                                Super Administrador
                            </div>
                        </div>
                    </div>
                </td>
                <td><code class="small"><?= htmlspecialchars($u['username']) ?></code></td>
                <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="<?= $appUrl ?>/superadmin/usuarios/<?= $u['id'] ?>/editar"
                           class="btn btn-outline-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>

                        <?php if (!$esYo): ?>
                        <!-- Toggle activo/inactivo -->
                        <form method="POST"
                              action="<?= $appUrl ?>/superadmin/usuarios/<?= $u['id'] ?>/toggle"
                              class="d-inline"
                              onsubmit="return confirm('<?= $u['activo'] ? '¿Desactivar este usuario?' : '¿Activar este usuario?' ?>')">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit"
                                    class="btn btn-outline-<?= $u['activo'] ? 'warning' : 'success' ?>"
                                    title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                <i class="bi bi-<?= $u['activo'] ? 'person-dash' : 'person-check' ?>"></i>
                            </button>
                        </form>

                        <!-- Eliminar -->
                        <button type="button"
                                class="btn btn-outline-danger"
                                title="Eliminar"
                                onclick="confirmarEliminar(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?>')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Eliminar usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar a <strong id="elimNombre"></strong>?</p>
                <div class="alert alert-danger small mb-0">
                    Esta acción es irreversible. El usuario perderá acceso permanentemente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('elimNombre').textContent = nombre;
    document.getElementById('formEliminar').action =
        '<?= $appUrl ?>/superadmin/usuarios/' + id + '/eliminar';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>