<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="d-flex flex-wrap gap-1 align-items-center">
        <!-- Filtros rápidos de estado -->
        <?php
        $filtros = ['' => 'Todas', 'activa' => 'Activas', 'vencida' => 'Vencidas', 'suspendida' => 'Suspendidas'];
        foreach ($filtros as $val => $label):
            $activo = ($filtroEstado === $val) ? 'btn-primary' : 'btn-outline-secondary';
            $q = $_GET['q'] ?? '';
        ?>
        <a href="?estado=<?= $val ?><?= $q ? '&q='.urlencode($q) : '' ?>"
           class="btn btn-sm <?= $activo ?>"><?= $label ?></a>
        <?php endforeach; ?>
        <span class="text-muted small ms-2">
            <?= count($instituciones) ?> resultado<?= count($instituciones) != 1 ? 's' : '' ?>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $appUrl ?>/superadmin/instituciones/exportar"
           class="btn btn-sm btn-outline-success" title="Exportar todas las instituciones a CSV">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
        </a>
        <a href="<?= $appUrl ?>/superadmin/instituciones/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nueva Institución
        </a>
    </div>
</div>

<!-- Búsqueda en tiempo real -->
<div class="mb-3 position-relative">
    <i class="bi bi-search position-absolute text-muted" style="left:12px;top:50%;transform:translateY(-50%)"></i>
    <input type="text" id="buscarInst" class="form-control ps-4"
           placeholder="Buscar por nombre, email o identificador URL..."
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           autocomplete="off">
    <span id="limpiarBusqueda" class="position-absolute text-muted" style="right:12px;top:50%;transform:translateY(-50%);cursor:pointer;display:none">
        <i class="bi bi-x-circle"></i>
    </span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Institución</th>
                        <th>URL Preinscripción</th>
                        <th>Plan</th>
                        <th>Suscripción</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($instituciones)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay instituciones que mostrar.</td></tr>
                <?php else: ?>
                <?php foreach ($instituciones as $i): ?>
                <?php
                    $estadoClass = match($i['suscripcion_estado'] ?? '') {
                        'activa'     => 'badge-activo',
                        'vencida'    => 'badge-vencida',
                        'suspendida' => 'bg-secondary text-white',
                        default      => 'bg-light text-muted',
                    };
                    $estadoLabel = match($i['suscripcion_estado'] ?? '') {
                        'activa'     => 'Activa',
                        'vencida'    => 'Vencida',
                        'suspendida' => 'Suspendida',
                        default      => 'Sin plan',
                    };
                    $diasR = $i['dias_restantes'] ?? null;
                ?>
                <tr class="inst-row"
                    data-nombre="<?= strtolower(htmlspecialchars($i['nombre'])) ?>"
                    data-email="<?= strtolower(htmlspecialchars($i['email'] ?? '')) ?>"
                    data-slug="<?= strtolower(htmlspecialchars($i['subdomain'] ?? '')) ?>">
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($i['nombre']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($i['email'] ?? '') ?></div>
                    </td>
                    <td>
                        <?php
                        $appUrl2 = (require __DIR__ . '/../../../../config/app.php')['url'];
                        if ($i['subdomain']): ?>
                        <a href="<?= $appUrl2 ?>/preinscripcion/<?= htmlspecialchars($i['subdomain']) ?>"
                           target="_blank" class="small text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1 opacity-50"></i><?= htmlspecialchars($i['subdomain']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($i['plan_nombre']): ?>
                        <span class="badge px-2 py-1" style="background:<?= htmlspecialchars($i['plan_color'] ?? '#ddd') ?>22;color:<?= htmlspecialchars($i['plan_color'] ?? '#888') ?>;border:1px solid <?= htmlspecialchars($i['plan_color'] ?? '#ddd') ?>44">
                            <?= htmlspecialchars($i['plan_nombre']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted small">Sin plan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $estadoClass ?>"><?= $estadoLabel ?></span>
                    </td>
                    <td>
                        <?php if ($i['fecha_vencimiento']): ?>
                            <?php
                            $fechaFmt = date('d/m/Y', strtotime($i['fecha_vencimiento']));
                            if ($diasR !== null && $diasR >= 0 && $diasR <= DIAS_AVISO_VENCIMIENTO) {
                                echo "<span class='text-warning fw-semibold'>{$fechaFmt}</span>";
                                echo "<div style='font-size:.7rem;color:#ca8a04'>⚠️ {$diasR} días</div>";
                            } elseif ($diasR !== null && $diasR < 0) {
                                echo "<span class='text-danger fw-semibold'>{$fechaFmt}</span>";
                            } else {
                                echo "<span>{$fechaFmt}</span>";
                            }
                            ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $i['activo'] ? 'badge-activo' : 'badge-inactivo' ?>">
                            <?= $i['activo'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>"
                               class="btn btn-outline-primary" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-outline-danger btn-eliminar"
                                    data-id="<?= $i['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($i['nombre']) ?>"
                                    title="Eliminar institución">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── MODAL ELIMINAR ─────────────────────────────────── -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Eliminar Institución</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar <strong id="elimNombre"></strong>?</p>
                <div class="alert alert-danger small">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Esta acción es <strong>irreversible</strong>. Se eliminarán todos los datos:
                    usuarios, suscripciones, pagos, estudiantes y preinscripciones.
                </div>
                <label class="form-label fw-semibold">Escribe <code>ELIMINAR</code> para confirmar:</label>
                <input type="text" id="confirmarEliminar" class="form-control" placeholder="ELIMINAR">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" id="btnConfirmarEliminar" class="btn btn-danger" disabled>
                        <i class="bi bi-trash3 me-1"></i>Eliminar definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ── Búsqueda en tiempo real ──────────────────────────────
const inputBuscar = document.getElementById('buscarInst');
const limpiar     = document.getElementById('limpiarBusqueda');

inputBuscar.addEventListener('input', filtrar);

function filtrar() {
    const q = inputBuscar.value.toLowerCase().trim();
    limpiar.style.display = q ? 'block' : 'none';
    let visible = 0;
    document.querySelectorAll('.inst-row').forEach(row => {
        const match = !q ||
            row.dataset.nombre.includes(q) ||
            row.dataset.email.includes(q) ||
            row.dataset.slug.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    // Actualizar contador
    document.querySelector('.text-muted.small.ms-2').textContent =
        visible + ' resultado' + (visible !== 1 ? 's' : '');
}

limpiar.addEventListener('click', () => {
    inputBuscar.value = '';
    filtrar();
    inputBuscar.focus();
});

// Ejecutar si hay valor inicial (viene de URL)
if (inputBuscar.value) filtrar();

// ── Modal Eliminar ───────────────────────────────────────
document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', () => {
        const id     = btn.dataset.id;
        const nombre = btn.dataset.nombre;
        document.getElementById('elimNombre').textContent    = nombre;
        document.getElementById('formEliminar').action       = `<?= $appUrl ?>/superadmin/instituciones/${id}/eliminar`;
        document.getElementById('confirmarEliminar').value   = '';
        document.getElementById('btnConfirmarEliminar').disabled = true;
        new bootstrap.Modal(document.getElementById('modalEliminar')).show();
    });
});

document.getElementById('confirmarEliminar').addEventListener('input', function() {
    document.getElementById('btnConfirmarEliminar').disabled = this.value !== 'ELIMINAR';
});
</script>