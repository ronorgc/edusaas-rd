<?php
// =====================================================
// views/colegio/admin/secciones/form.php
// Formulario crear/editar sección.
// Variables recibidas desde AdminController:
//   $seccion     → array|null — null = modo crear
//   $grados      → array — grados activos de la institución
//   $anoActivo   → array|null — año escolar vigente
//   $modoEdicion → bool
//   $csrf_token  → string
// =====================================================

$titulo = $modoEdicion ? 'Editar Sección' : 'Nueva Sección';
$accion = $modoEdicion
    ? "/admin/secciones/{$seccion['id']}/actualizar"
    : '/admin/secciones/guardar';

// Valores prellenados
$gradoIdSeleccionado = (int)($_GET['grado'] ?? $seccion['grado_id'] ?? 0);
$nombre     = htmlspecialchars($seccion['nombre']    ?? '');
$capacidad  = (int)($seccion['capacidad']            ?? 40);
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/secciones" class="back-link">← Secciones</a>
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

            <!-- Grado (deshabilitado en edición para proteger matrículas) -->
            <div class="form-group">
                <label for="grado_id" class="form-label">
                    Grado <span class="required">*</span>
                </label>
                <?php if ($modoEdicion): ?>
                    <!-- En edición el grado no se puede cambiar -->
                    <input type="hidden" name="grado_id" value="<?= (int)$seccion['grado_id'] ?>">
                    <input type="text"
                           class="form-control"
                           value="<?= htmlspecialchars($seccion['grado_nombre'] ?? '') ?>"
                           disabled>
                    <small class="form-hint text-muted">
                        El grado no puede modificarse si la sección ya existe.
                    </small>
                <?php else: ?>
                    <select id="grado_id" name="grado_id" class="form-control" required>
                        <option value="">— Selecciona un grado —</option>
                        <?php
                        // Agrupar grados por nivel para el select
                        $nivelesLabel = [
                            'inicial'    => 'Nivel Inicial',
                            'primario'   => 'Nivel Primario',
                            'secundario' => 'Nivel Secundario',
                        ];
                        $gradosPorNivel = [];
                        foreach ($grados as $g) {
                            $gradosPorNivel[$g['nivel']][] = $g;
                        }
                        foreach ($gradosPorNivel as $nivel => $lista):
                        ?>
                            <optgroup label="<?= $nivelesLabel[$nivel] ?? ucfirst($nivel) ?>">
                                <?php foreach ($lista as $g): ?>
                                    <option value="<?= $g['id'] ?>"
                                        <?= $gradoIdSeleccionado === (int)$g['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- Nombre de la sección -->
            <div class="form-group">
                <label for="nombre" class="form-label">
                    Nombre de la sección <span class="required">*</span>
                </label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       class="form-control"
                       value="<?= $nombre ?>"
                       placeholder="Ej: A, B, C o «Matutino», «Vespertino»"
                       required
                       maxlength="10"
                       <?= !$modoEdicion ? 'autofocus' : '' ?>>
                <small class="form-hint">
                    Generalmente una letra: A, B, C… o una jornada: Matutino, Vespertino.
                </small>
            </div>

            <!-- Capacidad -->
            <div class="form-group">
                <label for="capacidad" class="form-label">
                    Capacidad máxima de estudiantes
                </label>
                <input type="number"
                       id="capacidad"
                       name="capacidad"
                       class="form-control form-control--short"
                       value="<?= $capacidad ?>"
                       min="1"
                       max="80">
                <small class="form-hint">
                    Número máximo de estudiantes que puede tener esta sección.
                    El sistema avisará cuando se alcance el límite.
                </small>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="/admin/secciones" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $modoEdicion ? '💾 Guardar cambios' : '+ Crear sección' ?>
                </button>
            </div>
        </form>
    </div>
</div>