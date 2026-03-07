<?php
$appUrl  = (require __DIR__ . '/../../../config/app.php')['url'];
$e       = $estudiante;
$esEdit  = $modoEdicion && $e;
$action  = $esEdit
    ? $appUrl . '/estudiantes/' . $e['id'] . '/editar'
    : $appUrl . '/estudiantes/crear';
$val = fn(string $k, string $d='') => htmlspecialchars($e[$k] ?? $d);

$tutorResp = null;
if ($esEdit && !empty($e['tutores'])) {
    foreach ($e['tutores'] as $t) { if ($t['es_responsable']) { $tutorResp = $t; break; } }
    if (!$tutorResp) $tutorResp = $e['tutores'][0];
}

$provincias = ['Azua','Bahoruco','Barahona','Dajabón','Distrito Nacional','Duarte',
               'El Seibo','Elías Piña','Espaillat','Hato Mayor','Hermanas Mirabal',
               'Independencia','La Altagracia','La Romana','La Vega','María Trinidad Sánchez',
               'Monseñor Nouel','Monte Cristi','Monte Plata','Pedernales','Peravia',
               'Puerto Plata','Samaná','San Cristóbal','San José de Ocoa','San Juan',
               'San Pedro de Macorís','Sánchez Ramírez','Santiago','Santiago Rodríguez',
               'Santo Domingo','Valverde'];

$tiposSangre = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/estudiantes">Estudiantes</a></li>
        <?php if ($esEdit): ?>
        <li class="breadcrumb-item"><a href="<?= $appUrl ?>/estudiantes/<?= $e['id'] ?>"><?= htmlspecialchars($e['nombres'].' '.$e['apellidos']) ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?= $esEdit ? 'Editar' : 'Nuevo Estudiante' ?></li>
    </ol>
