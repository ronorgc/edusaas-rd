<?php
// =====================================================
// views/colegio/admin/anos-escolares/index.php
// Lista de años escolares de la institución.
// Variables recibidas desde AdminController::anosEscolares():
//   $anos      → array — todos los años de la institución
//   $anoActivo → array|null — año con activo = 1
// =====================================================
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">📅 Años Escolares</h1>
        <p class="page-subtitle">Gestiona los ciclos académicos de tu institución</p>
    </div>
    <div class="page-header__right">
        <?php if (!VisorMiddleware::estaActivo()): ?>
            <a href="/admin/anos-escolares/crear" class="btn btn-primary">
                + Nuevo Año Escolar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── ALERTA: sin año activo ─────────────────────── -->
<?php if (!$anoActivo): ?>
    <div class="alert alert-warning">
        ⚠️ <strong>No hay un año escolar activo.</strong>
        Sin un año activo no podrás crear secciones ni registrar matrículas.
        <?php if (!VisorMiddleware::estaActivo()): ?>
            Activa uno desde la tabla de abajo o
            <a href="/admin/anos-escolares/crear">crea el primero</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ── TABLA DE AÑOS ESCOLARES ────────────────────── -->
<?php if (empty($anos)): ?>
    <div class="empty-state">
        <span class="empty-state__icon">📅</span>
        <p>Aún no has creado ningún año escolar.</p>
        <?php if (!VisorMiddleware::estaActivo()): ?>
            <a href="/admin/anos-escolares/crear" class="btn btn-primary">
                Crear primer año escolar
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>Año Escolar</th>
                        <th>Período</th>
                        <th class="text-center">Secciones</th>
                        <th class="text-center">Períodos</th>
                        <th class="text-center">Estado</th>
                        <?php if (!VisorMiddleware::estaActivo()): ?>
                            <th class="text-right">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anos as $ano): ?>
                        <tr class="<?= $ano['activo'] ? 'row-highlight' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($ano['nombre']) ?></strong>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($ano['fecha_inicio'])) ?>
                                &nbsp;→&nbsp;
                                <?= date('d/m/Y', strtotime($ano['fecha_fin'])) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-neutral">
                                    <?= $ano['total_secciones'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-neutral">
                                    <?= $ano['total_periodos'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($ano['activo']): ?>
                                    <span class="badge badge-success">✅ Vigente</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <?php if (!VisorMiddleware::estaActivo()): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <!-- Editar -->
                                        <a href="/admin/anos-escolares/<?= $ano['id'] ?>/editar"
                                           class="btn btn-sm btn-secondary"
                                           title="Editar">
                                            ✏️
                                        </a>

                                        <!-- Activar (solo si no es el activo) -->
                                        <?php if (!$ano['activo']): ?>
                                            <form method="POST"
                                                  action="/admin/anos-escolares/<?= $ano['id'] ?>/activar"
                                                  onsubmit="return confirm('¿Marcar «<?= htmlspecialchars($ano['nombre']) ?>» como año escolar vigente?')">
                                                <input type="hidden" name="csrf_token"
                                                       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-primary"
                                                        title="Activar como vigente">
                                                    ⚡ Activar
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Eliminar (solo si no tiene secciones/períodos y no es activo) -->
                                        <?php if (!$ano['activo'] && $ano['total_secciones'] == 0 && $ano['total_periodos'] == 0): ?>
                                            <form method="POST"
                                                  action="/admin/anos-escolares/<?= $ano['id'] ?>/eliminar"
                                                  onsubmit="return confirm('¿Eliminar el año escolar «<?= htmlspecialchars($ano['nombre']) ?>»? Esta acción no se puede deshacer.')">
                                                <input type="hidden" name="csrf_token"
                                                       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        title="Eliminar">
                                                    🗑️
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ── NOTA INFORMATIVA ───────────────────────────── -->
<div class="info-box mt-3">
    <p>
        💡 <strong>¿Cómo funciona?</strong>
        Solo puede haber <strong>un año activo</strong> a la vez.
        Al activar uno nuevo, el anterior queda inactivo automáticamente.
        Las secciones, matrículas y calificaciones quedan vinculadas al año
        en que fueron creadas — no se pierden al cambiar de año.
    </p>
</div>