<?php $appUrl = APP_URL; // ADR-016 ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="text-muted small"><?= count($planes) ?> plan<?= count($planes) != 1 ? 'es' : '' ?> en total</span>
    </div>
    <a href="<?= $appUrl ?>/superadmin/planes/crear" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Plan
    </a>
</div>

<div class="row g-4">
<?php foreach ($planes as $plan):
    $inactivo = !$plan['activo'];
    // colegios_activos viene del JOIN en PlanModel::getAllConColegiosActivos() — no query en vista (BUG-V-05)
    $colegiosEnPlan = (int)($plan['colegios_activos'] ?? 0);
?>
<div class="col-md-4">
    <div class="card h-100 <?= $inactivo ? 'opacity-60' : '' ?>"
         style="border-top: 4px solid <?= htmlspecialchars($plan['color']) ?><?= $inactivo ? ';filter:grayscale(.7)' : '' ?>">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi <?= htmlspecialchars($plan['icono']) ?> fs-3"
                       style="color:<?= htmlspecialchars($plan['color']) ?>"></i>
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($plan['nombre']) ?></h5>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <?php if ($colegiosEnPlan > 0): ?>
                    <span class="badge bg-primary" title="Colegios activos en este plan">
                        <?= $colegiosEnPlan ?> colegio<?= $colegiosEnPlan != 1 ? 's' : '' ?>
                    </span>
                    <?php endif; ?>
                    <span class="badge <?= $inactivo ? 'bg-secondary' : 'bg-success' ?>">
                        <?= $inactivo ? 'Inactivo' : 'Activo' ?>
                    </span>
                </div>
            </div>

            <p class="text-muted small"><?= htmlspecialchars($plan['descripcion'] ?? '') ?></p>

            <div class="mb-3">
                <span class="fs-3 fw-bold" style="color:<?= htmlspecialchars($plan['color']) ?>">
                    RD$<?= number_format($plan['precio_mensual'], 0) ?>
                </span>
                <span class="text-muted">/mes</span>
                <?php if ($plan['precio_anual'] > 0): ?>
                <div class="text-muted small">
                    Anual: RD$<?= number_format($plan['precio_anual'], 0) ?>
                    <?php
                    $ahorro = round(($plan['precio_mensual']*12 - $plan['precio_anual']) / ($plan['precio_mensual']*12) * 100);
                    if ($ahorro > 0): ?>
                    <span class="text-success">(ahorra <?= $ahorro ?>%)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <ul class="list-unstyled small">
                <li class="mb-1">
                    <i class="bi bi-people me-2 text-primary"></i>
                    <strong><?= $plan['max_estudiantes'] ?: '∞' ?></strong> estudiantes
                </li>
                <li class="mb-1">
                    <i class="bi bi-person-workspace me-2 text-primary"></i>
                    <strong><?= $plan['max_profesores'] ?: '∞' ?></strong> profesores
                </li>
                <li class="mb-1">
                    <i class="bi bi-grid me-2 text-primary"></i>
                    <strong><?= $plan['max_secciones'] ?: '∞' ?></strong> secciones
                </li>
                <?php
                $mods = [
                    'incluye_pagos'       => 'cash-stack',
                    'incluye_reportes'    => 'file-earmark-pdf',
                    'incluye_comunicados' => 'megaphone-fill',
                    'incluye_api'         => 'code-slash',
                ];
                $modLabels = [
                    'incluye_pagos'       => 'Módulo de pagos',
                    'incluye_reportes'    => 'Reportes PDF',
                    'incluye_comunicados' => 'Comunicados',
                    'incluye_api'         => 'Acceso a API',
                ];
                foreach ($mods as $key => $icon): ?>
                <li class="mb-1 <?= $plan[$key] ? '' : 'text-muted' ?>">
                    <i class="bi bi-<?= $plan[$key] ? "check-circle-fill text-success" : "x-circle text-muted" ?> me-2"></i>
                    <?= $modLabels[$key] ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card-footer bg-transparent d-flex gap-2">
            <a href="<?= $appUrl ?>/superadmin/planes/<?= $plan['id'] ?>/editar"
               class="btn btn-outline-primary btn-sm flex-grow-1">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <form method="POST"
                  action="<?= $appUrl ?>/superadmin/planes/<?= $plan['id'] ?>/toggle"
                  onsubmit="return confirm('<?= $inactivo ? '¿Activar este plan?' : ($colegiosEnPlan > 0 ? "Este plan tiene {$colegiosEnPlan} colegio(s) activo(s). ¿Desactivarlo de todas formas?" : '¿Desactivar este plan?') ?>')">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit"
                        class="btn btn-sm <?= $inactivo ? 'btn-outline-success' : 'btn-outline-secondary' ?>"
                        title="<?= $inactivo ? 'Activar' : 'Desactivar' ?> plan">
                    <i class="bi bi-<?= $inactivo ? 'toggle-off' : 'toggle-on' ?>"></i>
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>