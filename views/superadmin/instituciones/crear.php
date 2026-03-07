<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="row justify-content-center">
<div class="col-lg-9">

<div class="card">
    <div class="card-header fw-semibold">
        <i class="bi bi-building-add me-2 text-primary"></i>Datos de la Institución
    </div>
    <div class="card-body">
        <form action="<?= $appUrl ?>/superadmin/instituciones/crear" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nombre del colegio <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej: Colegio San Juan Bautista">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option value="privado">Privado</option>
                        <option value="publico">Público</option>
                    </select>
                </div>

                <!-- Identificador URL -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Identificador URL <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text text-muted">/preinscripcion/</span>
                        <input type="text" name="subdomain" id="subdomain" class="form-control" required
                               placeholder="sanjuan" pattern="[a-z0-9\-]+"
                               title="Solo letras minúsculas, números y guiones">
                    </div>
                    <div class="form-text">Solo letras minúsculas, números y guiones. Define la URL del formulario de pre-inscripción.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Código MINERD</label>
                    <input type="text" name="codigo_minerd" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="809-000-0000">
                </div>

                <!-- Contacto -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="director@colegio.edu.do">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Municipio</label>
                    <input type="text" name="municipio" class="form-control" placeholder="Santo Domingo">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Provincia</label>
                    <input type="text" name="provincia" class="form-control" placeholder="Distrito Nacional">
                </div>
            </div>

            <hr class="my-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-success"></i>Plan y Suscripción</h6>

            <div class="row g-3 mb-3">
                <!-- Selector de planes -->
                <?php foreach ($planes as $plan): ?>
                <div class="col-md-4">
                    <div class="plan-card border rounded-3 p-3 h-100" style="cursor:pointer;" onclick="seleccionarPlan(<?= $plan['id'] ?>, this)">
                        <input type="radio" name="plan_id" value="<?= $plan['id'] ?>" class="d-none" <?= $plan['nombre'] === 'Profesional' ? 'checked' : '' ?>>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= htmlspecialchars($plan['icono']) ?> fs-4" style="color:<?= htmlspecialchars($plan['color']) ?>"></i>
                            <span class="fw-bold"><?= htmlspecialchars($plan['nombre']) ?></span>
                        </div>
                        <div class="fw-bold fs-5" style="color:<?= htmlspecialchars($plan['color']) ?>">
                            RD$<?= number_format($plan['precio_mensual'], 0) ?><span class="fw-normal text-muted fs-6">/mes</span>
                        </div>
                        <ul class="list-unstyled small text-muted mt-2 mb-0">
                            <li><i class="bi bi-people me-1"></i><?= $plan['max_estudiantes'] ?: '∞' ?> estudiantes</li>
                            <li><i class="bi bi-person-workspace me-1"></i><?= $plan['max_profesores'] ?: '∞' ?> profesores</li>
                            <?php if ($plan['incluye_pagos']): ?>
                            <li><i class="bi bi-cash-stack me-1 text-success"></i>Módulo de pagos</li>
                            <?php endif; ?>
                            <?php if ($plan['incluye_reportes']): ?>
                            <li><i class="bi bi-file-earmark-pdf me-1 text-danger"></i>Reportes PDF</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo de suscripción</label>
                    <select name="tipo_susc" id="tipo_susc" class="form-select"
                            onchange="toggleTrialDias()">
                        <option value="normal">Normal (cobrado)</option>
                        <option value="trial">🧪 Trial gratuito</option>
                    </select>
                </div>
                <div class="col-md-3" id="bloque_trial_dias" style="display:none">
                    <label class="form-label fw-semibold">Días de prueba</label>
                    <select name="trial_dias" class="form-select">
                        <option value="7">7 días</option>
                        <option value="14" selected>14 días</option>
                        <option value="30">30 días</option>
                        <option value="60">60 días</option>
                    </select>
                </div>
                <div class="col-md-4" id="bloque_facturacion">
                    <label class="form-label fw-semibold">Facturación</label>
                    <select name="tipo_facturacion" class="form-select">
                        <option value="mensual">Mensual</option>
                        <option value="anual">Anual (con descuento)</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-lock me-2 text-primary"></i>Credenciales del Administrador</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Username (auto-generado)</label>
                    <input type="text" id="preview_username" class="form-control bg-light" readonly placeholder="Se genera del identificador">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contraseña inicial</label>
                    <input type="text" name="password_admin" class="form-control" value="Colegio2024!" required>
                    <div class="form-text text-warning">⚠️ Comunica esta contraseña al administrador del colegio.</div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Checkbox pago inicial -->
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="registrar_pago" id="registrar_pago" checked>
                <label class="form-check-label fw-semibold" for="registrar_pago">
                    Registrar pago inicial
                </label>
            </div>
            <div id="bloque_pago" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Método de pago</label>
                    <select name="metodo_pago" class="form-select">
                        <option value="transferencia">Transferencia</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Referencia (opcional)</label>
                    <input type="text" name="referencia" class="form-control" placeholder="Número de transferencia, etc.">
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?= $appUrl ?>/superadmin/instituciones" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Crear Institución
                </button>
            </div>
        </form>
    </div>
</div>

</div>
</div>

<style>
.plan-card { transition: all .2s; border-color: #e2e8f0 !important; }
.plan-card:hover { border-color: #1a56db !important; background: #f0f6ff; }
.plan-card.selected { border-color: #1a56db !important; background: #eff6ff; box-shadow: 0 0 0 2px #1a56db33; }
</style>
<script>
function toggleTrialDias() {
    const tipo    = document.getElementById('tipo_susc').value;
    const esTrial = tipo === 'trial';
    document.getElementById('bloque_trial_dias').style.display    = esTrial ? 'block' : 'none';
    document.getElementById('bloque_facturacion').style.display   = esTrial ? 'none'  : 'block';
    const regPago = document.getElementById('registrar_pago');
    if (regPago) {
        regPago.disabled = esTrial;
        if (esTrial) { regPago.checked = false; document.getElementById('bloque_pago').style.display = 'none'; }
    }
}

function seleccionarPlan(id, el) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
}
// Seleccionar Profesional por defecto
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name=plan_id]:checked');
    if (checked) checked.closest('.plan-card').classList.add('selected');

    // Preview de username
    document.getElementById('subdomain').addEventListener('input', function() {
        document.getElementById('preview_username').value = this.value + '_admin';
    });

    // Toggle bloque pago
    document.getElementById('registrar_pago').addEventListener('change', function() {
        document.getElementById('bloque_pago').style.display = this.checked ? '' : 'none';
    });
});
</script>