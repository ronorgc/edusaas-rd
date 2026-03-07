<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= $appUrl ?>/superadmin/cobros/masivo" class="btn btn-warning btn-sm">
        <i class="bi bi-lightning-fill me-1"></i>Renovación masiva
    </a>
</div>

<div class="row g-4">

    <!-- ── PANEL IZQUIERDO: Buscar colegio y cobrar ── -->
    <div class="col-lg-5">

        <!-- Buscador de institución -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-search me-2 text-primary"></i>Buscar Institución
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="buscador" class="form-control form-control-lg"
                           placeholder="🔍 Escribe el nombre del colegio..."
                           autocomplete="off">
                </div>

                <!-- Lista de resultados del buscador -->
                <div id="lista-instituciones">
                    <?php foreach ($instituciones as $i):
                        $estado  = $i['suscripcion_estado'] ?? 'sin_plan';
                        $diasR   = (int)($i['dias_restantes'] ?? 0);
                        $clrEstado = match($estado) {
                            'activa'     => $diasR <= 7 ? '#ca8a04' : '#16a34a',
                            'vencida'    => '#dc2626',
                            'suspendida' => '#6b7280',
                            default      => '#94a3b8',
                        };
                        $lblEstado = match($estado) {
                            'activa'     => $diasR <= 7 ? "⚠️ Vence en {$diasR}d" : '✅ Activa',
                            'vencida'    => '❌ Vencida',
                            'suspendida' => '⏸️ Suspendida',
                            default      => '—',
                        };
                    ?>
                    <div class="inst-item" data-nombre="<?= htmlspecialchars(strtolower($i['nombre'])) ?>">
                        <button type="button"
                                class="btn-inst w-100 text-start"
                                onclick="cargarFormulario(<?= $i['id'] ?>)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($i['nombre']) ?></div>
                                    <div class="text-muted" style="font-size:.78rem">
                                        <?= htmlspecialchars($i['plan_nombre'] ?? 'Sin plan') ?>
                                        <?php if ($i['fecha_vencimiento']): ?>
                                        · Vence <?= date('d/m/Y', strtotime($i['fecha_vencimiento'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span style="color:<?= $clrEstado ?>;font-size:.78rem;font-weight:600;white-space:nowrap">
                                    <?= $lblEstado ?>
                                </span>
                            </div>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Formulario de cobro (se carga aquí dinámicamente) -->
        <div id="panel-formulario" style="display:none">
            <div class="card">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cash-coin me-2 text-success"></i>Registrar Pago</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="cerrarFormulario()">✕ Cancelar</button>
                </div>
                <div class="card-body" id="contenido-formulario">
                    <!-- Se inyecta via fetch -->
                </div>
            </div>
        </div>

    </div>

    <!-- ── PANEL DERECHO: Pagos del día ── -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-calendar-check me-2 text-success"></i>
                    Cobros de hoy — <?= date('d/m/Y') ?>
                </span>
                <span class="fw-bold text-success fs-6">
                    RD$<?= number_format($totalHoy, 2) ?>
                </span>
            </div>
            <div class="card-body p-0" id="tabla-cobros-hoy">
                <?php if (empty($pagosHoy)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    Sin cobros registrados hoy
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Colegio</th>
                                <th>Plan</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pagosHoy as $p): ?>
                        <tr>
                            <td><code class="small"><?= htmlspecialchars($p['numero_factura']) ?></code></td>
                            <td class="fw-semibold small"><?= htmlspecialchars($p['institucion_nombre']) ?></td>
                            <td>
                                <span class="badge" style="background:#e0e7ff;color:#3730a3">
                                    <?= htmlspecialchars($p['plan_nombre']) ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-success">RD$<?= number_format($p['monto'], 2) ?></td>
                            <td class="text-capitalize small"><?= htmlspecialchars($p['metodo_pago']) ?></td>
                            <td>
                                <a href="<?= $appUrl ?>/superadmin/cobros/recibo/<?= $p['id'] ?>"
                                   target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir recibo">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<style>
.btn-inst {
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .75rem 1rem;
    margin-bottom: .4rem;
    transition: all .15s;
    cursor: pointer;
    width: 100%;
}
.btn-inst:hover {
    background: #eff6ff;
    border-color: #1a56db;
}
.btn-inst.seleccionado {
    background: #eff6ff;
    border-color: #1a56db;
    box-shadow: 0 0 0 2px rgba(26,86,219,.15);
}
#lista-instituciones {
    max-height: 380px;
    overflow-y: auto;
}
</style>

<script>
const appUrl = '<?= $appUrl ?>';

// ── Filtro de búsqueda en tiempo real ──
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.inst-item').forEach(item => {
        item.style.display = item.dataset.nombre.includes(q) ? '' : 'none';
    });
});

