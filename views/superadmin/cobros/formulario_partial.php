<?php
// Este partial es cargado vía fetch desde cobros/index.php
// Variables disponibles: $inst, $suscActiva, $planes, $csrf_token (pasado por el controller)
$appUrl = APP_URL; // ADR-016: constante global, no require por vista
?>

<!-- Info rápida de la institución -->
<div class="d-flex align-items-start gap-3 mb-4 p-3 rounded-3"
     style="background:#f0f6ff;border:1px solid #bfdbfe">
    <div class="flex-grow-1">
        <div class="fw-bold fs-6"><?= htmlspecialchars($inst['nombre']) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($inst['email'] ?? '') ?></div>
        <?php if ($suscActiva): ?>
        <div class="mt-1">
            <span class="badge" style="background:#dcfce7;color:#166534">
                Plan actual: <?= htmlspecialchars($suscActiva['plan_nombre']) ?>
            </span>
            <span class="badge bg-light text-muted ms-1">
                Vence: <?= date('d/m/Y', strtotime($suscActiva['fecha_vencimiento'])) ?>
            </span>
        </div>
        <?php else: ?>
        <span class="badge badge-inactivo mt-1">Sin suscripción activa</span>
        <?php endif; ?>
    </div>
</div>

<form action="<?= $appUrl ?>/superadmin/cobros/procesar" method="POST">
    <input type="hidden" name="_csrf_token"    value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <input type="hidden" name="institucion_id" value="<?= $inst['id'] ?>">

    <!-- Plan -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Plan <span class="text-danger">*</span></label>
        <select name="plan_id" id="sel-plan" class="form-select" required onchange="actualizarMonto()">
            <?php foreach ($planes as $pl): ?>
            <option value="<?= $pl['id'] ?>"
                    data-mensual="<?= $pl['precio_mensual'] ?>"
                    data-anual="<?= $pl['precio_anual'] ?>"
                    <?= ($suscActiva && $suscActiva['plan_id'] == $pl['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($pl['nombre']) ?>
                — RD$<?= number_format($pl['precio_mensual'], 0) ?>/mes
                · RD$<?= number_format($pl['precio_anual'], 0) ?>/año
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Tipo facturación -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Período</label>
        <div class="d-flex gap-2">
            <div class="form-check form-check-inline flex-fill">
                <input class="form-check-input" type="radio" name="tipo_facturacion"
                       id="tipo_mensual" value="mensual"
                       <?= (!$suscActiva || $suscActiva['tipo_facturacion'] === 'mensual') ? 'checked' : '' ?>
                       onchange="actualizarMonto()">
                <label class="form-check-label" for="tipo_mensual">
                    Mensual <small class="text-muted">(1 mes)</small>
                </label>
            </div>
            <div class="form-check form-check-inline flex-fill">
                <input class="form-check-input" type="radio" name="tipo_facturacion"
                       id="tipo_anual" value="anual"
                       <?= ($suscActiva && $suscActiva['tipo_facturacion'] === 'anual') ? 'checked' : '' ?>
                       onchange="actualizarMonto()">
                <label class="form-check-label" for="tipo_anual">
                    Anual <small class="text-success">(ahorra ~17%)</small>
                </label>
            </div>
        </div>
    </div>

    <!-- Monto -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Monto a cobrar (RD$)</label>
        <div class="input-group">
            <span class="input-group-text fw-bold">RD$</span>
            <input type="number" name="monto_custom" id="campo-monto"
                   class="form-control form-control-lg fw-bold"
                   step="0.01" min="0" required
                   placeholder="0.00"
                   oninput="this.dataset.editado='1'; actualizarMonto()"
                   style="font-size:1.3rem;color:#1a56db">
        </div>
        <div id="preview-monto" class="text-muted small mt-1"></div>
        <div class="form-text">Puedes modificar el monto si acordaste un precio diferente.</div>
    </div>

    <!-- Método de pago -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Método de pago <span class="text-danger">*</span></label>
        <div class="row g-2">
            <?php
            $metodos = [
                'transferencia' => ['🏦', 'Transferencia'],
                'efectivo'      => ['💵', 'Efectivo'],
                'cheque'        => ['📝', 'Cheque'],
                'tarjeta'       => ['💳', 'Tarjeta'],
            ];
            foreach ($metodos as $val => [$icon, $label]):
            ?>
            <div class="col-6">
                <div class="form-check metodo-card border rounded-3 p-2">
                    <input class="form-check-input" type="radio" name="metodo_pago"
                           id="met_<?= $val ?>" value="<?= $val ?>"
                           <?= $val === 'transferencia' ? 'checked' : '' ?>>
                    <label class="form-check-label d-block text-center" for="met_<?= $val ?>"
                           style="cursor:pointer;font-size:.9rem">
                        <span style="font-size:1.25rem"><?= $icon ?></span><br>
                        <?= $label ?>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Referencia -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Referencia / Comprobante</label>
        <input type="text" name="referencia" class="form-control"
               placeholder="Ej: Trans. #00123456, Cheque #456...">
        <div class="form-text">Número de transferencia, cheque u otra referencia.</div>
    </div>

    <!-- Descuento -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-semibold mb-0">Descuento (opcional)</label>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="toggleDescuento()">
                <i class="bi bi-tag me-1"></i>Aplicar descuento
            </button>
        </div>
        <div id="bloque-descuento" style="display:none">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Tipo</label>
                            <select name="descuento_tipo" id="desc-tipo" class="form-select form-select-sm"
                                    onchange="calcularDescuento()">
                                <option value="">Sin descuento</option>
                                <option value="pct">Porcentaje (%)</option>
                                <option value="monto">Monto fijo (RD$)</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Valor</label>
                            <input type="number" name="descuento_valor" id="desc-valor"
                                   class="form-control form-control-sm" min="0" step="0.01"
                                   placeholder="0" oninput="calcularDescuento()">
                        </div>
                        <div class="col-3">
                            <div id="desc-resultado" class="text-success fw-bold small text-center"></div>
                        </div>
                        <div class="col-12">
                            <input type="text" name="descuento_motivo" class="form-control form-control-sm"
                                   placeholder="Motivo (negociación, promoción, cortesía...)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notas internas -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Notas internas</label>
        <textarea name="notas" class="form-control" rows="2"
                  placeholder="Notas opcionales (no aparecen en el recibo)"></textarea>
    </div>

    <!-- Resumen antes de confirmar -->
    <div class="alert alert-primary py-2 small mb-3" id="resumen-pago">
        <i class="bi bi-info-circle me-1"></i>
        Selecciona el plan y período para ver el resumen.
    </div>

    <button type="submit" class="btn btn-success w-100 btn-lg fw-bold">
        <i class="bi bi-check-circle-fill me-2"></i>
        Confirmar Pago e Imprimir Recibo
    </button>
</form>

<style>
.metodo-card { cursor: pointer; transition: all .15s; }
.metodo-card:has(input:checked) { background: #eff6ff; border-color: #1a56db !important; }
</style>