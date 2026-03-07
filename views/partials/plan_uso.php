<?php
// Widget de uso del plan — dashboard del colegio
// Se oculta automáticamente para SuperAdmin y sin suscripción

$_plan   = $_SESSION['plan'] ?? [];
$_instId = (int)($_SESSION['institucion_id'] ?? 0);
$_mostrar = !empty($_plan)
         && (int)($_plan['plan_id'] ?? 0) !== 0
         && $_instId > 0;

if ($_mostrar):
    $_uso   = PlanHelper::getResumenUso($_instId);
    $_vence = $_plan['fecha_vencimiento'] ?? null;
    $_dias  = $_vence ? (int)ceil((strtotime($_vence) - time()) / 86400) : null;
    $_mods  = [
        'pagos'       => ['label' => 'Pagos y cuotas', 'icono' => 'bi-cash-stack'],
        'reportes'    => ['label' => 'Reportes PDF',   'icono' => 'bi-file-earmark-pdf'],
        'comunicados' => ['label' => 'Comunicados',    'icono' => 'bi-megaphone-fill'],
    ];
?>
<div class="card mb-4 border-0"
     style="background:linear-gradient(135deg,#f0f6ff,#e8f0fe);
            border-left:4px solid #1a56db !important;border-radius:12px;">
    <div class="card-body pb-2">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-star-fill text-primary"></i>
                <span class="fw-bold">Plan <?= htmlspecialchars($_plan['nombre'] ?? '') ?></span>
            </div>
            <?php if ($_dias !== null): ?>
            <span class="badge <?= $_dias <= 7 ? 'bg-danger' : ($_dias <= 30 ? 'bg-warning text-dark' : 'bg-success') ?>">
                <?= $_dias <= 0 ? 'VENCIDO' : "Vence en {$_dias} días" ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="row g-2 mb-3">
        <?php foreach ($_uso as $_item):
            $_max    = (int)$_item['max'];
            $_actual = (int)$_item['actual'];
            $_ilim   = $_max === 0;
            $_pct    = $_ilim ? 0 : ($_max > 0 ? min(100, round($_actual / $_max * 100)) : 0);
            $_color  = $_ilim ? '#10b981' : ($_pct >= 90 ? '#ef4444' : ($_pct >= 70 ? '#f59e0b' : '#10b981'));
        ?>
            <div class="col-md-4">
                <div class="bg-white rounded-3 p-2 h-100" style="border:1px solid #e2e8f0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-muted">
                            <i class="bi <?= $_item['icono'] ?> me-1"></i><?= $_item['label'] ?>
                        </span>
                        <span class="small fw-bold" style="color:<?= $_color ?>">
                            <?= $_actual ?><?= $_ilim ? '' : "/{$_max}" ?>
                        </span>
                    </div>
                    <?php if (!$_ilim): ?>
                    <div class="progress" style="height:5px;border-radius:3px">
                        <div class="progress-bar" style="width:<?= $_pct ?>%;background:<?= $_color ?>;border-radius:3px"></div>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.68rem">
                        <?= $_max - $_actual ?> disponible<?= ($_max - $_actual) !== 1 ? 's' : '' ?>
                    </div>
                    <?php else: ?>
                    <div class="text-success mt-1" style="font-size:.68rem">Ilimitado</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="d-flex flex-wrap gap-2">
        <?php foreach ($_mods as $_key => $_mod):
            $_tiene = PlanHelper::tieneModulo($_key);
        ?>
            <span class="badge px-2 py-1"
                  style="background:<?= $_tiene ? '#d1fae5' : '#f1f5f9' ?>;
                         color:<?= $_tiene ? '#065f46' : '#94a3b8' ?>;
                         border:1px solid <?= $_tiene ? '#a7f3d0' : '#e2e8f0' ?>;
                         opacity:<?= $_tiene ? '1' : '0.6' ?>">
                <i class="bi <?= $_tiene ? $_mod['icono'] : 'bi-lock-fill' ?> me-1"></i>
                <?= $_mod['label'] ?>
            </span>
        <?php endforeach; ?>
        <?php if (!PlanHelper::tieneModulo('pagos') || !PlanHelper::tieneModulo('reportes')): ?>
            <a href="mailto:soporte@edusaas.do"
               class="badge px-2 py-1 text-decoration-none"
               style="background:#eff6ff;color:#1a56db;border:1px solid #bfdbfe">
                <i class="bi bi-arrow-up-circle me-1"></i>Mejorar plan
            </a>
        <?php endif; ?>
        </div>

    </div>
</div>
<?php endif; ?>