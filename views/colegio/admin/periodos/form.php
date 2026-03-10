<?php
// =====================================================
// views/colegio/admin/periodos/form.php
// Formulario crear/editar período de evaluación.
// Variables recibidas desde AdminController:
//   $periodo     → array|null — null = modo crear
//   $anoActivo   → array|null
//   $modoEdicion → bool
//   $csrf_token  → string
// =====================================================

$titulo = $modoEdicion ? 'Editar Período' : 'Nuevo Período';
$accion = $modoEdicion
    ? "/admin/periodos/{$periodo['id']}/actualizar"
    : '/admin/periodos/guardar';

// Valores prellenados
$nombre = htmlspecialchars($periodo['nombre'] ?? '');
$orden  = (int)($periodo['orden']             ?? 1);

// Sugerencias de nombres estándar MINERD
$sugerencias = [
    '1er Período',
    '2do Período',
    '3er Período',
    'Recuperación',
    'Período Final',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/periodos" class="back-link">← Períodos</a>
        <h1 class="page-title"><?= $titulo ?></h1>
        <?php if ($anoActivo): ?>
            <p class="page-subtitle">
                Año escolar: <strong><?= htmlspecialchars($anoActivo['nombre']) ?></strong>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="card card--form">
    <div class="card__body">
        <form method="POST" action="<?= $accion ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Nombre del período -->
            <div class="form-group">
                <label for="nombre" class="form-label">
                    Nombre del período <span class="required">*</span>
                </label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       class="form-control"
                       value="<?= $nombre ?>"
                       placeholder="Ej: 1er Período, Recuperación…"
                       required
                       maxlength="80"
                       list="sugerencias-periodos"
                       autofocus>

                <!-- Datalist con sugerencias MINERD -->
                <datalist id="sugerencias-periodos">
                    <?php foreach ($sugerencias as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>">
                    <?php endforeach; ?>
                </datalist>

                <small class="form-hint">
                    Puedes seleccionar un nombre estándar MINERD o escribir el tuyo.
                </small>
            </div>

            <!-- Orden de presentación -->
            <div class="form-group">
                <label for="orden" class="form-label">
                    Orden de presentación
                </label>
                <input type="number"
                       id="orden"
                       name="orden"
                       class="form-control form-control--short"
                       value="<?= $orden ?>"
                       min="1"
                       max="20">
                <small class="form-hint">
                    Define el orden en listas y reportes de calificaciones.
                    El orden estándar MINERD: 1→5.
                </small>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="/admin/periodos" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $modoEdicion ? '💾 Guardar cambios' : '+ Crear período' ?>
                </button>
            </div>
        </form>
    </div>
</div>