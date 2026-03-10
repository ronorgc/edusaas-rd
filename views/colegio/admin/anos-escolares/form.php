<?php
// =====================================================
// views/colegio/admin/anos-escolares/form.php
// Formulario para crear o editar un año escolar.
// Variables recibidas desde AdminController:
//   $ano         → array|null — null = modo crear
//   $modoEdicion → bool
//   $csrf_token  → string
// =====================================================

$titulo  = $modoEdicion ? 'Editar Año Escolar' : 'Nuevo Año Escolar';
$accion  = $modoEdicion
    ? "/admin/anos-escolares/{$ano['id']}/actualizar"
    : '/admin/anos-escolares/guardar';

// Prellenar valores del formulario
$nombre     = htmlspecialchars($ano['nombre']      ?? '');
$fechaInicio = $ano['fecha_inicio'] ?? '';
$fechaFin    = $ano['fecha_fin']    ?? '';
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/anos-escolares" class="back-link">← Años Escolares</a>
        <h1 class="page-title"><?= $titulo ?></h1>
    </div>
</div>

<div class="card card--form">
    <div class="card__body">
        <form method="POST" action="<?= $accion ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Nombre del año escolar -->
            <div class="form-group">
                <label for="nombre" class="form-label">
                    Nombre del año escolar <span class="required">*</span>
                </label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       class="form-control"
                       value="<?= $nombre ?>"
                       placeholder="Ej: 2025-2026"
                       required
                       maxlength="50"
                       autofocus>
                <small class="form-hint">
                    Usa el formato estándar MINERD: <strong>AAAA-AAAA</strong>
                    (ej: 2025-2026).
                </small>
            </div>

            <!-- Fechas de inicio y fin -->
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="fecha_inicio" class="form-label">
                        Fecha de inicio <span class="required">*</span>
                    </label>
                    <input type="date"
                           id="fecha_inicio"
                           name="fecha_inicio"
                           class="form-control"
                           value="<?= $fechaInicio ?>"
                           required>
                </div>

                <div class="form-group form-group--half">
                    <label for="fecha_fin" class="form-label">
                        Fecha de cierre <span class="required">*</span>
                    </label>
                    <input type="date"
                           id="fecha_fin"
                           name="fecha_fin"
                           class="form-control"
                           value="<?= $fechaFin ?>"
                           required>
                </div>
            </div>

            <!-- Aviso en modo edición -->
            <?php if ($modoEdicion): ?>
                <div class="alert alert-info mt-2">
                    ℹ️ Editar las fechas solo actualiza los registros del año.
                    Las secciones, períodos y matrículas vinculadas no se modifican.
                </div>
            <?php endif; ?>

            <!-- Botones -->
            <div class="form-actions">
                <a href="/admin/anos-escolares" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <?= $modoEdicion ? '💾 Guardar cambios' : '+ Crear año escolar' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Validación de fechas en cliente -->
<script>
document.getElementById('fecha_fin').addEventListener('change', function () {
    const inicio = document.getElementById('fecha_inicio').value;
    if (inicio && this.value && this.value <= inicio) {
        alert('La fecha de cierre debe ser posterior a la fecha de inicio.');
        this.value = '';
    }
});
</script>