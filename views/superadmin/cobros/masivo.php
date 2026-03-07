<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$hoy    = date('Y-m-d');

// Separar vencidos de por vencer
$vencidos   = array_filter($pendientes, fn($p) => (int)$p['dias_restantes'] < 0);
$porVencer  = array_filter($pendientes, fn($p) => (int)$p['dias_restantes'] >= 0);
$totalEstimado = array_sum(array_map(fn($p) =>
    $p['tipo_facturacion'] === 'anual' ? $p['precio_anual'] : $p['precio_mensual'],
    $pendientes
));
?>

<!-- ── Encabezado ─────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <p class="text-muted mb-0">
            <strong><?= count($vencidos) ?></strong> vencidos ·
            <strong><?= count($porVencer) ?></strong> vencen en los próximos 30 días ·
            Ingreso estimado: <strong class="text-success">RD$<?= number_format($totalEstimado, 0) ?></strong>
        </p>
    </div>
    <a href="<?= $appUrl ?>/superadmin/cobros" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Cobros individuales
    </a>
</div>

<?php if (empty($pendientes)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
        <h5 class="fw-bold">Todo al día</h5>
        <p class="text-muted">No hay colegios vencidos ni por vencer en los próximos 30 días.</p>
    </div>
</div>
<?php else: ?>

<form method="POST" action="<?= $appUrl ?>/superadmin/cobros/masivo/procesar" id="formMasivo">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<!-- ── Opciones globales ──────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Método de pago</label>
                <select name="metodo_pago" class="form-select form-select-sm">
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="cheque">Cheque</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tipo de facturación</label>
                <select name="tipo_facturacion" class="form-select form-select-sm" id="tipoFacGlobal"
                        onchange="actualizarTodosLosMontol(this.value)">
                    <option value="mensual">Mensual</option>
                    <option value="anual">Anual</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="selTodos"
                           onchange="toggleTodos(this.checked)">
                    <label class="form-check-label fw-semibold" for="selTodos">
                        Seleccionar todos
                    </label>
                </div>
            </div>
            <div class="col-md-3 text-end">
                <div class="text-muted small mb-1">Total seleccionado:</div>
                <div class="fw-bold fs-5 text-success" id="totalSeleccionado">RD$0</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Tabla de colegios ──────────────────────────────── -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"></th>
                        <th>Colegio</th>
                        <th>Plan actual</th>
                        <th>Vencimiento</th>
                        <th style="width:180px">Plan a renovar</th>
                        <th style="width:140px">Monto (RD$)</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                // Primero vencidos, luego por vencer
                $todos = array_merge(array_values($vencidos), array_values($porVencer));
                foreach ($todos as $p):
                    $dias   = (int)$p['dias_restantes'];
                    $vencido = $dias < 0;
                    $urgente = $dias >= 0 && $dias <= 7;
                    $montoDefault = $p['tipo_facturacion'] === 'anual'
                        ? $p['precio_anual'] : $p['precio_mensual'];
                ?>
                <tr class="fila-colegio <?= $vencido ? 'table-danger' : ($urgente ? 'table-warning' : '') ?>"
                    data-inst="<?= $p['id'] ?>"
                    data-plan="<?= $p['plan_id'] ?>"
                    data-mensual="<?= $p['precio_mensual'] ?>"
                    data-anual="<?= $p['precio_anual'] ?>">

                    <td class="text-center">
                        <input type="checkbox" name="seleccionados[]"
                               value="<?= $p['id'] ?>"
                               class="form-check-input check-colegio"
                               onchange="recalcularTotal()">
                    </td>

                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($p['email'] ?? '') ?></div>
                    </td>

                    <td>
                        <span class="badge px-2"
                              style="background:<?= htmlspecialchars($p['plan_color']) ?>22;
                                     color:<?= htmlspecialchars($p['plan_color']) ?>;
                                     border:1px solid <?= htmlspecialchars($p['plan_color']) ?>44">
                            <?= htmlspecialchars($p['plan_nombre']) ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($vencido): ?>
                            <span class="badge bg-danger">Vencido hace <?= abs($dias) ?>d</span>
                        <?php elseif ($urgente): ?>
                            <span class="badge bg-warning text-dark">Vence en <?= $dias ?>d</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">
                                <?= date('d/m/Y', strtotime($p['fecha_vencimiento'])) ?> (<?= $dias ?>d)
                            </span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <select name="plan_<?= $p['id'] ?>"
                                class="form-select form-select-sm sel-plan"
                                data-inst="<?= $p['id'] ?>"
                                onchange="actualizarMontoPlan(this)">
                            <?php foreach ($planes as $pl): ?>
                            <option value="<?= $pl['id'] ?>"
                                    data-mensual="<?= $pl['precio_mensual'] ?>"
                                    data-anual="<?= $pl['precio_anual'] ?>"
                                    <?= $pl['id'] == $p['plan_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">RD$</span>
                            <input type="number" name="monto_<?= $p['id'] ?>"
                                   class="form-control campo-monto"
                                   data-inst="<?= $p['id'] ?>"
                                   value="<?= number_format($montoDefault, 2, '.', '') ?>"
                                   step="0.01" min="0"
                                   onchange="recalcularTotal()">
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Botón procesar ─────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        Se generará una factura y se enviará email de confirmación por cada colegio seleccionado.
    </div>
    <button type="submit" class="btn btn-success px-4" id="btnProcesar" disabled
            onclick="return confirm('¿Confirmas la renovación de los colegios seleccionados?')">
        <i class="bi bi-check-all me-2"></i>
        Renovar seleccionados
        <span class="badge bg-white text-success ms-1" id="badgeCount">0</span>
    </button>
</div>

</form>
<?php endif; ?>

<script>
const planesData = <?= json_encode(array_values($planes)) ?>;

function toggleTodos(checked) {
    document.querySelectorAll('.check-colegio').forEach(c => c.checked = checked);
    recalcularTotal();
}

function actualizarTodosLosMontol(tipo) {
    document.querySelectorAll('.fila-colegio').forEach(fila => {
        const sel    = fila.querySelector('.sel-plan');
        const opt    = sel.options[sel.selectedIndex];
        const monto  = tipo === 'anual'
            ? parseFloat(opt.dataset.anual  || 0)
            : parseFloat(opt.dataset.mensual || 0);
        const campo  = fila.querySelector('.campo-monto');
        if (campo) campo.value = monto.toFixed(2);
    });
    recalcularTotal();
}

function actualizarMontoPlan(sel) {
    const instId = sel.dataset.inst;
    const opt    = sel.options[sel.selectedIndex];
    const tipo   = document.getElementById('tipoFacGlobal').value;
    const monto  = tipo === 'anual'
        ? parseFloat(opt.dataset.anual  || 0)
        : parseFloat(opt.dataset.mensual || 0);
    const campo  = document.querySelector(`[name="monto_${instId}"]`);
    if (campo) campo.value = monto.toFixed(2);
    recalcularTotal();
}

function recalcularTotal() {
    let total = 0, count = 0;
    document.querySelectorAll('.check-colegio:checked').forEach(chk => {
        const instId = chk.value;
        const campo  = document.querySelector(`[name="monto_${instId}"]`);
        total += parseFloat(campo?.value || 0);
        count++;
    });
    document.getElementById('totalSeleccionado').textContent =
        'RD$' + total.toLocaleString('es-DO', {minimumFractionDigits: 0});
    document.getElementById('badgeCount').textContent = count;
    document.getElementById('btnProcesar').disabled   = count === 0;
}
</script>