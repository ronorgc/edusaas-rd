<?php
// =====================================================
// views/colegio/admin/periodos/index.php
// Lista de períodos de evaluación del año activo.
// Variables recibidas desde AdminController::periodos():
//   $periodos  → array — períodos ordenados por orden ASC
//   $anoActivo → array|null
// =====================================================
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">📋 Períodos de Evaluación</h1>
        <p class="page-subtitle">
            <?php if ($anoActivo): ?>
                Año escolar vigente: <strong><?= htmlspecialchars($anoActivo['nombre']) ?></strong>
            <?php else: ?>
                Sin año escolar activo
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header__right">
        <?php if ($anoActivo && !VisorMiddleware::estaActivo()): ?>
            <a href="/admin/periodos/crear" class="btn btn-primary">
                + Nuevo Período
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── ALERTA: sin año activo ─────────────────────── -->
<?php if (!$anoActivo): ?>
    <div class="alert alert-warning">
        ⚠️ <strong>No hay un año escolar activo.</strong>
        Debes activar un año antes de gestionar períodos.
        <a href="/admin/anos-escolares">Ir a Años Escolares →</a>
    </div>

<?php elseif (empty($periodos)): ?>
    <!-- Sin períodos configurados -->
    <div class="empty-state">
        <span class="empty-state__icon">📋</span>
        <p>No hay períodos de evaluación para <strong><?= htmlspecialchars($anoActivo['nombre']) ?></strong>.</p>
        <?php if (!VisorMiddleware::estaActivo()): ?>
            <p class="text-muted">
                Normalmente se crean 5 períodos según el MINERD:
                1er, 2do, 3er Período, Recuperación y Final.
            </p>
            <a href="/admin/periodos/crear" class="btn btn-primary">
                Crear primer período
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ── TABLA DE PERÍODOS ──────────────────────── -->
    <div class="card">
        <div class="card__body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Período</th>
                        <th class="text-center">Orden</th>
                        <?php if (!VisorMiddleware::estaActivo()): ?>
                            <th class="text-right">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periodos as $periodo): ?>
                        <tr>
                            <td class="text-center text-muted">
                                <?= (int)$periodo['orden'] ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($periodo['nombre']) ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-neutral">
                                    <?= (int)$periodo['orden'] ?>
                                </span>
                            </td>

                            <?php if (!VisorMiddleware::estaActivo()): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/admin/periodos/<?= $periodo['id'] ?>/editar"
                                           class="btn btn-sm btn-secondary"
                                           title="Editar período">
                                            ✏️
                                        </a>
                                        <form method="POST"
                                              action="/admin/periodos/<?= $periodo['id'] ?>/eliminar"
                                              onsubmit="return confirm('¿Eliminar el período «<?= htmlspecialchars($periodo['nombre']) ?>»?\n\nSolo es posible si no tiene calificaciones registradas.')">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    title="Eliminar período">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card__footer text-muted">
            Total: <strong><?= count($periodos) ?></strong>
            período<?= count($periodos) !== 1 ? 's' : '' ?> configurado<?= count($periodos) !== 1 ? 's' : '' ?>
        </div>
    </div>
<?php endif; ?>

<!-- ── NOTA INFORMATIVA ───────────────────────────── -->
<div class="info-box mt-3">
    <p>
        💡 <strong>Períodos estándar MINERD para RD:</strong>
        1er Período (orden 1), 2do Período (orden 2), 3er Período (orden 3),
        Recuperación (orden 4), Período Final (orden 5).
        Los períodos son la referencia para registrar calificaciones.
    </p>
</div>