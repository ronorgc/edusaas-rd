<?php
$appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
$e      = $estudiante;
$edad   = $e['fecha_nacimiento']
    ? (int)date_diff(date_create($e['fecha_nacimiento']), date_create())->y : null;
$colores   = ['#1a56db','#7c3aed','#db2777','#ea580c','#16a34a','#0891b2'];
$color     = $colores[crc32($e['id']) % count($colores)];
$iniciales = mb_strtoupper(mb_substr($e['nombres'],0,1).mb_substr($e['apellidos'],0,1));
$parentMap = ['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor/a',
              'abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío/a','otro'=>'Otro'];
$estadoMap = [
    'activa'     => ['Activo',     'badge-activo'],
    'retirada'   => ['Retirado',   'badge-vencida'],
    'trasladado' => ['Trasladado', 'bg-secondary text-white badge'],
    'graduado'   => ['Graduado',   'bg-success text-white badge'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <nav><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/estudiantes">Estudiantes</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($e['apellidos'].', '.$e['nombres']) ?></li>
    </ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>/editar" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Editar</a>
        <a href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>/eliminar"
           class="btn btn-outline-danger btn-sm"
           onclick="return confirm('¿Desactivar este estudiante?')">
            <i class="bi bi-person-dash me-1"></i>Desactivar</a>
    </div>
</div>

<div class="row g-4">
    <!-- Columna izquierda -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center pt-4 pb-3">
                <?php if ($e['foto']): ?>
                <img src="<?= htmlspecialchars($e['foto']) ?>" class="rounded-circle mb-3 shadow-sm"
                     style="width:100px;height:100px;object-fit:cover;border:3px solid #e2e8f0">
                <?php else: ?>
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center
                            fw-bold text-white mb-3 shadow-sm"
                     style="width:100px;height:100px;background:<?= $color ?>;font-size:2rem;border:3px solid #e2e8f0">
                    <?= $iniciales ?>
                </div>
                <?php endif; ?>
                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($e['nombres'].' '.$e['apellidos']) ?></h5>
                <code class="text-muted small d-block mb-2"><?= htmlspecialchars($e['codigo_estudiante']) ?></code>
                <?php
                $matActiva = null;
                foreach ($e['historial_matriculas'] as $hm) {
                    if ($hm['estado'] === 'activa') { $matActiva = $hm; break; }
                }
                ?>
                <?php if ($matActiva): ?>
                <div class="d-flex justify-content-center gap-2 mt-1">
                    <span class="badge" style="background:#e0e7ff;color:#3730a3"><?= htmlspecialchars($matActiva['grado']) ?></span>
                    <span class="badge bg-light text-dark border">Sec. <?= htmlspecialchars($matActiva['seccion']) ?></span>
                    <span class="badge badge-activo">Activo</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Datos personales -->
        <div class="card mb-3">
            <div class="card-header fw-semibold small"><i class="bi bi-person-vcard me-2 text-primary"></i>Datos personales</div>
            <ul class="list-group list-group-flush small">
                <?php
                $filas = [
                    ['Fecha de nac.',  $e['fecha_nacimiento'] ? date('d/m/Y', strtotime($e['fecha_nacimiento'])) . " ({$edad} años)" : null],
                    ['Sexo',          $e['sexo'] === 'M' ? '♂ Masculino' : '♀ Femenino'],
                    ['Lugar de nac.', $e['lugar_nacimiento']],
                    ['Nacionalidad',  $e['nacionalidad']],
                    ['Cédula',        $e['cedula']],
                    ['NIE (MINERD)',  $e['nie']],
                ];
                foreach ($filas as [$label, $val]):
                    if (!$val) continue;
                ?>
                <li class="list-group-item d-flex justify-content-between py-2 px-3">
                    <span class="text-muted"><?= $label ?></span>
                    <span class="fw-medium text-end"><?= htmlspecialchars($val) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Datos médicos -->
        <?php if ($e['tipo_sangre'] || $e['alergias'] || $e['condiciones_medicas']): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold small"><i class="bi bi-heart-pulse me-2 text-danger"></i>Datos médicos</div>
            <ul class="list-group list-group-flush small">
                <?php if ($e['tipo_sangre']): ?>
                <li class="list-group-item d-flex justify-content-between py-2 px-3">
                    <span class="text-muted">Tipo de sangre</span>
                    <span class="fw-bold text-danger"><?= htmlspecialchars($e['tipo_sangre']) ?></span>
                </li>
                <?php endif; ?>
                <?php if ($e['alergias']): ?>
                <li class="list-group-item py-2 px-3">
                    <span class="text-muted d-block">Alergias</span>
                    <?= htmlspecialchars($e['alergias']) ?>
                </li>
                <?php endif; ?>
                <?php if ($e['condiciones_medicas']): ?>
                <li class="list-group-item py-2 px-3">
                    <span class="text-muted d-block">Condiciones médicas</span>
                    <?= htmlspecialchars($e['condiciones_medicas']) ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Contacto -->
        <?php if ($e['direccion'] || $e['telefono'] || $e['email']): ?>
        <div class="card">
            <div class="card-header fw-semibold small"><i class="bi bi-geo-alt me-2 text-primary"></i>Contacto</div>
            <ul class="list-group list-group-flush small">
                <?php if ($e['direccion']): ?>
                <li class="list-group-item py-2 px-3">
                    <span class="text-muted d-block">Dirección</span>
                    <?= htmlspecialchars($e['direccion']) ?>
                    <?php if ($e['municipio']): ?>, <?= htmlspecialchars($e['municipio']) ?><?php endif; ?>
                    <?php if ($e['provincia']): ?>, <?= htmlspecialchars($e['provincia']) ?><?php endif; ?>
                </li>
                <?php endif; ?>
                <?php if ($e['telefono']): ?>
                <li class="list-group-item d-flex justify-content-between py-2 px-3">
                    <span class="text-muted">Teléfono</span>
                    <a href="tel:<?= htmlspecialchars($e['telefono']) ?>"><?= htmlspecialchars($e['telefono']) ?></a>
                </li>
                <?php endif; ?>
                <?php if ($e['email']): ?>
                <li class="list-group-item d-flex justify-content-between py-2 px-3">
                    <span class="text-muted">Email</span>
                    <a href="mailto:<?= htmlspecialchars($e['email']) ?>"><?= htmlspecialchars($e['email']) ?></a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-8">
        <!-- Tutores -->
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Padres / Tutores</div>
            <?php if (empty($e['tutores'])): ?>
            <div class="card-body text-center text-muted small py-4">
                <i class="bi bi-person-x d-block fs-3 mb-1 opacity-25"></i>Sin tutores registrados
            </div>
            <?php else: ?>
            <div class="row g-0">
                <?php foreach ($e['tutores'] as $t): ?>
                <div class="col-md-6 p-3 border-bottom">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                             style="width:42px;height:42px;background:#1a56db;font-size:.85rem">
                            <?= mb_strtoupper(mb_substr($t['nombres'],0,1).mb_substr($t['apellidos'],0,1)) ?>
                        </div>
                        <div class="flex-grow-1 small">
                            <div class="fw-bold"><?= htmlspecialchars($t['nombres'].' '.$t['apellidos']) ?></div>
                            <div class="text-muted"><?= $parentMap[$t['parentesco']] ?? $t['parentesco'] ?>
                                <?php if ($t['es_responsable']): ?><span class="badge badge-activo ms-1">Responsable</span><?php endif; ?>
                            </div>
                            <?php if ($t['telefono']): ?>
                            <div class="mt-1"><i class="bi bi-telephone me-1 text-muted"></i>
                                <a href="tel:<?= htmlspecialchars($t['telefono']) ?>"><?= htmlspecialchars($t['telefono']) ?></a></div>
                            <?php endif; ?>
                            <?php if ($t['email']): ?>
                            <div><i class="bi bi-envelope me-1 text-muted"></i>
                                <a href="mailto:<?= htmlspecialchars($t['email']) ?>"><?= htmlspecialchars($t['email']) ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Historial de matrículas -->
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Historial de matrículas</div>
            <?php if (empty($e['historial_matriculas'])): ?>
            <div class="card-body text-center text-muted small py-4">
                <i class="bi bi-journal-x d-block fs-3 mb-1 opacity-25"></i>Sin matrículas registradas
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead><tr>
                        <th>Año escolar</th><th>Grado</th><th>Sección</th><th>F. Matrícula</th><th>Estado</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($e['historial_matriculas'] as $hm):
                        $bm = $estadoMap[$hm['estado']] ?? ['—','bg-light text-muted badge'];
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($hm['ano_escolar']) ?></td>
                        <td><?= htmlspecialchars($hm['grado']) ?></td>
                        <td><?= htmlspecialchars($hm['seccion']) ?></td>
                        <td><?= date('d/m/Y', strtotime($hm['fecha_matricula'])) ?></td>
                        <td><span class="badge <?= $bm[1] ?>"><?= $bm[0] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
