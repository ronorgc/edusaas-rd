<?php
$appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
$s = $sol;
$parentMap = ['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor/a',
              'abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío/a','otro'=>'Otro'];
$estadoBadge = [
    'pendiente'   => ['Pendiente',    'badge-vencida'],
    'en_revision' => ['En revisión',  'bg-info text-white badge'],
    'aprobada'    => ['Aprobada',     'badge-activo'],
    'rechazada'   => ['Rechazada',    'bg-secondary text-white badge'],
];
[$estLabel, $estBadge] = $estadoBadge[$s['estado']] ?? ['—', 'bg-light'];

$docs = [
    'doc_foto'            => ['Foto del estudiante',         'bi-person-bounding-box', true],
    'doc_acta_nacimiento' => ['Acta de nacimiento',          'bi-file-earmark-text',   true],
    'doc_cedula_tutor'    => ['Cédula del tutor',            'bi-credit-card-2-front', true],
    'doc_cert_medico'     => ['Certificado médico',          'bi-clipboard2-pulse',    true],
    'doc_vacunas'         => ['Tarjeta de vacunas',          'bi-shield-plus',         true],
    'doc_notas_anterior'  => ['Notas colegio anterior',      'bi-journal-text',        false],
    'doc_carta_saldo'     => ['Carta de saldo',              'bi-receipt',             false],
    'doc_sigerd'          => ['SIGERD / Historial MINERD',   'bi-file-earmark-bar-graph', false],
    'doc_extra_1'         => ['Documento adicional 1',       'bi-paperclip',           false],
    'doc_extra_2'         => ['Documento adicional 2',       'bi-paperclip',           false],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <nav><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/admin/preinscripciones">Pre-inscripciones</a></li>
        <li class="breadcrumb-item active">Solicitud #<?= $s['id'] ?></li>
    </ol></nav>
    <span class="badge <?= $estBadge ?> fs-6 px-3"><?= $estLabel ?></span>
</div>

<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-lg-4">

        <!-- Foto -->
        <?php if ($s['doc_foto']): ?>
        <div class="card mb-3 text-center pt-3">
            <div class="card-body">
                <img src="<?= htmlspecialchars($s['doc_foto']) ?>"
                     class="rounded-circle shadow-sm mb-2"
                     style="width:90px;height:90px;object-fit:cover;border:3px solid #e2e8f0">
                <div class="fw-bold"><?= htmlspecialchars($s['nombres'].' '.$s['apellidos']) ?></div>
                <div class="text-muted small">
                    <?= $s['sexo']==='M' ? '♂ Masculino' : '♀ Femenino' ?> ·
                    <?= $s['fecha_nacimiento']
                        ? (int)date_diff(date_create($s['fecha_nacimiento']),date_create())->y . ' años'
                        : '' ?>
                </div>
                <?php if ($s['grado_solicitado']): ?>
                <div class="mt-1">
                    <span class="badge" style="background:#e0e7ff;color:#3730a3">
                        <?= htmlspecialchars($s['grado_solicitado']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Datos médicos -->
        <?php if ($s['tipo_sangre'] || $s['alergias'] || $s['condiciones_medicas']): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold small text-danger">
                <i class="bi bi-heart-pulse me-2"></i>Datos médicos
            </div>
            <ul class="list-group list-group-flush small">
                <?php if ($s['tipo_sangre']): ?>
                <li class="list-group-item d-flex justify-content-between py-2 px-3">
                    <span class="text-muted">Tipo de sangre</span>
                    <span class="fw-bold text-danger"><?= htmlspecialchars($s['tipo_sangre']) ?></span>
                </li>
                <?php endif; ?>
                <?php if ($s['alergias']): ?>
                <li class="list-group-item py-2 px-3">
                    <span class="text-muted d-block">Alergias</span>
                    <?= htmlspecialchars($s['alergias']) ?>
                </li>
                <?php endif; ?>
                <?php if ($s['condiciones_medicas']): ?>
                <li class="list-group-item py-2 px-3">
                    <span class="text-muted d-block">Condiciones</span>
                    <?= htmlspecialchars($s['condiciones_medicas']) ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <?php if (in_array($s['estado'], ['pendiente','en_revision'])): ?>
        <div class="card border-0" style="background:#f8fafc">
            <div class="card-body">
                <p class="fw-bold small text-muted text-uppercase mb-2">Decisión</p>

                <!-- Aprobar -->
                <form method="POST" action="<?= $appUrl ?>/admin/preinscripciones/<?= $s['id'] ?>/aprobar"
                      id="frmAprobar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Notas internas (opcional)</label>
                        <textarea name="notas_admin" class="form-control form-control-sm" rows="2"
                                  id="notasAprobar"
                                  placeholder="Observaciones para el expediente…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold"
                            onclick="return confirm('¿Aprobar esta solicitud y crear el estudiante automáticamente?')">
                        <i class="bi bi-check2-circle me-2"></i>Aprobar y Crear Estudiante
                    </button>
                </form>

                <hr>

                <!-- Rechazar -->
                <form method="POST" action="<?= $appUrl ?>/admin/preinscripciones/<?= $s['id'] ?>/rechazar"
                      id="frmRechazar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Motivo del rechazo</label>
                        <textarea name="notas_admin" class="form-control form-control-sm" rows="2"
                                  placeholder="Explica el motivo para tus registros…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-danger w-100"
                            onclick="return confirm('¿Rechazar esta solicitud?')">
                        <i class="bi bi-x-circle me-2"></i>Rechazar
                    </button>
                </form>
            </div>
        </div>
        <?php elseif ($s['estado'] === 'aprobada' && $s['estudiante_id']): ?>
        <div class="alert alert-success border-0 rounded-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            Aprobada. <a href="<?= $appUrl ?>/estudiantes/<?= $s['estudiante_id'] ?>" class="alert-link">
                Ver ficha del estudiante →
            </a>
        </div>
        <?php elseif ($s['estado'] === 'rechazada'): ?>
        <div class="alert alert-secondary border-0 rounded-3">
            <i class="bi bi-x-circle me-2"></i>Solicitud rechazada.
            <?php if ($s['notas_admin']): ?>
            <div class="small mt-1"><?= htmlspecialchars($s['notas_admin']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-8">

        <!-- Datos del estudiante -->
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-person-fill me-2 text-primary"></i>Datos del estudiante</div>
            <div class="card-body">
                <div class="row g-0 small">
                    <?php
                    $filas = [
                        ['Nombres',           $s['nombres']],
                        ['Apellidos',         $s['apellidos']],
                        ['Fecha nacimiento',  $s['fecha_nacimiento'] ? date('d/m/Y', strtotime($s['fecha_nacimiento'])) : null],
                        ['Sexo',              $s['sexo'] === 'M' ? '♂ Masculino' : '♀ Femenino'],
                        ['Lugar nacimiento',  $s['lugar_nacimiento']],
                        ['Nacionalidad',      $s['nacionalidad']],
                        ['Cédula',            $s['cedula']],
                        ['NIE',               $s['nie']],
                        ['Provincia',         $s['provincia']],
                        ['Municipio',         $s['municipio']],
                        ['Grado solicitado',  $s['grado_solicitado']],
                        ['Colegio anterior',  $s['colegio_anterior']],
                        ['Dirección',         $s['direccion']],
                    ];
                    foreach ($filas as [$k, $v]):
                        if (!$v) continue;
                    ?>
                    <div class="col-md-6 d-flex justify-content-between border-bottom py-2 px-1">
                        <span class="text-muted"><?= $k ?></span>
                        <span class="fw-semibold text-end ms-2"><?= htmlspecialchars($v) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Datos del tutor -->
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-people me-2 text-primary"></i>
                <?= $parentMap[$s['tutor_parentesco']] ?? $s['tutor_parentesco'] ?> Responsable
            </div>
            <div class="card-body">
                <div class="row g-0 small">
                    <?php
                    $filasTutor = [
                        ['Nombre completo', $s['tutor_nombres'].' '.$s['tutor_apellidos']],
                        ['Cédula',          $s['tutor_cedula']],
                        ['Celular',         $s['tutor_telefono']],
                        ['Email',           $s['tutor_email']],
                        ['Ocupación',       $s['tutor_ocupacion']],
                        ['Lugar trabajo',   $s['tutor_lugar_trabajo']],
                    ];
                    foreach ($filasTutor as [$k, $v]):
                        if (!$v) continue;
                    ?>
                    <div class="col-md-6 d-flex justify-content-between border-bottom py-2 px-1">
                        <span class="text-muted"><?= $k ?></span>
                        <span class="fw-semibold text-end ms-2"><?= htmlspecialchars($v) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Documentos -->
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-folder2-open me-2 text-warning"></i>Documentos adjuntos</div>
            <div class="card-body">
                <div class="row g-3">
                <?php foreach ($docs as $col => [$label, $icon, $obligatorio]): ?>
                <div class="col-md-6">
                    <?php if ($s[$col]): ?>
                    <?php
                    $ext  = strtolower(pathinfo($s[$col], PATHINFO_EXTENSION));
                    $isPdf = $ext === 'pdf';
                    ?>
                    <div class="border rounded-3 overflow-hidden" style="background:#f8fafc">
                        <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                            <i class="bi <?= $icon ?> text-primary"></i>
                            <span class="small fw-semibold flex-grow-1"><?= $label ?></span>
                            <?php if ($obligatorio): ?>
                            <span class="badge bg-success" style="font-size:.65rem">✓ Subido</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isPdf): ?>
                        <div class="text-center p-3">
                            <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size:2.5rem"></i>
                            <div class="small text-muted mt-1">Archivo PDF</div>
                        </div>
                        <?php else: ?>
                        <img src="<?= htmlspecialchars($s[$col]) ?>"
                             class="w-100"
                             style="max-height:140px;object-fit:cover;cursor:zoom-in"
                             onclick="openModal('<?= htmlspecialchars($s[$col]) ?>', '<?= htmlspecialchars($label) ?>')">
                        <?php endif; ?>
                        <div class="p-2 text-center">
                            <a href="<?= htmlspecialchars($s[$col]) ?>" target="_blank"
                               class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-<?= $isPdf ? 'file-earmark-pdf' : 'zoom-in' ?> me-1"></i>
                                <?= $isPdf ? 'Ver PDF' : 'Ver imagen' ?>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="border rounded-3 p-3 text-center"
                         style="background:#f1f5f9;border-style:dashed!important">
                        <i class="bi <?= $icon ?> text-muted opacity-50 d-block" style="font-size:1.5rem"></i>
                        <span class="small text-muted"><?= $label ?></span>
                        <?php if ($obligatorio): ?>
                        <br><span class="badge bg-danger-subtle text-danger" style="font-size:.65rem">
                            <i class="bi bi-exclamation-circle me-1"></i>No subido
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal zoom imagen -->
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header py-2">
                <span class="fw-bold" id="imgModalLabel"></span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark">
                <img id="imgModalSrc" src="" class="img-fluid" style="max-height:80vh">
            </div>
        </div>
    </div>
</div>

<script>
function openModal(src, label) {
    document.getElementById('imgModalSrc').src = src;
    document.getElementById('imgModalLabel').textContent = label;
    new bootstrap.Modal(document.getElementById('imgModal')).show();
}
// Sincronizar notas al aprobar/rechazar
document.getElementById('frmAprobar')?.addEventListener('submit', () => {
    document.querySelector('#frmRechazar [name=notas_admin]').value = '';
});
</script>
