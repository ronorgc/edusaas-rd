<?php
$appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
$m      = $metricas; // alias corto

// Helpers locales
$fmt  = fn(float $n): string => 'RD$' . number_format($n, 0, '.', ',');
$pct  = fn(float $n): string => ($n >= 0 ? '+' : '') . number_format($n, 1) . '%';
$tendColor = fn(float $n): string => $n >= 0 ? '#10b981' : '#ef4444';
$tendIcon  = fn(float $n): string => $n >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right';

// Etiquetas de los 12 meses
$labels12 = array_map(fn($k) => date('M', strtotime($k . '-01')), array_keys($m['ingresos_12_meses']));
?>

<?php if ($m['clientes_activos'] === 0 && $m['ingresos_anio'] == 0): ?>
<!-- Estado vacío — sin datos aún -->
<div class="alert border-0 mb-4 d-flex gap-3 align-items-start"
     style="background:#f0f6ff;border-left:4px solid #1a56db !important;border-radius:12px;">
    <div style="font-size:2.5rem;line-height:1">🚀</div>
    <div>
        <div class="fw-bold mb-1" style="color:#1a56db">¡Bienvenido a EduSaaS RD!</div>
        <div class="text-muted small">
            Aún no tienes instituciones registradas. Las métricas y gráficos se activarán
            automáticamente cuando registres tu primer colegio y su suscripción.
        </div>
        <a href="<?= $appUrl ?>/superadmin/instituciones/crear" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-plus-lg me-1"></i>Registrar primer colegio
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     FILA 1 — KPIs principales
════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- MRR -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">
                MRR <span class="kpi-tooltip" title="Monthly Recurring Revenue — Ingreso mensual recurrente de todas las suscripciones activas">?</span>
            </div>
            <div class="kpi-value"><?= $fmt($m['mrr']) ?></div>
            <div class="kpi-sub" style="color:<?= $tendColor($m['crecimiento_mom']) ?>">
                <i class="bi <?= $tendIcon($m['crecimiento_mom']) ?>"></i>
                <?= $pct($m['crecimiento_mom']) ?> vs mes anterior
            </div>
        </div>
    </div>

    <!-- ARR -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">
                ARR <span class="kpi-tooltip" title="Annual Recurring Revenue — MRR × 12">?</span>
            </div>
            <div class="kpi-value"><?= $fmt($m['arr']) ?></div>
            <div class="kpi-sub text-muted">Ingreso anual proyectado</div>
        </div>
    </div>

    <!-- Clientes activos -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">Clientes activos</div>
            <div class="kpi-value"><?= $m['clientes_activos'] ?></div>
            <div class="kpi-sub">
                <span style="color:#10b981">+<?= $m['clientes_nuevos_mes'] ?> nuevos</span>
                <span class="ms-2" style="color:#ef4444">−<?= $m['clientes_perdidos'] ?> perdidos</span>
                <span class="ms-2 text-muted">este mes</span>
            </div>
        </div>
    </div>

    <!-- ARPU -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">
                ARPU <span class="kpi-tooltip" title="Average Revenue Per User — Ingreso promedio por cliente activo">?</span>
            </div>
            <div class="kpi-value"><?= $fmt($m['arpu']) ?></div>
            <div class="kpi-sub text-muted">Por institución / mes</div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     FILA 2 — Métricas secundarias
════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- Ingresos realizados este mes -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-card--sm">
            <div class="kpi-label">Cobrado este mes</div>
            <div class="kpi-value kpi-value--sm"><?= $fmt($m['ingresos_mes']) ?></div>
            <div class="kpi-sub text-muted">Pagos confirmados</div>
        </div>
    </div>

    <!-- Proyección del año -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-card--sm">
            <div class="kpi-label">Proyección <?= date('Y') ?></div>
            <div class="kpi-value kpi-value--sm"><?= $fmt($m['proyeccion_anio']) ?></div>
            <div class="kpi-sub text-muted"><?= $fmt($m['ingresos_anio']) ?> cobrados</div>
        </div>
    </div>

    <!-- Churn rate -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-card--sm">
            <div class="kpi-label">
                Churn Rate <span class="kpi-tooltip" title="% de clientes que dejaron de pagar este mes">?</span>
            </div>
            <div class="kpi-value kpi-value--sm" style="color:<?= $m['churn_rate'] > 5 ? '#ef4444' : ($m['churn_rate'] > 0 ? '#f59e0b' : '#10b981') ?>">
                <?= number_format($m['churn_rate'], 1) ?>%
            </div>
            <div class="kpi-sub text-muted"><?= $m['clientes_perdidos'] ?> cliente(s) perdido(s)</div>
        </div>
    </div>

    <!-- Ingresos en riesgo -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-card--sm" style="border-left: 3px solid #f59e0b">
            <div class="kpi-label">⚠️ En riesgo (30 días)</div>
            <div class="kpi-value kpi-value--sm" style="color:#f59e0b"><?= $fmt($m['ingresos_riesgo']) ?></div>
            <div class="kpi-sub text-muted">Suscripciones próximas a vencer</div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════════
     FILA 3 — Gráficos
