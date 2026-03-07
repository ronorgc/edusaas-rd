<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card" style="border-top: 4px solid <?= htmlspecialchars($plan['color']) ?>">
    <div class="card-header fw-bold">
        <i class="bi <?= htmlspecialchars($plan['icono']) ?> me-2" style="color:<?= htmlspecialchars($plan['color']) ?>"></i>
        Editar Plan: <?= htmlspecialchars($plan['nombre']) ?>
    </div>
    <div class="card-body">
        <form action="<?= $appUrl ?>/superadmin/planes/<?= $plan['id'] ?>/editar" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nombre del plan</label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($plan['nombre']) ?>" required>
                </div>
                <div class="col-3">
                    <label class="form-label fw-semibold">Color</label>
                    <input type="color" name="color" class="form-control form-control-color w-100"
                           value="<?= htmlspecialchars($plan['color']) ?>">
                </div>
                <div class="col-3">
                    <label class="form-label fw-semibold">Ícono</label>
                    <select name="icono" class="form-select">
                        <?php
                        $iconos = ['bi-box','bi-briefcase-fill','bi-star-fill','bi-gem',
                                   'bi-rocket-takeoff','bi-lightning-fill','bi-shield-fill-check','bi-buildings'];
                        foreach ($iconos as $ic):
                        ?>
                        <option value="<?= $ic ?>" <?= $plan['icono'] === $ic ? 'selected' : '' ?>><?= $ic ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($plan['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Precio mensual (RD$)</label>
                    <input type="number" name="precio_mensual" class="form-control" value="<?= $plan['precio_mensual'] ?>" step="0.01" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Precio anual (RD$)</label>
                    <input type="number" name="precio_anual" class="form-control" value="<?= $plan['precio_anual'] ?>" step="0.01" required>
                </div>
            </div>

            <h6 class="fw-bold mb-3 mt-4">Límites <span class="fw-normal text-muted">(0 = ilimitado)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-4">
                    <label class="form-label fw-semibold">Estudiantes</label>
                    <input type="number" name="max_estudiantes" class="form-control" value="<?= $plan['max_estudiantes'] ?>" min="0">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Profesores</label>
                    <input type="number" name="max_profesores" class="form-control" value="<?= $plan['max_profesores'] ?>" min="0">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Secciones</label>
                    <input type="number" name="max_secciones" class="form-control" value="<?= $plan['max_secciones'] ?>" min="0">
                </div>
            </div>

            <h6 class="fw-bold mb-3">Funcionalidades incluidas</h6>
            <div class="row g-2">
                <?php
                $features = [
                    'incluye_pagos'       => ['Módulo de Pagos y Cuotas',    'cash-stack'],
                    'incluye_reportes'    => ['Reportes PDF y Excel',         'file-earmark-pdf'],
                    'incluye_comunicados' => ['Comunicados a Padres',          'megaphone-fill'],
                    'incluye_api'         => ['Acceso a API REST',             'code-slash'],
                ];
                foreach ($features as $key => [$label, $icon]):
                ?>
                <div class="col-6">
                    <div class="form-check border rounded-3 p-3">
                        <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $key ?>" <?= $plan[$key] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="<?= $key ?>">
                            <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?= $appUrl ?>/superadmin/planes" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>