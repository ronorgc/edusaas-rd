<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$estadoConfig = [
    'pendiente'   => ['Pendiente',    'badge-vencida',                          'bi-hourglass-split'],
    'en_revision' => ['En revisión',  'bg-info text-white badge',               'bi-eye-fill'],
    'aprobada'    => ['Aprobada',     'badge-activo',                           'bi-check-circle-fill'],
    'rechazada'   => ['Rechazada',    'bg-secondary text-white badge',          'bi-x-circle-fill'],
];
?>

<!-- Contadores -->
<div class="row g-3 mb-4">
    <?php
    $tabs = ['pendiente' => ['Pendientes','#f59e0b'], 'en_revision' => ['En revisión','#0891b2'],
             'aprobada' => ['Aprobadas','#10b981'], 'rechazada' => ['Rechazadas','#6b7280'], 'todas' => ['Todas','#1a56db']];
    foreach ($tabs as $k => [$label, $color]):
        $cnt = $k === 'todas'
            ? array_sum($contadores)
            : ($contadores[$k] ?? 0);
        $active = $filtroEstado === $k;
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="?estado=<?= $k ?>" class="text-decoration-none">
            <div class="stat-card" style="<?= $active ? "border:2px solid {$color};box-shadow:0 0 0 3px {$color}22" : '' ?>">
                <div><div class="stat-value" style="color:<?= $color ?>"><?= $cnt ?></div>
                     <div class="stat-label"><?= $label ?></div></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-inbox-fill me-2 text-primary"></i>
              Pre-inscripciones
              <span class="badge bg-primary ms-1"><?= count($solicitudes) ?></span>
        </span>
        <?php
        $urlForm = $appUrl . '/preinscripcion/' .
            (Database::getInstance()->query("SELECT subdomain FROM instituciones WHERE id = " .
                ($_SESSION['institucion_id'] ?? 0))->fetchColumn() ?? '');
        ?>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small d-none d-md-inline">Enlace público:</span>
            <code class="small text-primary d-none d-md-inline"><?= htmlspecialchars($urlForm) ?></code>
            <button class="btn btn-sm btn-outline-primary"
                    onclick="navigator.clipboard.writeText('<?= htmlspecialchars($urlForm) ?>');this.innerHTML='<i class=\'bi bi-check2\'></i> Copiado'">
                <i class="bi bi-copy"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted opacity-25 d-block mb-2"></i>
            <p class="text-muted">No hay solicitudes <?= $filtroEstado !== 'todas' ? "con estado «{$filtroEstado}»" : '' ?>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Grado</th>
                        <th>Tutor</th>
                        <th>Contacto</th>
                        <th>Docs</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s):
                    $cfg = $estadoConfig[$s['estado']] ?? ['—', 'bg-light', 'bi-question'];
                    // Contar documentos subidos
                    $docCols = ['doc_foto','doc_acta_nacimiento','doc_cedula_tutor',
                                'doc_cert_medico','doc_vacunas','doc_notas_anterior',
                                'doc_carta_saldo','doc_sigerd','doc_extra_1','doc_extra_2'];
                    $docCount = count(array_filter(array_map(fn($c) => $s[$c], $docCols)));
                ?>
                <tr onclick="window.location='<?= $appUrl ?>/admin/preinscripciones/<?= $s['id'] ?>'"
                    style="cursor:pointer">
                    <td><span class="text-muted small"><?= $s['id'] ?></span></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($s['apellidos'].', '.$s['nombres']) ?></div>
                        <div class="text-muted small">
                            <?= $s['fecha_nacimiento']
                                ? (int)date_diff(date_create($s['fecha_nacimiento']), date_create())->y . ' años'
                                : '' ?>
                            · <?= $s['sexo'] === 'M' ? '♂' : '♀' ?>
                        </div>
                    </td>
                    <td><span class="small"><?= htmlspecialchars($s['grado_solicitado'] ?? '—') ?></span></td>
                    <td>
                        <div class="small"><?= htmlspecialchars($s['tutor_nombres'].' '.$s['tutor_apellidos']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['tutor_parentesco']) ?></div>
                    </td>
                    <td>
                        <a href="tel:<?= htmlspecialchars($s['tutor_telefono']) ?>"
                           class="small d-block text-decoration-none"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($s['tutor_telefono']) ?>
                        </a>
                        <a href="mailto:<?= htmlspecialchars($s['tutor_email']) ?>"
                           class="small text-decoration-none text-muted"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($s['tutor_email']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge <?= $docCount >= 5 ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <i class="bi bi-paperclip me-1"></i><?= $docCount ?>/10
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $cfg[1] ?>">
                            <i class="bi <?= $cfg[2] ?> me-1"></i><?= $cfg[0] ?>
                        </span>
                    </td>
                    <td class="text-muted small">
                        <?= date('d/m/Y', strtotime($s['created_at'])) ?>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="<?= $appUrl ?>/admin/preinscripciones/<?= $s['id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
