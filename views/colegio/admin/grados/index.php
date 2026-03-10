<?php
// =====================================================
// views/colegio/admin/grados/index.php
// Listado de grados agrupado por nivel MINERD.
// Solo lectura — los grados provienen del seed.
// Variables recibidas desde AdminController::grados():
//   $porNivel  → array ['inicial'=>[], 'primario'=>[], 'secundario'=>[]]
//   $anoActivo → array|null
// =====================================================

// Etiquetas de nivel para mostrar en la vista
$etiquetasNivel = [
    'inicial'    => ['label' => '🌱 Nivel Inicial',    'color' => 'green'],
    'primario'   => ['label' => '📚 Nivel Primario',   'color' => 'blue'],
    'secundario' => ['label' => '🎓 Nivel Secundario', 'color' => 'purple'],
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">🏫 Grados</h1>
        <p class="page-subtitle">Estructura académica por niveles — MINERD</p>
    </div>
    <div class="page-header__right">
        <!-- Los grados no tienen CRUD: vienen del seed MINERD -->
        <a href="/admin/secciones/crear" class="btn btn-primary">
            + Nueva Sección
        </a>
    </div>
</div>

<!-- ── ALERTA: sin año activo ─────────────────────── -->
<?php if (!$anoActivo): ?>
    <div class="alert alert-warning">
        ⚠️ No hay un año escolar activo.
        Los conteos de secciones y matriculados no estarán disponibles.
        <a href="/admin/anos-escolares">Activar año escolar →</a>
    </div>
<?php endif; ?>

<!-- ── GRADOS POR NIVEL ───────────────────────────── -->
<?php foreach ($etiquetasNivel as $nivelKey => $nivelInfo): ?>
    <?php if (!empty($porNivel[$nivelKey])): ?>
        <div class="card mb-4">
            <div class="card__header">
                <h2 class="card__title">
                    <?= $nivelInfo['label'] ?>
                </h2>
                <?php if ($anoActivo): ?>
                    <span class="card__subtitle">
                        Año vigente: <?= htmlspecialchars($anoActivo['nombre']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card__body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Grado</th>
                            <th class="text-center">Secciones activas</th>
                            <th class="text-center">Matriculados (año vigente)</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porNivel[$nivelKey] as $grado): ?>
                            <tr>
                                <td class="text-muted">
                                    <?= (int)$grado['orden'] ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($grado['nombre']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($grado['total_secciones'] > 0): ?>
                                        <span class="badge badge-success">
                                            <?= $grado['total_secciones'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-neutral">
                                        <?= $grado['total_matriculados'] ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <!-- Ver secciones de este grado -->
                                    <a href="/admin/secciones?grado=<?= $grado['id'] ?>"
                                       class="btn btn-sm btn-secondary">
                                        Ver secciones
                                    </a>
                                    <?php if (!VisorMiddleware::estaActivo()): ?>
                                        <a href="/admin/secciones/crear?grado=<?= $grado['id'] ?>"
                                           class="btn btn-sm btn-primary">
                                            + Sección
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- ── NOTA INFORMATIVA ───────────────────────────── -->
<div class="info-box">
    <p>
        💡 Los grados son definidos por el <strong>MINERD</strong> y vienen
        precargados en el sistema. No es posible agregar, eliminar ni modificar
        grados desde este panel. Para agregar un grupo a un grado,
        crea una <a href="/admin/secciones/crear">nueva sección</a>.
    </p>
</div>