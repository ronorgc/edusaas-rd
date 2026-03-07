<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<?php
$grupoLabels = [
    'empresa'     => ['label' => 'Empresa',      'icono' => 'bi-building'],
    'marca'       => ['label' => 'Marca',         'icono' => 'bi-palette-fill'],
    'facturacion' => ['label' => 'Facturación',   'icono' => 'bi-receipt'],
    'sistema'     => ['label' => 'Sistema',       'icono' => 'bi-gear-fill'],
];
$grupoKeys = array_keys($grupoLabels);
$primerGrupo = $grupoKeys[0];
?>

<form action="<?= $appUrl ?>/superadmin/configuracion" method="POST"
      enctype="multipart/form-data" id="formConfig">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<!-- Tabs de navegación -->
<ul class="nav nav-tabs mb-0 border-bottom-0" id="tabsConfig">
    <?php foreach ($grupoLabels as $key => $meta): ?>
    <li class="nav-item">
        <a class="nav-link <?= $key === $primerGrupo ? 'active' : '' ?>"
           data-bs-toggle="tab" href="#tab-<?= $key ?>">
            <i class="bi <?= $meta['icono'] ?> me-1"></i><?= $meta['label'] ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm" style="border-radius:0 0 12px 12px">
<div class="card-body">
<div class="tab-content pt-2">

<?php foreach ($grupoLabels as $grupoKey => $meta):
    $campos = $grupos[$grupoKey] ?? [];
?>
<div class="tab-pane fade <?= $grupoKey === $primerGrupo ? 'show active' : '' ?>"
     id="tab-<?= $grupoKey ?>">

    <?php if (empty($campos)): ?>
    <p class="text-muted text-center py-4">No hay configuraciones en este grupo aún.</p>
    <?php else: ?>

    <div class="row g-3 mt-1">
    <?php foreach ($campos as $campo):
        $clave = $campo['clave'];
        $valor = $campo['valor'] ?? '';
        $tipo  = $campo['tipo'];
        $desc  = $campo['descripcion'] ?? '';
        $label = ucwords(str_replace('_', ' ', explode('_', $clave, 2)[1] ?? $clave));
    ?>
        <div class="<?= $tipo === 'textarea' ? 'col-12' : 'col-md-6' ?>">
            <label class="form-label fw-semibold small"><?= htmlspecialchars($label) ?></label>

            <?php if ($tipo === 'textarea'): ?>
                <textarea name="<?= $clave ?>" class="form-control" rows="3"><?= htmlspecialchars($valor) ?></textarea>

            <?php elseif ($tipo === 'boolean'): ?>
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox"
                           name="<?= $clave ?>" id="<?= $clave ?>"
                           <?= $valor === '1' ? 'checked' : '' ?>
                           style="width:2.5rem;height:1.3rem">
                    <label class="form-check-label" for="<?= $clave ?>">
                        <?= $valor === '1' ? 'Activado' : 'Desactivado' ?>
                    </label>
                </div>

            <?php elseif ($tipo === 'color'): ?>
                <div class="d-flex align-items-center gap-2">
                    <input type="color" name="<?= $clave ?>"
                           class="form-control form-control-color"
                           value="<?= htmlspecialchars($valor ?: '#1a56db') ?>"
                           style="width:60px;height:38px">
                    <input type="text" class="form-control font-monospace"
                           value="<?= htmlspecialchars($valor) ?>"
                           oninput="document.querySelector('[name=<?= $clave ?>]').value=this.value"
                           style="max-width:110px">
                </div>

            <?php elseif ($tipo === 'image'): ?>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <?php if ($valor): ?>
                    <img src="<?= htmlspecialchars($valor) ?>?v=<?= time() ?>"
                         alt="Logo actual" style="height:48px;max-width:120px;object-fit:contain;border:1px solid #e2e8f0;border-radius:8px;padding:4px;background:#fff">
                    <?php else: ?>
                    <div class="text-muted small border rounded p-2" style="font-size:.75rem">Sin logo</div>
                    <?php endif; ?>
                    <div>
                        <input type="file" name="marca_logo_archivo"
                               class="form-control form-control-sm"
                               accept=".png,.jpg,.jpeg,.svg,.webp"
                               style="max-width:260px">
                        <div class="form-text">PNG, JPG, SVG o WebP — máx. 2MB</div>
                    </div>
                </div>
                <input type="hidden" name="<?= $clave ?>" value="<?= htmlspecialchars($valor) ?>">

            <?php elseif ($tipo === 'number'): ?>
                <input type="number" name="<?= $clave ?>" class="form-control"
                       value="<?= htmlspecialchars($valor) ?>" min="0" step="1">

            <?php elseif ($tipo === 'email'): ?>
                <input type="email" name="<?= $clave ?>" class="form-control"
                       value="<?= htmlspecialchars($valor) ?>">

            <?php elseif ($tipo === 'url'): ?>
                <input type="url" name="<?= $clave ?>" class="form-control"
                       value="<?= htmlspecialchars($valor) ?>"
                       placeholder="https://...">

            <?php else: ?>
                <input type="text" name="<?= $clave ?>" class="form-control"
                       value="<?= htmlspecialchars($valor) ?>">
            <?php endif; ?>

            <?php if ($desc): ?>
            <div class="form-text text-muted"><?= htmlspecialchars($desc) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- Preview en vivo para el tab de marca -->
    <?php if ($grupoKey === 'marca'): ?>
    <div class="mt-4 p-3 rounded-3" style="background:#0f172a;max-width:260px">
        <div style="color:#e2e8f0;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;opacity:.5">
            Preview sidebar
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <span id="prev-nombre" style="font-size:1.1rem;font-weight:800;color:#fff">
                <?= htmlspecialchars(ConfigModel::get('marca_nombre_sistema','EduSaaS RD')) ?>
            </span>
        </div>
        <div id="prev-slogan" style="font-size:.72rem;color:#94a3b8">
            <?= htmlspecialchars(ConfigModel::get('marca_slogan','Sistema Educativo RD')) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
<?php endforeach; ?>

</div><!-- tab-content -->

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <span class="text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        Los cambios de color y nombre de marca se aplican al recargar la página.
    </span>
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Guardar configuración
    </button>
</div>

</div><!-- card-body -->
</div><!-- card -->
</form>

<script>
// Preview en vivo del nombre y slogan del sidebar
const inputNombre  = document.querySelector('[name="marca_nombre_sistema"]');
const inputSlogan  = document.querySelector('[name="marca_slogan"]');
const prevNombre   = document.getElementById('prev-nombre');
const prevSlogan   = document.getElementById('prev-slogan');

if (inputNombre && prevNombre) {
    inputNombre.addEventListener('input', () => prevNombre.textContent = inputNombre.value);
}
if (inputSlogan && prevSlogan) {
    inputSlogan.addEventListener('input', () => prevSlogan.textContent = inputSlogan.value);
}

// Actualizar label del switch al cambiar
document.querySelectorAll('.form-check-input[type=checkbox]').forEach(chk => {
    chk.addEventListener('change', function() {
        this.nextElementSibling.textContent = this.checked ? 'Activado' : 'Desactivado';
    });
});
</script>