<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$i = $inst; // alias corto
$v = fn(string $k, string $d='') => htmlspecialchars($i[$k] ?? $d);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <nav><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/superadmin/instituciones">Instituciones</a></li>
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>"><?= $v('nombre') ?></a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>/usuarios"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people me-1"></i>Usuarios
        </a>
        <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-9">

<div class="card">
    <div class="card-header fw-semibold">
        <i class="bi bi-pencil-square me-2 text-primary"></i>
        Editar datos de <strong><?= $v('nombre') ?></strong>
    </div>
    <div class="card-body">
    <form action="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>/editar" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Nombre del colegio <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" required value="<?= $v('nombre') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                <select name="tipo" class="form-select" required>
                    <option value="privado" <?= $i['tipo']==='privado'?'selected':'' ?>>Privado</option>
                    <option value="publico" <?= $i['tipo']==='publico'?'selected':'' ?>>Público</option>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label fw-semibold">Identificador URL <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" name="subdomain" class="form-control" required
                           pattern="[a-z0-9\-]+" value="<?= $v('subdomain') ?>">
                    <div class="form-text text-muted mt-1">/preinscripcion/<strong id="slug_preview"><?= htmlspecialchars($inst['subdomain'] ?? '') ?></strong></div>
                </div>
                <div class="form-text text-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    ⚠️ Cambiar este valor rompe la URL del formulario de pre-inscripción ya compartida con los padres.
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Código MINERD</label>
                <input type="text" name="codigo_minerd" class="form-control" value="<?= $v('codigo_minerd') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">RNC</label>
                <input type="text" name="rnc" class="form-control" value="<?= $v('rnc') ?>" placeholder="Opcional">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required value="<?= $v('email') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" value="<?= $v('telefono') ?>">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Dirección</label>
                <textarea name="direccion" class="form-control" rows="2"><?= $v('direccion') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Municipio</label>
                <input type="text" name="municipio" class="form-control" value="<?= $v('municipio') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Provincia</label>
                <input type="text" name="provincia" class="form-control" value="<?= $v('provincia') ?>">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="<?= $appUrl ?>/superadmin/instituciones/<?= $i['id'] ?>"
               class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Guardar cambios
            </button>
        </div>
    </form>
    </div>
</div>

</div>
</div>

<script>
document.querySelector('[name="subdomain"]').addEventListener('input', function() {
    const el = document.getElementById('slug_preview');
    if (el) el.textContent = this.value || '...';
});
</script>