════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- Ingresos 12 meses (área) -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Ingresos — últimos 12 meses</span>
                <a href="<?= $appUrl ?>/superadmin/ingresos" class="btn btn-sm btn-outline-primary">Ver detalle</a>
            </div>
            <div class="card-body pb-2">
                <canvas id="chartIngresos" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Distribución por plan (donut) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Distribución por plan
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if (empty($m['por_plan'])): ?>
                <p class="text-muted small text-center py-3">Sin datos aún</p>
                <?php else: ?>
                <canvas id="chartPlanes" height="180" style="max-height:180px"></canvas>
                <div class="mt-3 w-100">
                    <?php foreach ($m['por_plan'] as $plan): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="d-flex align-items-center gap-2 small">
                            <span style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($plan['color']) ?>;display:inline-block"></span>
                            <?= htmlspecialchars($plan['nombre']) ?>
                        </span>
                        <span class="fw-semibold small"><?= $plan['cantidad'] ?> cliente<?= $plan['cantidad'] != 1 ? 's' : '' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════════
     FILA 4 — Nuevos clientes + Vencimientos + Pagos
════════════════════════════════════════════════ -->
<div class="row g-3">

    <!-- Nuevos clientes 12 meses (barras) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-person-plus-fill me-2 text-success"></i>Nuevos clientes / mes
            </div>
            <div class="card-body pb-2">
                <canvas id="chartClientes" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Vencimientos próximos -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-fill me-2 text-warning"></i>Vencimientos próximos</span>
                <?php if (!empty($porVencer)): ?>
                <a href="<?= $appUrl ?>/superadmin/notificaciones" class="btn btn-sm btn-warning">
                    Avisar
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0" style="overflow-y:auto;max-height:260px">
                <?php if (empty($porVencer)): ?>
                <p class="text-muted text-center py-4 small">Sin vencimientos próximos 🎉</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($porVencer as $v):
                        $d = (int)$v['dias_restantes'];
                        $clr = $d <= 3 ? '#ef4444' : '#f59e0b';
                    ?>
                    <li class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $v['institucion_id'] ?>"
                                   class="fw-semibold small text-decoration-none text-dark">
                                    <?= htmlspecialchars($v['institucion_nombre']) ?>
                                </a>
                                <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($v['plan_nombre']) ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge fw-bold" style="background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>44">
                                    <?= $d === 0 ? 'HOY' : "{$d}d" ?>
                                </span>
                                <div style="font-size:.7rem;color:#94a3b8"><?= date('d/m/Y', strtotime($v['fecha_vencimiento'])) ?></div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimos cobros -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2 text-success"></i>Últimos cobros</span>
                <a href="<?= $appUrl ?>/superadmin/cobros" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-plus-lg"></i> Cobrar
                </a>
            </div>
            <div class="card-body p-0" style="overflow-y:auto;max-height:260px">
                <?php if (empty($ultimosPagos)): ?>
                <p class="text-center text-muted py-4 small">Sin cobros este mes</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($ultimosPagos as $p): ?>
                    <li class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($p['institucion_nombre']) ?></div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <?= htmlspecialchars($p['plan_nombre']) ?> ·
                                    <?= date('d/m', strtotime($p['fecha_pago'])) ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold small text-success">
                                    RD$<?= number_format($p['monto'], 0) ?>
                                </span>
                                <a href="<?= $appUrl ?>/superadmin/cobros/recibo/<?= $p['id'] ?>"
                                   target="_blank" class="text-muted" title="Imprimir">
                                    <i class="bi bi-printer small"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════════
     CSS del dashboard
════════════════════════════════════════════════ -->
<style>
.kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    height: 100%;
    transition: box-shadow .15s;
}
.kpi-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.07); }
.kpi-card--sm   { padding: 1rem 1.25rem; }

.kpi-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #94a3b8;
    margin-bottom: .35rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.kpi-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: .35rem;
    font-variant-numeric: tabular-nums;
}
.kpi-value--sm { font-size: 1.3rem; }
.kpi-sub { font-size: .78rem; }

.kpi-tooltip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    font-size: .65rem;
    cursor: help;
    font-style: normal;
}
</style>

<!-- ════════════════════════════════════════════════
     CHARTS
════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels12   = <?= json_encode($labels12) ?>;
const datos12    = <?= json_encode(array_values($m['ingresos_12_meses'])) ?>;
const clientes12 = <?= json_encode(array_values($m['clientes_12_meses'])) ?>;
const planesDatos = <?= json_encode(array_values(array_column($m['por_plan'], 'cantidad'))) ?>;
const planesNombres = <?= json_encode(array_values(array_column($m['por_plan'], 'nombre'))) ?>;
const planesColores = <?= json_encode(array_values(array_column($m['por_plan'], 'color'))) ?>;

// ── Ingresos 12 meses (área) ──
new Chart(document.getElementById('chartIngresos').getContext('2d'), {
    type: 'line',
    data: {
        labels: labels12,
        datasets: [{
            label: 'Ingresos RD$',
            data: datos12,
            borderColor: '#1a56db',
            backgroundColor: 'rgba(26,86,219,0.08)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1a56db',
            pointRadius: 4,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' RD$' + ctx.parsed.y.toLocaleString('es-DO')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: v => 'RD$' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v),
                    font: { size: 11 }
                }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// ── Distribución por plan (donut) ──
if (document.getElementById('chartPlanes') && planesDatos.length > 0) {
    new Chart(document.getElementById('chartPlanes').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: planesNombres,
            datasets: [{
                data: planesDatos,
                backgroundColor: planesColores.map(c => c + 'cc'),
                borderColor: planesColores,
                borderWidth: 2,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} cliente${ctx.parsed !== 1 ? 's' : ''}`
                    }
                }
            }
        }
    });
}

// ── Nuevos clientes 12 meses (barras) ──
new Chart(document.getElementById('chartClientes').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels12,
        datasets: [{
            label: 'Nuevos',
            data: clientes12,
            backgroundColor: 'rgba(16,185,129,0.2)',
            borderColor: '#10b981',
            borderWidth: 2,
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { size: 11 } },
                grid: { color: '#f1f5f9' }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
</script>