<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card" id="cardPlan" style="border-top: 4px solid #1a56db">
    <div class="card-header fw-bold">
        <i class="bi bi-plus-circle me-2 text-primary"></i>Nuevo Plan
    </div>
    <div class="card-body">
        <form action="<?= $appUrl ?>/superadmin/planes/crear" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Nombre + Color + Ícono -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nombre del plan <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required
                           placeholder="Ej: Enterprise, Starter, Prueba..."
                           oninput="document.getElementById('previewNombre').textContent = this.value || 'Nuevo Plan'">
                </div>
                <div class="col-3">
                    <label class="form-label fw-semibold">Color</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" name="color" id="colorPicker" value="#1a56db"
                               class="form-control form-control-color"
                               oninput="aplicarColor(this.value)">
                        <span class="small text-muted" id="colorHex">#1a56db</span>
                    </div>
                </div>
                <div class="col-3">
                    <label class="form-label fw-semibold">Ícono</label>
                    <select name="icono" class="form-select" id="selectIcono"
                            onchange="document.getElementById('previewIcono').className='bi '+this.value+' fs-3'">
                        <option value="bi-box">📦 Básico</option>
                        <option value="bi-briefcase-fill" selected>💼 Profesional</option>
                        <option value="bi-star-fill">⭐ Premium</option>
                        <option value="bi-gem">💎 Enterprise</option>
                        <option value="bi-rocket-takeoff">🚀 Starter</option>
                        <option value="bi-lightning-fill">⚡ Pro</option>
                        <option value="bi-shield-fill-check">🛡️ Seguro</option>
                        <option value="bi-buildings">🏢 Corporate</option>
                    </select>
                </div>
            </div>

            <!-- Preview en vivo -->
            <div class="mb-4 p-3 rounded-3 d-flex align-items-center gap-3"
                 id="previewCard"
                 style="background:#f0f6ff;border-left:4px solid #1a56db">
                <i class="bi bi-briefcase-fill fs-3" id="previewIcono" style="color:#1a56db"></i>
                <div>
                    <div class="fw-bold" id="previewNombre">Nuevo Plan</div>
                    <div class="small text-muted">Vista previa del plan</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"
                          placeholder="Ideal para colegios medianos con múltiples secciones..."></textarea>
            </div>

            <!-- Precios -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Precio mensual (RD$) <span class="text-danger">*</span></label>
                    <input type="number" name="precio_mensual" class="form-control" required
                           min="0" step="0.01" placeholder="2500.00"
                           oninput="calcularDescuento()">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Precio anual (RD$)</label>
                    <input type="number" name="precio_anual" class="form-control"
                           min="0" step="0.01" placeholder="25000.00" id="precioAnual"
                           oninput="calcularDescuento()">
                    <div class="form-text" id="textoDescuento"></div>
                </div>
            </div>

            <!-- Límites -->
            <h6 class="fw-bold mb-3 mt-4">Límites <span class="fw-normal text-muted small">(0 = ilimitado)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-4">
                    <label class="form-label fw-semibold">Estudiantes</label>
                    <input type="number" name="max_estudiantes" class="form-control" value="0" min="0">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Profesores</label>
                    <input type="number" name="max_profesores" class="form-control" value="0" min="0">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Secciones</label>
                    <input type="number" name="max_secciones" class="form-control" value="0" min="0">
                </div>
            </div>

            <!-- Módulos -->
            <h6 class="fw-bold mb-3">Funcionalidades incluidas</h6>
            <div class="row g-2 mb-4">
                <?php
                $features = [
                    'incluye_pagos'       => ['Módulo de Pagos y Cuotas',  'cash-stack'],
                    'incluye_reportes'    => ['Reportes PDF y Excel',       'file-earmark-pdf'],
                    'incluye_comunicados' => ['Comunicados a Padres',       'megaphone-fill'],
                    'incluye_api'         => ['Acceso a API REST',          'code-slash'],
                ];
                foreach ($features as $key => [$label, $icon]):
                ?>
                <div class="col-6">
                    <div class="form-check border rounded-3 p-3">
                        <input class="form-check-input" type="checkbox"
                               name="<?= $key ?>" id="<?= $key ?>">
                        <label class="form-check-label fw-semibold" for="<?= $key ?>">
                            <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= $appUrl ?>/superadmin/planes" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Crear Plan
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
function aplicarColor(color) {
    document.getElementById('colorHex').textContent = color;
    document.getElementById('previewCard').style.borderLeftColor = color;
    document.getElementById('previewIcono').style.color = color;
    document.getElementById('cardPlan').style.borderTopColor = color;
}

function calcularDescuento() {
    const mensual = parseFloat(document.querySelector('[name=precio_mensual]').value) || 0;
    const anual   = parseFloat(document.getElementById('precioAnual').value) || 0;
    const txt     = document.getElementById('textoDescuento');
    if (mensual > 0 && anual > 0) {
        const ahorro = Math.round((mensual * 12 - anual) / (mensual * 12) * 100);
        txt.textContent = ahorro > 0
            ? `✅ El cliente ahorra un ${ahorro}% con el plan anual`
            : ahorro < 0 ? '⚠️ El precio anual es mayor que 12 meses' : '';
        txt.className = 'form-text ' + (ahorro > 0 ? 'text-success' : 'text-warning');
    } else {
        txt.textContent = '';
    }
}
</script>