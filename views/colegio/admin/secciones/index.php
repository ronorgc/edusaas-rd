<?php
// =====================================================
// views/colegio/admin/secciones/index.php
// Lista de secciones del año escolar activo.
// Variables recibidas desde AdminController::secciones():
//   $secciones → array — secciones con grado_nombre, nivel, total_estudiantes
//   $anoActivo → array|null
// =====================================================
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">🏷️ Secciones</h1>
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
            <a href="/admin/secciones/crear" class="btn btn-primary">
                + Nueva Sección
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── ALERTA: sin año activo ─────────────────────── -->
<?php if (!$anoActivo): ?>
    <div class="alert alert-warning">
        ⚠️ <strong>No hay un año escolar activo.</strong>
        Debes activar un año antes de gestionar secciones.
        <a href="/admin/anos-escolares">Ir a Años Escolares →</a>
    </div>

<?php elseif (empty($secciones)): ?>
    <!-- Sin secciones creadas aún -->
    <div class="empty-state">
        <span class="empty-state__icon">🏷️</span>
        <p>No hay secciones en el año escolar <strong><?= htmlspecialchars($anoActivo['nombre']) ?></strong>.</p>
        <?php if (!VisorMiddleware::estaActivo()): ?>
            <a href="/admin/secciones/crear" class="btn btn-primary">
                Crear primera sección
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ── TABLA DE SECCIONES ─────────────────────── -->
    <div class="card">
        <div class="card__body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th>Grado</th>
                        <th>Nivel</th>
                        <th class="text-center">Estudiantes</th>
                        <th class="text-center">Capacidad</th>
                        <?php if (!VisorMiddleware::estaActivo()): ?>
                            <th class="text-right">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($secciones as $sec): ?>
                        <?php
                        // Calcular ocupación para mostrar alerta visual
                        $pct = $sec['capacidad'] > 0
                            ? round($sec['total_estudiantes'] / $sec['capacidad'] * 100)
                            : 0;
                        $ocupacionClass = $pct >= 100 ? 'badge-danger'
                            : ($pct >= 80 ? 'badge-warning' : 'badge-success');
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($sec['nombre']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($sec['grado_nombre']) ?>
                            </td>
                            <td>
                                <?php
                                $nivelLabel = [
                                    'inicial'    => 'Inicial',
                                    'primario'   => 'Primario',
                                    'secundario' => 'Secundario',
                                ];
                                echo $nivelLabel[$sec['nivel']] ?? ucfirst($sec['nivel']);
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $ocupacionClass ?>">
                                    <?= $sec['total_estudiantes'] ?>
                                </span>
                            </td>
                            <td class="text-center text-muted">
                                <?= (int)$sec['capacidad'] ?>
                            </td>

                            <?php if (!VisorMiddleware::estaActivo()): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/admin/secciones/<?= $sec['id'] ?>/editar"
                                           class="btn btn-sm btn-secondary"
                                           title="Editar sección">
                                            ✏️
                                        </a>

                                        <!-- Eliminar solo si no tiene matriculados -->
                                        <?php if ($sec['total_estudiantes'] == 0): ?>
                                            <form method="POST"
                                                  action="/admin/secciones/<?= $sec['id'] ?>/eliminar"
                                                  onsubmit="return confirm('¿Desactivar la sección «<?= htmlspecialchars($sec['nombre']) ?>»?')">
                                                <input type="hidden" name="csrf_token"
                                                       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        title="Eliminar sección">
                                                    🗑️
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- No se puede eliminar: tiene estudiantes -->
                                            <button class="btn btn-sm btn-disabled"
                                                    title="No se puede eliminar: tiene estudiantes matriculados"
                                                    disabled>
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card__footer text-muted">
            Total: <strong><?= count($secciones) ?></strong>
            sección<?= count($secciones) !== 1 ? 'es' : '' ?> en
            <?= htmlspecialchars($anoActivo['nombre']) ?>
        </div>
    </div>
<?php endif; ?>