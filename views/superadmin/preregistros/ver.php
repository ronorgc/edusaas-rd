<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$s = $solicitud;
$estadoBadge = [
    'pendiente'  => 'bg-warning text-dark',
    'contactado' => 'bg-info text-dark',
    'aprobado'   => 'bg-success',
    'rechazado'  => 'bg-secondary',
][$s['estado']] ?? 'bg-secondary';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= $appUrl ?>/superadmin/preregistros" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a la lista
    </a>
    <span class="badge <?= $estadoBadge ?> fs-6 px-3 py-2">
        <?= ucfirst(htmlspecialchars($s['estado'])) ?>
    </span>
</div>

<div class="row g-4">

    <!-- Datos de la solicitud -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header fw-bold">
                <i class="bi bi-building me-2 text-primary"></i>
                <?= htmlspecialchars($s['nombre']) ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small">Tipo</div>
                        <div class="fw-semibold"><?= ucfirst(htmlspecialchars($s['tipo'])) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Código MINERD</div>
                        <div class="fw-semibold"><?= htmlspecialchars($s['codigo_minerd'] ?? '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Municipio</div>
                        <div><?= htmlspecialchars($s['municipio'] ?? '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Provincia</div>
                        <div><?= htmlspecialchars($s['provincia'] ?? '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Estudiantes aprox.</div>
                        <div><?= $s['cant_estudiantes'] ? number_format($s['cant_estudiantes']) : '—' ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Plan de interés</div>
                        <div><?= htmlspecialchars($s['plan_nombre'] ?? '—') ?></div>
                    </div>
                </div>

                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small">Director / Contacto</div>
                        <div class="fw-semibold"><?= htmlspecialchars($s['nombre_director'] ?? '—') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['cargo_contacto'] ?? '') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Email</div>
                        <a href="mailto:<?= htmlspecialchars($s['email']) ?>"><?= htmlspecialchars($s['email']) ?></a>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Teléfono</div>
                        <div><?= htmlspecialchars($s['telefono'] ?? '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Fecha de solicitud</div>
                        <div><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></div>
                    </div>
                </div>

                <?php if ($s['mensaje']): ?>
                <hr class="my-3">
                <div class="text-muted small mb-1">Mensaje del solicitante</div>
                <div class="bg-light rounded-3 p-3 small"><?= nl2br(htmlspecialchars($s['mensaje'])) ?></div>
                <?php endif; ?>

                <?php if ($s['notas_internas']): ?>
                <hr class="my-3">
                <div class="text-muted small mb-1">Notas internas</div>
                <div class="bg-warning bg-opacity-10 border border-warning rounded-3 p-3 small">
                    <?= nl2br(htmlspecialchars($s['notas_internas'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($s['estado'] === 'aprobado' && $s['inst_nombre']): ?>
                <div class="alert alert-success mt-3 small mb-0">
                    <i class="bi bi-check-circle me-1"></i>
                    Institución creada: <strong><?= htmlspecialchars($s['inst_nombre']) ?></strong>
                    <?php if ($s['revisado_en']): ?>
                    — <?= date('d/m/Y H:i', strtotime($s['revisado_en'])) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="col-lg-5">

        <?php if ($s['estado'] === 'pendiente' || $s['estado'] === 'contactado'): ?>

        <!-- APROBAR -->
        <div class="card mb-3 border-success">
            <div class="card-header fw-bold text-success bg-success bg-opacity-10">
                <i class="bi bi-check-circle me-2"></i>Aprobar solicitud
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Se creará la institución, un usuario admin y una suscripción activa.
                    Se enviará email con credenciales al colegio.
                </p>
                <form method="POST" action="<?= $appUrl ?>/superadmin/preregistros/<?= $s['id'] ?>/aprobar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Plan a asignar <span class="text-danger">*</span></label>
                        <select name="plan_id" class="form-select form-select-sm" required>
                            <option value="">— Selecciona un plan —</option>
                            <?php foreach ($planes as $pl): ?>
                            <option value="<?= $pl['id'] ?>"
                                    <?= (int)$pl['id'] === (int)($s['plan_interes'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nombre']) ?>
                                (RD$<?= number_format($pl['precio_mensual'], 0) ?>/mes)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Facturación</label>
                        <select name="tipo_facturacion" class="form-select form-select-sm">
                            <option value="mensual">Mensual</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Contraseña inicial del admin</label>
                        <input type="text" name="password_admin" class="form-control form-control-sm"
                               value="Colegio2024!" required>
                        <div class="form-text">Se enviará al colegio por email.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Notas internas (opcional)</label>
                        <textarea name="notas_internas" class="form-control form-control-sm" rows="2"
                                  placeholder="Negociación especial, descuento, etc."><?= htmlspecialchars($s['notas_internas'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('¿Aprobar y crear la institución?')">
                        <i class="bi bi-check-lg me-1"></i>Aprobar y crear cuenta
                    </button>
                </form>
            </div>
        </div>

        <!-- CONTACTADO -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-telephone me-2 text-info"></i>Marcar como contactado
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/superadmin/preregistros/<?= $s['id'] ?>/contactado">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <textarea name="notas" class="form-control form-control-sm mb-2" rows="2"
                              placeholder="Llamé el lunes, esperando respuesta..."><?= htmlspecialchars($s['notas_internas'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-outline-info btn-sm w-100">
                        <i class="bi bi-telephone-check me-1"></i>Marcar como contactado
                    </button>
                </form>
            </div>
        </div>

        <!-- RECHAZAR -->
        <div class="card border-danger">
            <div class="card-header fw-semibold text-danger bg-danger bg-opacity-10">
                <i class="bi bi-x-circle me-2"></i>Rechazar solicitud
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/superadmin/preregistros/<?= $s['id'] ?>/rechazar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <textarea name="motivo" class="form-control form-control-sm mb-2" rows="2"
                              placeholder="Motivo del rechazo (uso interno)..."></textarea>
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                            onclick="return confirm('¿Rechazar esta solicitud?')">
                        <i class="bi bi-x-lg me-1"></i>Rechazar
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-check-circle-fill fs-3 d-block mb-2 <?= $s['estado'] === 'aprobado' ? 'text-success' : '' ?>"></i>
                Solicitud <?= htmlspecialchars($s['estado']) ?>.
                <?php if ($s['revisado_en']): ?>
                <div class="small mt-1">
                    <?= date('d/m/Y H:i', strtotime($s['revisado_en'])) ?>
                    <?php if ($s['revisor_nombre']): ?>
                    · por <?= htmlspecialchars($s['revisor_nombre'] . ' ' . $s['revisor_apellido']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>