<?php $appUrl = (require __DIR__ . '/../../../config/app.php')['url']; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe"><i class="bi bi-people-fill text-primary fs-4"></i></div>
            <div><div class="stat-value"><?= $stats['total'] ?? 0 ?></div><div class="stat-label">Total estudiantes</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7"><i class="bi bi-check2-circle fs-4" style="color:#16a34a"></i></div>
            <div><div class="stat-value"><?= $stats['matriculados'] ?? 0 ?></div><div class="stat-label">Matriculados</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe"><i class="bi bi-gender-male fs-4" style="color:#7c3aed"></i></div>
            <div><div class="stat-value"><?= $stats['masculino'] ?? 0 ?></div><div class="stat-label">Masculino</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce7f3"><i class="bi bi-gender-female fs-4" style="color:#db2777"></i></div>
            <div><div class="stat-value"><?= $stats['femenino'] ?? 0 ?></div><div class="stat-label">Femenino</div></div>
        </div>
    </div>
</div>

<!-- Toolbar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= $appUrl ?>/estudiantes" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Nombre, apellido o código…"
                           value="<?= htmlspecialchars($filtros['busqueda']) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="grado_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos los grados</option>
                    <?php foreach ($grados as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $filtros['grado_id'] == $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="seccion_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($secciones as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filtros['seccion_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['grado_nombre'] . ' — Sec. ' . $s['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i></button>
                <?php if ($filtros['busqueda'] || $filtros['grado_id'] || $filtros['seccion_id']): ?>
                <a href="<?= $appUrl ?>/estudiantes" class="btn btn-outline-secondary">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-people-fill me-2 text-primary"></i>Estudiantes
            <span class="badge bg-primary ms-1"><?= count($estudiantes) ?></span>
        </span>
        <a href="<?= $appUrl ?>/estudiantes/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Estudiante
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($estudiantes)): ?>
        <div class="text-center py-5">
            <i class="bi bi-person-x fs-1 text-muted opacity-50 d-block mb-2"></i>
            <p class="text-muted">No se encontraron estudiantes.</p>
            <a href="<?= $appUrl ?>/estudiantes/crear" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Registrar primer estudiante
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:48px"></th>
                        <th>Estudiante</th>
                        <th>Código</th>
                        <th>Grado / Sección</th>
                        <th>Sexo</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($estudiantes as $e):
                    $iniciales = mb_strtoupper(mb_substr($e['nombres'],0,1).mb_substr($e['apellidos'],0,1));
                    $colores   = ['#1a56db','#7c3aed','#db2777','#ea580c','#16a34a','#0891b2'];
                    $color     = $colores[crc32($e['id']) % count($colores)];
                    $estadoMap = [
                        'activa'     => ['Activo',     'badge-activo'],
                        'retirada'   => ['Retirado',   'badge-vencida'],
                        'trasladado' => ['Trasladado', 'bg-secondary text-white badge'],
                        'graduado'   => ['Graduado',   'bg-success text-white badge'],
                    ];
                    $est = $estadoMap[$e['matricula_estado'] ?? ''] ?? ['Sin matrícula', 'bg-light text-muted badge'];
                ?>
                <tr onclick="window.location='<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>'" style="cursor:pointer">
                    <td>
                        <?php if ($e['foto']): ?>
                        <img src="<?= htmlspecialchars($e['foto']) ?>" class="rounded-circle"
                             width="38" height="38" style="object-fit:cover">
                        <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                             style="width:38px;height:38px;background:<?= $color ?>;font-size:.8rem">
                            <?= $iniciales ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombres']) ?></div>
                        <?php if ($e['fecha_nacimiento']): ?>
                        <div class="text-muted small">
                            <?= date('d/m/Y', strtotime($e['fecha_nacimiento'])) ?> ·
                            <?= (int)date_diff(date_create($e['fecha_nacimiento']), date_create())->y ?> años
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><code class="small"><?= htmlspecialchars($e['codigo_estudiante']) ?></code></td>
                    <td>
                        <?php if ($e['grado_nombre']): ?>
                        <?= htmlspecialchars($e['grado_nombre']) ?>
                        <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($e['seccion_nombre']) ?></span>
                        <?php else: ?>
                        <span class="text-muted small">Sin matrícula</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $e['sexo'] === 'M'
                            ? '<span style="color:#7c3aed"><i class="bi bi-gender-male"></i> Masc.</span>'
                            : '<span style="color:#db2777"><i class="bi bi-gender-female"></i> Fem.</span>' ?>
                    </td>
                    <td><span class="badge <?= $est[1] ?>"><?= $est[0] ?></span></td>
                    <td onclick="event.stopPropagation()">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>">
                                    <i class="bi bi-eye me-2"></i>Ver ficha</a></li>
                                <li><a class="dropdown-item" href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>/editar">
                                    <i class="bi bi-pencil me-2"></i>Editar</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger"
                                       href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>/eliminar"
                                       onclick="return confirm('¿Desactivar este estudiante?')">
                                    <i class="bi bi-person-dash me-2"></i>Desactivar</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
