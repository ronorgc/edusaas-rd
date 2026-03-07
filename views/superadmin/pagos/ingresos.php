<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$totalFiltrado = array_sum(array_column($pagos, 'monto'));
?>

<!-- Filtros -->
<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Año</label>
                <select name="anio" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $anioActual == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Mes</label>
                <select name="mes" class="form-select">
                    <option value="0" <?= $mesActual == 0 ? 'selected' : '' ?>>Todos los meses</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $mesActual == $m ? 'selected' : '' ?>><?= $mesesNombres[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-md-4 text-end">
                <div class="text-muted small mb-1">Total del período</div>
                <div class="fw-bold fs-5 text-success mb-2">RD$<?= number_format($totalFiltrado, 2) ?></div>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= $appUrl ?>/superadmin/ingresos/exportar?formato=csv&anio=<?= $anioActual ?>&mes=<?= $mesActual ?>"
                       class="btn btn-sm btn-outline-success" title="Descargar Excel/CSV">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
                    </a>
                    <a href="<?= $appUrl ?>/superadmin/ingresos/exportar?formato=pdf&anio=<?= $anioActual ?>&mes=<?= $mesActual ?>"
                       target="_blank"
                       class="btn btn-sm btn-outline-danger" title="Ver reporte PDF">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Barra de exportación -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= $appUrl ?>/superadmin/ingresos/exportar?formato=csv&anio=<?= $anioActual ?>&mes=<?= $mesActual ?>"
       class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar a Excel / CSV
    </a>
    <a href="<?= $appUrl ?>/superadmin/ingresos/exportar?formato=pdf&anio=<?= $anioActual ?>&mes=<?= $mesActual ?>"
       target="_blank" class="btn btn-outline-danger">
        <i class="bi bi-file-earmark-pdf me-1"></i>Ver reporte PDF / Imprimir
    </a>
</div>

<!-- Gráfico anual -->
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Ingresos por mes — <?= $anioActual ?>
    </div>
    <div class="card-body">
        <canvas id="chartIngresos" height="100"></canvas>
    </div>
</div>

<!-- Tabla de pagos -->
<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-2 text-success"></i>Pagos registrados (<?= count($pagos) ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Institución</th>
                        <th>Plan</th>
                        <th>Período cubierto</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Monto</th>
                        <th>Fecha pago</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay pagos en este período.</td></tr>
                <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><code class="small"><?= htmlspecialchars($p['numero_factura']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['institucion_nombre']) ?></td>
                    <td>
                        <span class="badge" style="background:#e0e7ff;color:#3730a3">
                            <?= htmlspecialchars($p['plan_nombre']) ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= date('d/m/Y', strtotime($p['periodo_desde'])) ?> –
                        <?= date('d/m/Y', strtotime($p['periodo_hasta'])) ?>
                    </td>
                    <td class="text-capitalize"><?= htmlspecialchars($p['metodo_pago']) ?></td>
                    <td class="small"><?= htmlspecialchars($p['referencia'] ?? '—') ?></td>
                    <td class="fw-semibold text-success">RD$<?= number_format($p['monto'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($pagos)): ?>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="6" class="text-end">Total:</td>
                        <td class="text-success">RD$<?= number_format($totalFiltrado, 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartIngresos').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values(array_slice($mesesNombres, 1))) ?>,
        datasets: [{
            label: 'Ingresos RD$',
            data: <?= json_encode(array_values($ingresosMes)) ?>,
            backgroundColor: 'rgba(26,86,219,0.15)',
            borderColor: '#1a56db',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'RD$' + v.toLocaleString() }
            }
        }
    }
});
</script>