<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$estadoSusc = $suscActiva['estado'] ?? 'sin_plan';
?>

<div class="row g-3 mb-4">
    <!-- Info institución -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-building me-2"></i>Institución</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Nombre</dt>
                    <dd class="col-7 fw-semibold"><?= htmlspecialchars($inst['nombre']) ?></dd>
                    <dt class="col-5 text-muted">Tipo</dt>
                    <dd class="col-7 text-capitalize"><?= htmlspecialchars($inst['tipo']) ?></dd>
                    <dt class="col-5 text-muted">URL Preinscripción</dt>
                    <dd class="col-7">
                        <?php if (!empty($inst['subdomain'])): ?>
                        <?php $appUrlVer = (require __DIR__ . '/../../../../config/app.php')['url']; ?>
                        <a href="<?= $appUrlVer ?>/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>"
                           target="_blank" class="small">
                            /preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>
                        </a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </dd>
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7"><?= htmlspecialchars($inst['email'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Teléfono</dt>
                    <dd class="col-7"><?= htmlspecialchars($inst['telefono'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Municipio</dt>
                    <dd class="col-7"><?= htmlspecialchars($inst['municipio'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Cód. MINERD</dt>
                    <dd class="col-7"><?= htmlspecialchars($inst['codigo_minerd'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Suscripción activa -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-credit-card me-2"></i>Suscripción</div>
            <div class="card-body">
                <?php if ($suscActiva): ?>
                <?php
                $dias = (int) ceil((strtotime($suscActiva['fecha_vencimiento']) - time()) / 86400);
                $colorDias = $dias <= 0 ? 'danger' : ($dias <= 7 ? 'warning' : 'success');
                ?>
                <div class="mb-3 text-center">
                    <span class="badge fs-6 px-3 py-2" style="background:<?= htmlspecialchars($suscActiva['plan_color']) ?>22;color:<?= htmlspecialchars($suscActiva['plan_color']) ?>;border:1px solid <?= htmlspecialchars($suscActiva['plan_color']) ?>55">
                        <i class="bi bi-star-fill me-1"></i><?= htmlspecialchars($suscActiva['plan_nombre']) ?>
                    </span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">Monto</dt>
                    <dd class="col-6 fw-semibold">RD$<?= number_format($suscActiva['monto'], 2) ?></dd>
                    <dt class="col-6 text-muted">Facturación</dt>
                    <dd class="col-6 text-capitalize"><?= $suscActiva['tipo_facturacion'] ?></dd>
                    <dt class="col-6 text-muted">Inicio</dt>
                    <dd class="col-6"><?= date('d/m/Y', strtotime($suscActiva['fecha_inicio'])) ?></dd>
                    <dt class="col-6 text-muted">Vence</dt>
                    <dd class="col-6">
                        <span class="text-<?= $colorDias ?> fw-semibold">
                            <?= date('d/m/Y', strtotime($suscActiva['fecha_vencimiento'])) ?>
                        </span>
                        <span class="badge bg-<?= $colorDias ?> ms-1"><?= max(0, $dias) ?>d</span>
                    </dd>
                </dl>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="bi bi-x-circle fs-2 d-block mb-2"></i>
                    Sin suscripción activa
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-lightning-fill me-2 text-warning"></i>Acciones</div>
            <div class="card-body d-grid gap-2">
                <!-- Editar institución -->
                <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/editar"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-2"></i>Editar datos del colegio
                </a>

                <!-- Gestión de usuarios -->
                <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/usuarios"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-people me-2"></i>Usuarios y contraseñas
                </a>

                <!-- Revisar datos del colegio (modo visor) -->
                <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/revisar"
                   class="btn btn-outline-info">
                    <i class="bi bi-eye me-2"></i>Ver datos del colegio (solo lectura)
                </a>

                <!-- Renovar / Registrar pago -->
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
                    <i class="bi bi-cash-coin me-2"></i>Registrar Pago / Renovar
                </button>

                <!-- Cambiar plan sin cobro -->
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCambiarPlan">
                    <i class="bi bi-arrow-repeat me-2"></i>Cambiar plan (sin cobro)
                </button>

                <?php if ($inst['activo']): ?>
                <!-- Suspender -->
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalSuspender">
                    <i class="bi bi-pause-circle me-2"></i>Suspender acceso
                </button>
                <?php else: ?>
                <!-- Reactivar -->
                <form action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/reactivar" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-play-circle me-2"></i>Reactivar acceso
                    </button>
                </form>
                <?php endif; ?>

                <a href="<?= $appUrl ?>/superadmin/instituciones" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Historial y pagos en tabs -->
<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="tabHistorial">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-pagos">
                    <i class="bi bi-receipt me-1"></i>Pagos (<?= count($pagos) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-suscripciones">
                    <i class="bi bi-clock-history me-1"></i>Historial suscripciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-log">
                    <i class="bi bi-journal-text me-1"></i>Actividad (<?= count($logActividad) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-notas">
                    <i class="bi bi-sticky me-1"></i>Notas internas
                    <?php if (!empty($inst['notas'])): ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">●</span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0 tab-content">
        <!-- Tab: Pagos -->
        <div class="tab-pane fade show active" id="tab-pagos">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Factura</th><th>Monto</th><th>Período</th><th>Método</th><th>Referencia</th><th>Fecha</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Sin pagos registrados</td></tr>
                    <?php else: ?>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><code class="small"><?= htmlspecialchars($p['numero_factura']) ?></code></td>
                            <td class="fw-semibold">RD$<?= number_format($p['monto'], 2) ?></td>
                            <td class="small text-muted">
                                <?= date('d/m/Y', strtotime($p['periodo_desde'])) ?> –
                                <?= date('d/m/Y', strtotime($p['periodo_hasta'])) ?>
                            </td>
                            <td class="text-capitalize"><?= htmlspecialchars($p['metodo_pago']) ?></td>
                            <td><?= htmlspecialchars($p['referencia'] ?? '—') ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                            <td>
                                <a href="<?= $appUrl ?>/superadmin/pagos/<?= $p['id'] ?>/recibo"
                                   target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver recibo">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Historial suscripciones -->
        <div class="tab-pane fade" id="tab-suscripciones">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Plan</th><th>Facturación</th><th>Monto</th><th>Inicio</th><th>Vencimiento</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historial as $h): ?>
                        <?php
                        $sc = match($h['estado']) {
                            'activa'     => 'badge-activo',
                            'vencida'    => 'badge-vencida',
                            'suspendida' => 'bg-secondary text-white',
                            'cancelada'  => 'bg-light text-muted',
                            default      => 'bg-light text-muted',
                        };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($h['plan_nombre']) ?></td>
                            <td class="text-capitalize"><?= $h['tipo_facturacion'] ?></td>
                            <td>RD$<?= number_format($h['monto'], 2) ?></td>
                            <td><?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($h['fecha_vencimiento'])) ?></td>
                            <td><span class="badge <?= $sc ?> text-capitalize"><?= $h['estado'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Tab: Log de actividad -->
        <div class="tab-pane fade" id="tab-log">
            <?php if (empty($logActividad)): ?>
            <p class="text-center text-muted py-4 small">Sin actividad registrada aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Fecha</th><th>Acción</th><th>Motivo</th><th>Realizado por</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logActividad as $entry):
                        $badgeLog = match($entry['accion']) {
                            'activada'      => 'bg-success',
                            'reactivada'    => 'bg-primary',
                            'suspendida'    => 'bg-warning text-dark',
                            'cancelada'     => 'bg-danger',
                            'plan_cambiado' => 'bg-info text-dark',
                            default         => 'bg-secondary',
                        };
                    ?>
                    <tr>
                        <td class="text-muted small" style="white-space:nowrap">
                            <?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?>
                        </td>
                        <td>
                            <span class="badge <?= $badgeLog ?> text-capitalize">
                                <?= htmlspecialchars($entry['accion']) ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($entry['motivo'] ?? '—') ?></td>
                        <td class="small text-muted">
                            <?= htmlspecialchars(($entry['nombres'] ?? '') . ' ' . ($entry['apellidos'] ?? '')) ?: '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Notas internas -->
        <div class="tab-pane fade" id="tab-notas">
            <div class="p-3">
                <form action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/notas" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-lock-fill me-1 text-muted"></i>
                        Notas privadas — solo visibles para el Super Admin
                    </label>
                    <textarea name="notas" class="form-control mb-3" rows="6"
                              placeholder="Ej: El director se llama Juan Pérez. Paga siempre a fin de mes. Interesado en plan anual..."
                              ><?= htmlspecialchars($inst['notas'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar notas
                    </button>
                    <?php if (!empty($inst['notas'])): ?>
                    <small class="text-muted ms-3">
                        Última edición guardada ✓
                    </small>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- ── MODAL: Registrar Pago / Renovar ── -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/pago" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Registrar Pago / Renovar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Plan</label>
                    <select name="plan_id" class="form-select" required>
                        <?php foreach ($planes as $pl): ?>
                        <option value="<?= $pl['id'] ?>" <?= ($suscActiva && $suscActiva['plan_id'] == $pl['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pl['nombre']) ?> — RD$<?= number_format($pl['precio_mensual'],0) ?>/mes
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de facturación</label>
                    <select name="tipo_facturacion" class="form-select">
                        <option value="mensual">Mensual</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Método de pago</label>
                    <select name="metodo_pago" class="form-select">
                        <option value="transferencia">Transferencia</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Referencia (opcional)</label>
                    <input type="text" name="referencia" class="form-control" placeholder="Número de transferencia, etc.">
                </div>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Se creará una nueva suscripción y se generará el número de factura automáticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i>Confirmar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Cambiar Plan Sin Cobro ── -->
<div class="modal fade" id="modalCambiarPlan" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/cambiar-plan" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Cambiar Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    Esto cambia el plan de la suscripción activa <strong>sin registrar un nuevo pago</strong>.
                    Útil para correcciones, cambios por acuerdo o período de gracia.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nuevo plan</label>
                    <select name="plan_id" class="form-select" required>
                        <?php foreach ($planes as $pl): ?>
                        <option value="<?= $pl['id'] ?>"
                                <?= ($suscActiva && $suscActiva['plan_id'] == $pl['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pl['nombre']) ?>
                            — RD$<?= number_format($pl['precio_mensual'], 0) ?>/mes
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Motivo del cambio</label>
                    <input type="text" name="motivo" class="form-control"
                           placeholder="Ej: Upgrade por acuerdo, corrección de plan...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Aplicar cambio
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Suspender ── -->
<div class="modal fade" id="modalSuspender" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= $appUrl ?>/superadmin/instituciones/<?= $inst['id'] ?>/suspender" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Suspender Institución</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">El colegio perderá acceso inmediatamente. Podrás reactivarlo en cualquier momento.</p>
                <label class="form-label fw-semibold">Motivo</label>
                <textarea name="motivo" class="form-control" rows="3" placeholder="Falta de pago, solicitud del cliente..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning">Suspender acceso</button>
            </div>
        </form>
    </div>
</div>