</nav>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<div class="row g-4">

    <!-- Columna izquierda: foto + identificación -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold small"><i class="bi bi-camera me-2 text-primary"></i>Foto</div>
            <div class="card-body text-center">
                <div id="foto-preview" class="mb-3">
                    <?php if ($esEdit && $e['foto']): ?>
                    <img src="<?= htmlspecialchars($e['foto']) ?>" class="rounded-circle shadow-sm"
                         style="width:100px;height:100px;object-fit:cover">
                    <?php else: ?>
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light border"
                         style="width:100px;height:100px">
                        <i class="bi bi-person fs-1 text-muted"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" name="foto" id="inputFoto" class="d-none"
                       accept="image/jpeg,image/png,image/webp">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="document.getElementById('inputFoto').click()">
                    <i class="bi bi-upload me-1"></i>
                    <?= ($esEdit && $e['foto']) ? 'Cambiar foto' : 'Subir foto' ?>
                </button>
                <div class="text-muted small mt-1">JPG, PNG · Máx. 2MB</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold small"><i class="bi bi-card-text me-2 text-primary"></i>Identificación</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Cédula / Pasaporte</label>
                    <input type="text" name="cedula" class="form-control" value="<?= $val('cedula') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">NIE (MINERD)</label>
                    <input type="text" name="nie" class="form-control" value="<?= $val('nie') ?>"
                           placeholder="Opcional">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Tipo de sangre</label>
                    <select name="tipo_sangre" class="form-select">
                        <option value="">No especificado</option>
                        <?php foreach ($tiposSangre as $ts): ?>
                        <option value="<?= $ts ?>" <?= ($e['tipo_sangre'] ?? '') === $ts ? 'selected' : '' ?>>
                            <?= $ts ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna derecha: tabs -->
    <div class="col-lg-8">
        <ul class="nav nav-tabs mb-3" id="formTabs">
            <li class="nav-item">
                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal">
                    <i class="bi bi-person me-1"></i>Personal
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-medico">
                    <i class="bi bi-heart-pulse me-1"></i>Médico
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tutor">
                    <i class="bi bi-people me-1"></i>Tutor / Padre
                </button>
            </li>
            <?php if (!$esEdit): ?>
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-matricula">
                    <i class="bi bi-journal-bookmark me-1"></i>Matrícula
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content">

            <!-- Tab Personal -->
            <div class="tab-pane fade show active" id="tab-personal">
                <div class="card"><div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombres" class="form-control" required
                                   value="<?= $val('nombres') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" class="form-control" required
                                   value="<?= $val('apellidos') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" class="form-control" required
                                   value="<?= $val('fecha_nacimiento') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sexo" value="M" id="sexoM"
                                           <?= ($e['sexo'] ?? 'M') === 'M' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="sexoM">♂ Masculino</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sexo" value="F" id="sexoF"
                                           <?= ($e['sexo'] ?? '') === 'F' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="sexoF">♀ Femenino</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lugar de nacimiento</label>
                            <input type="text" name="lugar_nacimiento" class="form-control"
                                   value="<?= $val('lugar_nacimiento') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control"
                                   value="<?= $val('nacionalidad','Dominicana') ?>">
                        </div>
                        <div class="col-12"><hr class="my-1"><p class="fw-semibold small text-muted mb-0">Contacto</p></div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"><?= $val('direccion') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control" value="<?= $val('municipio') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Provincia</label>
                            <select name="provincia" class="form-select">
                                <option value="">Seleccionar…</option>
                                <?php foreach ($provincias as $p): ?>
                                <option value="<?= $p ?>" <?= ($e['provincia'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" value="<?= $val('telefono') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $val('email') ?>">
                        </div>
                    </div>
                </div></div>
            </div>

            <!-- Tab Médico -->
            <div class="tab-pane fade" id="tab-medico">
                <div class="card"><div class="card-body">
                    <p class="text-muted small mb-3">Información médica relevante para emergencias.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alergias conocidas</label>
                            <textarea name="alergias" class="form-control" rows="3"
                                      placeholder="Ej: Penicilina, mariscos, látex…"><?= $val('alergias') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Condiciones médicas</label>
                            <textarea name="condiciones_medicas" class="form-control" rows="3"
                                      placeholder="Ej: Asma, diabetes, epilepsia…"><?= $val('condiciones_medicas') ?></textarea>
                        </div>
                    </div>
                </div></div>
            </div>

            <!-- Tab Tutor -->
            <div class="tab-pane fade" id="tab-tutor">
                <div class="card"><div class="card-body">
                    <p class="text-muted small mb-3">Datos del padre, madre o tutor responsable.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parentesco</label>
                            <select name="tutor_parentesco" class="form-select">
                                <?php foreach(['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor/a',
                                               'abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío/a','otro'=>'Otro'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= ($tutorResp['parentesco']??'tutor')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nombres</label>
                            <input type="text" name="tutor_nombres" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['nombres']??'') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellidos</label>
                            <input type="text" name="tutor_apellidos" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['apellidos']??'') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cédula</label>
                            <input type="text" name="tutor_cedula" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['cedula']??'') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="tel" name="tutor_telefono" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['telefono']??'') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tel. trabajo</label>
                            <input type="tel" name="tutor_tel_trabajo" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['telefono_trabajo']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="tutor_email" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['email']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ocupación</label>
                            <input type="text" name="tutor_ocupacion" class="form-control"
                                   value="<?= htmlspecialchars($tutorResp['ocupacion']??'') ?>">
                        </div>
                    </div>
                </div></div>
            </div>

            <!-- Tab Matrícula (solo creación) -->
            <?php if (!$esEdit): ?>
            <div class="tab-pane fade" id="tab-matricula">
                <div class="card"><div class="card-body">
                    <?php if (!$anoActivo): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No hay un año escolar activo.
                        <a href="<?= $appUrl ?>/admin/anos-escolares/crear" class="alert-link">Crear año escolar</a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-calendar-check me-1"></i>
                        Año escolar activo: <strong><?= htmlspecialchars($anoActivo['nombre']) ?></strong>
                    </div>
                    <input type="hidden" name="ano_escolar_id" value="<?= $anoActivo['id'] ?>">
                    <?php if (empty($secciones)): ?>
                    <div class="alert alert-warning py-2 small">
                        No hay secciones creadas para el año activo.
                        <a href="<?= $appUrl ?>/admin/secciones" class="alert-link">Crear secciones</a>
                    </div>
                    <?php else: ?>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Sección</label>
                        <select name="seccion_id" class="form-select">
                            <option value="">Sin matrícula por ahora</option>
                            <?php foreach ($secciones as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['grado_nombre'].' — Sección '.$s['nombre']) ?>
                                (<?= $s['total_estudiantes'] ?>/<?= $s['capacidad'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Puedes asignar la sección más adelante.</div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div></div>
            </div>
            <?php endif; ?>

        </div><!-- tab-content -->
    </div><!-- col-lg-8 -->
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="<?= $appUrl ?>/estudiantes<?= $esEdit ? '/'.$e['id'] : '' ?>"
       class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>
        <?= $esEdit ? 'Guardar cambios' : 'Registrar Estudiante' ?>
    </button>
</div>
</form>

<script>
document.getElementById('inputFoto').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('foto-preview').innerHTML =
            `<img src="${e.target.result}" class="rounded-circle shadow-sm"
                  style="width:100px;height:100px;object-fit:cover">`;
    };
    reader.readAsDataURL(file);
});
</script>