// ── Cargar formulario de una institución ──
function cargarFormulario(instId) {
    // Marcar botón seleccionado
    document.querySelectorAll('.btn-inst').forEach(b => b.classList.remove('seleccionado'));
    event.currentTarget.classList.add('seleccionado');

    document.getElementById('panel-formulario').style.display = '';
    document.getElementById('contenido-formulario').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch(appUrl + '/superadmin/cobros/formulario?inst_id=' + instId)
        .then(r => r.text())
        .then(html => {
            document.getElementById('contenido-formulario').innerHTML = html;
            // Scroll al formulario en móvil
            document.getElementById('panel-formulario').scrollIntoView({ behavior: 'smooth' });
            // Inicializar cálculo de montos
            actualizarMonto();
        })
        .catch(() => {
            document.getElementById('contenido-formulario').innerHTML =
                '<p class="text-danger text-center">Error al cargar el formulario.</p>';
        });
}

function cerrarFormulario() {
    document.getElementById('panel-formulario').style.display = 'none';
    document.querySelectorAll('.btn-inst').forEach(b => b.classList.remove('seleccionado'));
}

// ── Descuento ──────────────────────────────────────────────────
function toggleDescuento() {
    const b = document.getElementById('bloque-descuento');
    if (!b) return;
    b.style.display = b.style.display === 'none' ? 'block' : 'none';
}

function calcularDescuento() {
    const tipoEl  = document.getElementById('desc-tipo');
    const valorEl = document.getElementById('desc-valor');
    const montoEl = document.getElementById('campo-monto');
    const res     = document.getElementById('desc-resultado');
    if (!tipoEl || !valorEl || !montoEl) return;

    const tipo   = tipoEl.value;
    const valor  = parseFloat(valorEl.value) || 0;

    // Monto BASE: siempre recalcular desde el plan para evitar doble descuento
    const planSel  = document.getElementById('sel-plan');
    const tipoFac  = document.getElementById('sel-tipo');
    const opt      = planSel ? planSel.options[planSel.selectedIndex] : null;
    const montoBase = opt
        ? parseFloat(tipoFac?.value === 'anual' ? opt.dataset.anual : opt.dataset.mensual) || 0
        : parseFloat(montoEl.value) || 0;

    if (!tipo || !valor || !montoBase) {
        if (res) res.textContent = '';
        // Restaurar monto original si se borra el descuento
        if (montoEl && !montoEl.dataset.editado) montoEl.value = montoBase.toFixed(2);
        return;
    }

    const descuento = tipo === 'pct'
        ? Math.min(montoBase * valor / 100, montoBase)
        : Math.min(valor, montoBase);
    const final = Math.max(0, montoBase - descuento);

    // Actualizar el campo de monto con el valor final (esto es lo que se envía)
    montoEl.value = final.toFixed(2);
    delete montoEl.dataset.editado; // permitir que actualizarMonto lo sobreescriba si cambia plan

    if (res) {
        res.innerHTML =
            `<span class="text-danger fw-semibold">–RD$${descuento.toFixed(2)}</span><br>` +
            `<span class="text-success fw-bold">RD$${final.toFixed(2)}</span>`;
    }
}

// Función global para actualizar el monto sugerido al cambiar plan/tipo
function actualizarMonto() {
    const planSel = document.getElementById('sel-plan');
    const tipoSel = document.getElementById('sel-tipo');
    if (!planSel || !tipoSel) return;

    const opt    = planSel.options[planSel.selectedIndex];
    const mensual = parseFloat(opt.dataset.mensual || 0);
    const anual   = parseFloat(opt.dataset.anual   || 0);
    const monto   = tipoSel.value === 'anual' ? anual : mensual;

    const campoMonto = document.getElementById('campo-monto');
    if (campoMonto && !campoMonto.dataset.editado) {
        campoMonto.value = monto.toFixed(2);
    }

    const previewMonto = document.getElementById('preview-monto');
    if (previewMonto) {
        previewMonto.textContent = 'RD$' + (parseFloat(campoMonto?.value || monto)).toLocaleString('es-DO', {minimumFractionDigits:2});
    }
}
</script>