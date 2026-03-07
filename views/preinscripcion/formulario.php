<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pre-Inscripción — <?= htmlspecialchars($inst['nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root {
    --primary: #1a56db;
    --primary-dk: #1240a8;
    --accent: #f59e0b;
    --success: #10b981;
  }
  * { box-sizing: border-box; }
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
    min-height: 100vh;
    padding: 2rem 0;
  }
  .form-wrapper {
    max-width: 820px;
    margin: 0 auto;
    padding: 0 1rem;
  }
  /* Header institucional */
  .inst-header {
    text-align: center;
    color: #fff;
    margin-bottom: 2rem;
  }
  .inst-logo {
    width: 80px; height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,.3);
    margin-bottom: 1rem;
  }
  .inst-logo-placeholder {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    border: 3px solid rgba(255,255,255,.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #fff;
    margin-bottom: 1rem;
  }
  .inst-header h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.6rem;
    margin-bottom: .25rem;
  }
  .inst-header p { color: rgba(255,255,255,.65); font-size: .9rem; }

  /* Stepper */
  .stepper {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 2rem;
    position: relative;
  }
  .stepper::before {
    content: '';
    position: absolute;
    top: 18px; left: 15%; right: 15%;
    height: 2px;
    background: rgba(255,255,255,.15);
    z-index: 0;
  }
  .step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 1;
  }
  .step-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    border: 2px solid rgba(255,255,255,.25);
    color: rgba(255,255,255,.5);
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 700;
    transition: all .3s;
  }
  .step-item.active .step-circle {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(26,86,219,.3);
  }
  .step-item.done .step-circle {
    background: var(--success);
    border-color: var(--success);
    color: #fff;
  }
  .step-label {
    font-size: .72rem;
    color: rgba(255,255,255,.45);
    margin-top: .4rem;
    text-align: center;
    font-weight: 600;
    letter-spacing: .01em;
  }
  .step-item.active .step-label { color: rgba(255,255,255,.9); }
  .step-item.done .step-label   { color: var(--success); }

  /* Card del formulario */
  .form-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0,0,0,.4);
    overflow: hidden;
  }
  .section-header {
    display: none;
    padding: 1.75rem 2rem 0;
  }
  .section-header.active { display: block; }
  .section-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: .25rem;
  }
  .section-header p { color: #64748b; font-size: .875rem; }

  /* Secciones del form */
  .form-section {
    display: none;
    padding: 1.5rem 2rem;
    animation: fadeIn .25s ease;
  }
  .form-section.active { display: block; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  /* Labels y controles */
  .form-label {
    font-size: .8rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .4rem;
  }
  .form-label .required { color: var(--primary); }
  .form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    padding: .6rem .9rem;
    font-size: .9rem;
    transition: border-color .2s, box-shadow .2s;
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26,86,219,.12);
  }
  .form-control.is-invalid, .form-select.is-invalid {
    border-color: #ef4444;
  }

  /* Upload zones */
  .upload-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    cursor: pointer;
    transition: all .25s;
    background: #f8fafc;
    position: relative;
  }
  .upload-zone:hover, .upload-zone.dragover {
    border-color: var(--primary);
    background: #eff6ff;
  }
  .upload-zone.has-file {
    border-color: var(--success);
    background: #f0fdf4;
  }
  .upload-zone input[type=file] {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer;
    width: 100%; height: 100%;
  }
  .upload-icon { font-size: 1.75rem; color: #94a3b8; margin-bottom: .4rem; }
  .upload-zone.has-file .upload-icon { color: var(--success); }
  .upload-label {
    font-size: .82rem;
    font-weight: 600;
    color: #475569;
    display: block;
    margin-bottom: .15rem;
  }
  .upload-zone.has-file .upload-label { color: var(--success); }
  .upload-hint { font-size: .72rem; color: #94a3b8; }
  .upload-badge {
    position: absolute;
    top: 8px; right: 10px;
    background: var(--success);
    color: #fff;
    border-radius: 20px;
    padding: 1px 8px;
    font-size: .7rem;
    font-weight: 700;
    display: none;
  }
  .upload-zone.has-file .upload-badge { display: inline-block; }

  /* Doc obligatorio */
  .upload-zone.obligatorio {
    border-color: #fbbf24;
    background: #fffbeb;
  }
  .upload-zone.obligatorio.has-file {
    border-color: var(--success);
    background: #f0fdf4;
  }
  .req-badge {
    position: absolute;
    top: 8px; left: 10px;
    background: #f59e0b;
    color: #fff;
    border-radius: 20px;
    padding: 1px 8px;
    font-size: .68rem;
    font-weight: 700;
  }
  .upload-zone.has-file .req-badge { display: none; }

  /* Radio sexo */
  .sexo-group { display: flex; gap: 1rem; }
  .sexo-btn {
    flex: 1;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: .7rem;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: #f8fafc;
    font-weight: 600;
    font-size: .9rem;
    color: #64748b;
    user-select: none;
  }
  .sexo-btn:hover { border-color: var(--primary); }
  .sexo-btn.selected-m { border-color: #7c3aed; background: #f5f3ff; color: #7c3aed; }
  .sexo-btn.selected-f { border-color: #db2777; background: #fdf2f8; color: #db2777; }

  /* Checkbox estilizado */
  .check-card {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: .75rem 1rem;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: .75rem;
  }
  .check-card:hover { border-color: var(--primary); background: #eff6ff; }
  .check-card input { width: 1.1rem; height: 1.1rem; cursor: pointer; }

  /* Navegación entre pasos */
  .form-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 2rem 1.75rem;
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
  }
  .btn-nav-next {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: .7rem 2rem;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .5rem;
    transition: background .2s, transform .1s;
  }
  .btn-nav-next:hover { background: var(--primary-dk); }
  .btn-nav-next:active { transform: scale(.98); }
  .btn-nav-back {
    background: transparent;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: .7rem 1.5rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all .2s;
  }
  .btn-nav-back:hover { border-color: #94a3b8; color: #374151; }

  /* Alertas de error */
  .alert-preinsc {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin: 1.25rem 2rem;
    font-size: .875rem;
    color: #dc2626;
  }
  .alert-preinsc ul { margin: .5rem 0 0; padding-left: 1.25rem; }

  /* Sección docs: 2 columnas en desktop */
  .docs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  @media (max-width: 576px) {
    .docs-grid { grid-template-columns: 1fr; }
    .form-section { padding: 1.25rem 1.25rem; }
    .section-header { padding: 1.5rem 1.25rem 0; }
    .form-nav { padding: 1rem 1.25rem 1.5rem; }
    .sexo-group { flex-direction: column; }
  }

  /* Progreso visual */
  .progress-bar-custom {
    height: 4px;
    background: #e2e8f0;
    position: relative;
    overflow: hidden;
  }
  .progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #818cf8);
    transition: width .4s ease;
  }

  /* Sección condicional colegio anterior */
  #sec-colegio-anterior { display: none; }
  #sec-colegio-anterior.visible { display: block; }
</style>
</head>
<body>

<div class="form-wrapper">

  <!-- Header institucional -->
  <div class="inst-header">
    <?php if ($inst['logo']): ?>
    <img src="<?= htmlspecialchars($inst['logo']) ?>" class="inst-logo" alt="Logo">
    <?php else: ?>
    <div class="inst-logo-placeholder"><i class="bi bi-building"></i></div>
    <?php endif; ?>
    <h1><?= htmlspecialchars($inst['nombre']) ?></h1>
    <p><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(($inst['municipio'] ?? '') . ', ' . ($inst['provincia'] ?? '')) ?></p>
    <div class="mt-2">
      <span style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);
                   padding:4px 14px;border-radius:20px;font-size:.8rem;font-weight:700">
        <i class="bi bi-mortarboard-fill me-1"></i>Formulario de Pre-Inscripción <?= date('Y') ?>–<?= date('Y')+1 ?>
      </span>
    </div>
  </div>

  <!-- Stepper -->
  <div class="stepper" id="stepper">
    <div class="step-item active" data-step="1">
      <div class="step-circle">1</div>
      <div class="step-label">Estudiante</div>
    </div>
    <div class="step-item" data-step="2">
      <div class="step-circle">2</div>
      <div class="step-label">Médico</div>
    </div>
    <div class="step-item" data-step="3">
      <div class="step-circle">3</div>
      <div class="step-label">Tutor</div>
    </div>
    <div class="step-item" data-step="4">
      <div class="step-circle">4</div>
      <div class="step-label">Documentos</div>
    </div>
    <div class="step-item" data-step="5">
      <div class="step-circle">5</div>
      <div class="step-label">Revisión</div>
    </div>
  </div>

  <!-- Formulario -->
  <div class="form-card">
    <div class="progress-bar-custom">
      <div class="progress-bar-fill" id="progressBar" style="width:20%"></div>
    </div>

    <!-- Errores -->
    <?php if (!empty($error)): ?>
    <div class="alert-preinsc">
      <strong><i class="bi bi-exclamation-triangle me-1"></i>Por favor corrige los siguientes errores:</strong>
      <ul>
        <?php foreach ((array)$error as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="frmPreinsc" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <?php
    $appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
    $sub    = $inst['subdomain'];
    $provincias = ['Azua','Bahoruco','Barahona','Dajabón','Distrito Nacional','Duarte',
                   'El Seibo','Elías Piña','Espaillat','Hato Mayor','Hermanas Mirabal',
                   'Independencia','La Altagracia','La Romana','La Vega','María Trinidad Sánchez',
                   'Monseñor Nouel','Monte Cristi','Monte Plata','Pedernales','Peravia',
                   'Puerto Plata','Samaná','San Cristóbal','San José de Ocoa','San Juan',
                   'San Pedro de Macorís','Sánchez Ramírez','Santiago','Santiago Rodríguez',
                   'Santo Domingo','Valverde'];
    ?>

    <!-- ══════ PASO 1: Datos del estudiante ══════ -->
    <div class="section-header active" id="hdr-1">
      <h2><i class="bi bi-person-fill me-2 text-primary"></i>Datos del Estudiante</h2>
      <p>Información personal del niño/niña que desea inscribir.</p>
    </div>
    <div class="form-section active" id="sec-1">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombres <span class="required">*</span></label>
          <input type="text" name="nombres" class="form-control" placeholder="Ej: María Elena"
                 required autocomplete="given-name">
        </div>
        <div class="col-md-6">
          <label class="form-label">Apellidos <span class="required">*</span></label>
          <input type="text" name="apellidos" class="form-control" placeholder="Ej: Pérez García"
                 required autocomplete="family-name">
        </div>
        <div class="col-md-6">
          <label class="form-label">Fecha de nacimiento <span class="required">*</span></label>
          <input type="date" name="fecha_nacimiento" class="form-control"
                 max="<?= date('Y-m-d', strtotime('-3 years')) ?>"
                 min="<?= date('Y-m-d', strtotime('-25 years')) ?>"
                 required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Sexo <span class="required">*</span></label>
          <div class="sexo-group" id="sexoGroup">
            <div class="sexo-btn" id="btnM" onclick="setSexo('M')">
              <i class="bi bi-gender-male me-1"></i>Masculino
            </div>
            <div class="sexo-btn" id="btnF" onclick="setSexo('F')">
              <i class="bi bi-gender-female me-1"></i>Femenino
            </div>
          </div>
          <input type="hidden" name="sexo" id="inputSexo" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Lugar de nacimiento</label>
          <input type="text" name="lugar_nacimiento" class="form-control"
                 placeholder="Municipio, Provincia">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nacionalidad</label>
          <input type="text" name="nacionalidad" class="form-control" value="Dominicana">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cédula (si ya tiene)</label>
          <input type="text" name="cedula" class="form-control" placeholder="000-0000000-0">
        </div>
        <div class="col-md-6">
          <label class="form-label">NIE MINERD (si tiene)</label>
          <input type="text" name="nie" class="form-control" placeholder="Número del MINERD">
        </div>
        <div class="col-12">
          <label class="form-label">Dirección de residencia</label>
          <textarea name="direccion" class="form-control" rows="2"
                    placeholder="Calle, número, sector…"></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Municipio</label>
          <input type="text" name="municipio" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Provincia <span class="required">*</span></label>
          <select name="provincia" class="form-select" required>
            <option value="">Seleccionar…</option>
            <?php foreach ($provincias as $p): ?>
            <option value="<?= $p ?>"><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Grado al que aplica</label>
          <select name="grado_solicitado" class="form-select">
            <option value="">No especificado</option>
            <?php foreach ($grados as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- ══════ PASO 2: Datos médicos ══════ -->
    <div class="section-header" id="hdr-2">
      <h2><i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Información Médica</h2>
      <p>Esta información es confidencial y se usa únicamente en emergencias.</p>
    </div>
    <div class="form-section" id="sec-2">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Tipo de sangre</label>
          <select name="tipo_sangre" class="form-select">
            <option value="">No especificado</option>
            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts): ?>
            <option><?= $ts ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Alergias conocidas</label>
          <textarea name="alergias" class="form-control" rows="3"
                    placeholder="Ej: Penicilina, mariscos, látex, nueces… (déjalo en blanco si ninguna)"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Condiciones médicas</label>
          <textarea name="condiciones_medicas" class="form-control" rows="3"
                    placeholder="Ej: Asma, diabetes tipo 1, epilepsia, TDAH… (déjalo en blanco si ninguna)"></textarea>
        </div>
        <div class="col-12">
          <label class="check-card" id="chkOtroCol">
            <input type="checkbox" name="viene_de_otro_colegio" value="1"
                   id="vieneDeOtroCol" onchange="toggleColegioAnterior(this)">
            <div>
              <div style="font-weight:700;color:#0f172a">¿Viene de otro centro educativo?</div>
              <div style="font-size:.82rem;color:#64748b">Marca esto si el estudiante estuvo matriculado en otro colegio.</div>
            </div>
          </label>
        </div>
        <div class="col-12" id="sec-colegio-anterior">
          <label class="form-label">Nombre del colegio anterior</label>
          <input type="text" name="colegio_anterior" class="form-control"
                 placeholder="Nombre del centro educativo anterior">
        </div>
      </div>
    </div>

    <!-- ══════ PASO 3: Datos del tutor ══════ -->
    <div class="section-header" id="hdr-3">
      <h2><i class="bi bi-people-fill me-2 text-primary"></i>Padre / Madre / Tutor</h2>
      <p>Datos del responsable legal del estudiante. <strong>Será el contacto principal del colegio.</strong></p>
    </div>
    <div class="form-section" id="sec-3">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Parentesco <span class="required">*</span></label>
          <select name="tutor_parentesco" class="form-select" required>
            <option value="padre">Padre</option>
            <option value="madre">Madre</option>
            <option value="tutor" selected>Tutor/a legal</option>
            <option value="abuelo">Abuelo</option>
            <option value="abuela">Abuela</option>
            <option value="tio">Tío/a</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nombres <span class="required">*</span></label>
          <input type="text" name="tutor_nombres" class="form-control" required
                 autocomplete="given-name">
        </div>
        <div class="col-md-4">
          <label class="form-label">Apellidos <span class="required">*</span></label>
          <input type="text" name="tutor_apellidos" class="form-control" required
                 autocomplete="family-name">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cédula de identidad</label>
          <input type="text" name="tutor_cedula" class="form-control" placeholder="000-0000000-0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono fijo</label>
          <input type="tel" name="tutor_telefono_fijo" class="form-control" placeholder="809-000-0000">
        </div>
        <div class="col-md-6">
          <label class="form-label">Celular <span class="required">*</span></label>
          <input type="tel" name="tutor_telefono" class="form-control" required
                 placeholder="809-000-0000" autocomplete="tel">
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo electrónico <span class="required">*</span></label>
          <input type="email" name="tutor_email" class="form-control" required
                 placeholder="ejemplo@correo.com" autocomplete="email">
          <div class="form-text">Aquí recibirá confirmaciones y comunicados del colegio.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Ocupación</label>
          <input type="text" name="tutor_ocupacion" class="form-control"
                 placeholder="Ej: Médico, Contador, Comerciante…">
        </div>
        <div class="col-md-6">
          <label class="form-label">Lugar de trabajo</label>
          <input type="text" name="tutor_lugar_trabajo" class="form-control">
        </div>
      </div>
    </div>

    <!-- ══════ PASO 4: Documentos ══════ -->
    <div class="section-header" id="hdr-4">
      <h2><i class="bi bi-folder2-open me-2 text-warning"></i>Documentos Requeridos</h2>
      <p>Sube los documentos en formato <strong>JPG, PNG o PDF</strong>. Máximo 5MB por archivo.</p>
    </div>
    <div class="form-section" id="sec-4">

      <!-- Documentos obligatorios -->
      <p class="fw-bold text-dark mb-2" style="font-size:.85rem">
        <span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px">
          <i class="bi bi-star-fill me-1"></i>OBLIGATORIOS
        </span>
      </p>
      <div class="docs-grid mb-4">

        <div>
          <p class="form-label mb-2">Foto del estudiante <span class="required">*</span></p>
          <div class="upload-zone obligatorio" id="zone-foto"
               onclick="document.getElementById('doc-foto').click()"
               ondragover="dragOver(event,this)" ondragleave="dragLeave(this)"
               ondrop="drop(event,this,'doc-foto')">
            <input type="file" id="doc-foto" name="foto" accept="image/*"
                   onchange="fileSelected(this,'zone-foto','lbl-foto')">
            <span class="req-badge">Obligatorio</span>
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-person-bounding-box"></i></div>
            <span class="upload-label" id="lbl-foto">Foto reciente del estudiante</span>
            <span class="upload-hint">JPG, PNG · Máx. 5MB</span>
          </div>
        </div>

        <div>
          <p class="form-label mb-2">Acta de nacimiento <span class="required">*</span></p>
          <div class="upload-zone obligatorio" id="zone-acta"
               onclick="document.getElementById('doc-acta').click()"
               ondragover="dragOver(event,this)" ondragleave="dragLeave(this)"
               ondrop="drop(event,this,'doc-acta')">
            <input type="file" id="doc-acta" name="acta_nacimiento" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-acta','lbl-acta')">
            <span class="req-badge">Obligatorio</span>
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-file-earmark-text"></i></div>
            <span class="upload-label" id="lbl-acta">Acta de nacimiento</span>
            <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
          </div>
        </div>

        <div>
          <p class="form-label mb-2">Cédula del tutor <span class="required">*</span></p>
          <div class="upload-zone obligatorio" id="zone-cedtut"
               onclick="document.getElementById('doc-cedtut').click()"
               ondragover="dragOver(event,this)" ondragleave="dragLeave(this)"
               ondrop="drop(event,this,'doc-cedtut')">
            <input type="file" id="doc-cedtut" name="cedula_tutor" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-cedtut','lbl-cedtut')">
            <span class="req-badge">Obligatorio</span>
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-credit-card-2-front"></i></div>
            <span class="upload-label" id="lbl-cedtut">Cédula padre/madre/tutor</span>
            <span class="upload-hint">Ambos lados · Máx. 5MB</span>
          </div>
        </div>

        <div>
          <p class="form-label mb-2">Certificado médico <span class="required">*</span></p>
          <div class="upload-zone obligatorio" id="zone-medico"
               onclick="document.getElementById('doc-medico').click()"
               ondragover="dragOver(event,this)" ondragleave="dragLeave(this)"
               ondrop="drop(event,this,'doc-medico')">
            <input type="file" id="doc-medico" name="cert_medico" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-medico','lbl-medico')">
            <span class="req-badge">Obligatorio</span>
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-clipboard2-pulse"></i></div>
            <span class="upload-label" id="lbl-medico">Certificado médico vigente</span>
            <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
          </div>
        </div>

        <div>
          <p class="form-label mb-2">Tarjeta de vacunas <span class="required">*</span></p>
          <div class="upload-zone obligatorio" id="zone-vacuna"
               onclick="document.getElementById('doc-vacuna').click()"
               ondragover="dragOver(event,this)" ondragleave="dragLeave(this)"
               ondrop="drop(event,this,'doc-vacuna')">
            <input type="file" id="doc-vacuna" name="vacunas" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-vacuna','lbl-vacuna')">
            <span class="req-badge">Obligatorio</span>
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-shield-plus"></i></div>
            <span class="upload-label" id="lbl-vacuna">Tarjeta de vacunas</span>
            <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
          </div>
        </div>
      </div>

      <!-- Documentos del colegio anterior (condicionales) -->
      <div id="docs-colegio-anterior" style="display:none">
        <p class="fw-bold text-dark mb-2" style="font-size:.85rem">
          <span style="background:#fce7f3;color:#9d174d;padding:2px 10px;border-radius:20px">
            <i class="bi bi-building me-1"></i>COLEGIO ANTERIOR (Obligatorio si aplica)
          </span>
        </p>
        <div class="docs-grid mb-4">
          <div>
            <p class="form-label mb-2">Boletín / Notas <span class="required">*</span></p>
            <div class="upload-zone obligatorio" id="zone-notas"
                 onclick="document.getElementById('doc-notas').click()">
              <input type="file" id="doc-notas" name="notas_anterior" accept="image/*,.pdf"
                     onchange="fileSelected(this,'zone-notas','lbl-notas')">
              <span class="req-badge">Obligatorio</span>
              <span class="upload-badge">✓ Subido</span>
              <div class="upload-icon"><i class="bi bi-journal-text"></i></div>
              <span class="upload-label" id="lbl-notas">Boletín oficial de notas</span>
              <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
            </div>
          </div>
          <div>
            <p class="form-label mb-2">Carta de saldo <span class="required">*</span></p>
            <div class="upload-zone obligatorio" id="zone-saldo"
                 onclick="document.getElementById('doc-saldo').click()">
              <input type="file" id="doc-saldo" name="carta_saldo" accept="image/*,.pdf"
                     onchange="fileSelected(this,'zone-saldo','lbl-saldo')">
              <span class="req-badge">Obligatorio</span>
              <span class="upload-badge">✓ Subido</span>
              <div class="upload-icon"><i class="bi bi-receipt"></i></div>
              <span class="upload-label" id="lbl-saldo">Carta de saldo / paz y salvo</span>
              <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
            </div>
          </div>
          <div>
            <p class="form-label mb-2">SIGERD / Historial MINERD</p>
            <div class="upload-zone" id="zone-sigerd"
                 onclick="document.getElementById('doc-sigerd').click()">
              <input type="file" id="doc-sigerd" name="sigerd" accept="image/*,.pdf"
                     onchange="fileSelected(this,'zone-sigerd','lbl-sigerd')">
              <span class="upload-badge">✓ Subido</span>
              <div class="upload-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
              <span class="upload-label" id="lbl-sigerd">Historial MINERD / SIGERD</span>
              <span class="upload-hint">Opcional · PDF · Máx. 5MB</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Documentos adicionales opcionales -->
      <p class="fw-bold text-dark mb-2" style="font-size:.85rem">
        <span style="background:#f0f9ff;color:#0369a1;padding:2px 10px;border-radius:20px">
          <i class="bi bi-plus-circle me-1"></i>ADICIONALES (opcionales)
        </span>
      </p>
      <div class="docs-grid">
        <div>
          <p class="form-label mb-2">Documento adicional 1</p>
          <div class="upload-zone" id="zone-ext1"
               onclick="document.getElementById('doc-ext1').click()">
            <input type="file" id="doc-ext1" name="extra_1" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-ext1','lbl-ext1')">
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-paperclip"></i></div>
            <span class="upload-label" id="lbl-ext1">Cualquier otro documento</span>
            <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
          </div>
        </div>
        <div>
          <p class="form-label mb-2">Documento adicional 2</p>
          <div class="upload-zone" id="zone-ext2"
               onclick="document.getElementById('doc-ext2').click()">
            <input type="file" id="doc-ext2" name="extra_2" accept="image/*,.pdf"
                   onchange="fileSelected(this,'zone-ext2','lbl-ext2')">
            <span class="upload-badge">✓ Subido</span>
            <div class="upload-icon"><i class="bi bi-paperclip"></i></div>
            <span class="upload-label" id="lbl-ext2">Cualquier otro documento</span>
            <span class="upload-hint">JPG, PNG, PDF · Máx. 5MB</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════ PASO 5: Revisión y envío ══════ -->
    <div class="section-header" id="hdr-5">
      <h2><i class="bi bi-check2-circle me-2 text-success"></i>Revisión Final</h2>
      <p>Verifica que toda la información sea correcta antes de enviar.</p>
    </div>
    <div class="form-section" id="sec-5">
      <div id="resumen-data" class="row g-3 small">
        <!-- Se llena por JS -->
      </div>
      <div class="mt-3 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="chkAcepto" required>
          <label class="form-check-label" for="chkAcepto" style="font-size:.875rem">
            Declaro que la información proporcionada es verídica y que los documentos
            subidos son auténticos. Entiendo que la aprobación está sujeta a revisión
            por parte del colegio.
          </label>
        </div>
      </div>
    </div>

    <!-- Navegación -->
    <div class="form-nav" id="formNav">
      <button type="button" class="btn-nav-back" id="btnBack" style="visibility:hidden"
              onclick="goStep(-1)">
        <i class="bi bi-arrow-left me-1"></i>Anterior
      </button>
      <button type="button" class="btn-nav-next" id="btnNext" onclick="goStep(1)">
        Siguiente <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>

    </form>
  </div><!-- form-card -->

  <p class="text-center mt-3" style="color:rgba(255,255,255,.4);font-size:.75rem">
    EduSaaS RD · Sistema de Gestión Educativa · <?= date('Y') ?>
  </p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
let currentStep = 1;
const totalSteps = 5;
const progressPct = [20, 40, 60, 80, 100];

function goStep(dir) {
  if (dir === 1 && !validateCurrentStep()) return;

  const newStep = currentStep + dir;
  if (newStep < 1 || newStep > totalSteps) return;

  // Ocultar actual
  document.getElementById('sec-' + currentStep).classList.remove('active');
  document.getElementById('hdr-' + currentStep).classList.remove('active');
  document.querySelector('[data-step="' + currentStep + '"]').classList.remove('active');
  if (dir === 1) {
    document.querySelector('[data-step="' + currentStep + '"]').classList.add('done');
  } else {
    document.querySelector('[data-step="' + (currentStep) + '"]').classList.remove('done');
  }

  currentStep = newStep;

  // Mostrar nuevo
  document.getElementById('sec-' + currentStep).classList.add('active');
  document.getElementById('hdr-' + currentStep).classList.add('active');
  document.querySelector('[data-step="' + currentStep + '"]').classList.add('active');
  document.getElementById('progressBar').style.width = progressPct[currentStep - 1] + '%';

  // Botones
  document.getElementById('btnBack').style.visibility = currentStep > 1 ? 'visible' : 'hidden';

  if (currentStep === totalSteps) {
    fillResumen();
    document.getElementById('btnNext').innerHTML =
      '<i class="bi bi-send-fill me-1"></i>Enviar Solicitud';
    document.getElementById('btnNext').onclick = submitForm;
  } else {
    document.getElementById('btnNext').innerHTML =
      'Siguiente <i class="bi bi-arrow-right ms-1"></i>';
    document.getElementById('btnNext').onclick = () => goStep(1);
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateCurrentStep() {
  if (currentStep === 1) {
    const nombres = document.querySelector('[name=nombres]').value.trim();
    const apellidos = document.querySelector('[name=apellidos]').value.trim();
    const fnac = document.querySelector('[name=fecha_nacimiento]').value;
    const sexo = document.getElementById('inputSexo').value;
    const prov = document.querySelector('[name=provincia]').value;
    if (!nombres || !apellidos || !fnac || !sexo || !prov) {
      showStepError('Completa todos los campos obligatorios (marcados con *).');
      return false;
    }
  }
  if (currentStep === 3) {
    const tnom = document.querySelector('[name=tutor_nombres]').value.trim();
    const tape = document.querySelector('[name=tutor_apellidos]').value.trim();
    const ttel = document.querySelector('[name=tutor_telefono]').value.trim();
    const tem  = document.querySelector('[name=tutor_email]').value.trim();
    if (!tnom || !tape || !ttel || !tem) {
      showStepError('Completa todos los campos obligatorios del tutor.');
      return false;
    }
    if (!/\S+@\S+\.\S+/.test(tem)) {
      showStepError('El correo del tutor no es válido.');
      return false;
    }
  }
  if (currentStep === 4) {
    const obligatorios = ['doc-foto','doc-acta','doc-cedtut','doc-medico','doc-vacuna'];
    for (const id of obligatorios) {
      const el = document.getElementById(id);
      if (!el.files || !el.files[0]) {
        showStepError('Debes subir todos los documentos obligatorios (borde amarillo).');
        return false;
      }
    }
    // Si viene de otro colegio
    if (document.getElementById('vieneDeOtroCol').checked) {
      const notas = document.getElementById('doc-notas');
      const saldo = document.getElementById('doc-saldo');
      if (!notas.files[0] || !saldo.files[0]) {
        showStepError('Debes subir las notas y carta de saldo del colegio anterior.');
        return false;
      }
    }
  }
  if (currentStep === 5) {
    if (!document.getElementById('chkAcepto').checked) {
      showStepError('Debes aceptar la declaración antes de enviar.');
      return false;
    }
  }
  clearStepError();
  return true;
}

function submitForm() {
  if (!validateCurrentStep()) return;
  document.getElementById('frmPreinsc').submit();
}

function showStepError(msg) {
  clearStepError();
  const div = document.createElement('div');
  div.id = 'stepError';
  div.className = 'alert-preinsc mx-0 mt-3';
  div.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + msg;
  const sec = document.getElementById('sec-' + currentStep);
  sec.insertAdjacentElement('afterend', div);
}

function clearStepError() {
  const el = document.getElementById('stepError');
  if (el) el.remove();
}

// Sexo
function setSexo(val) {
  document.getElementById('inputSexo').value = val;
  document.getElementById('btnM').className = 'sexo-btn' + (val==='M' ? ' selected-m' : '');
  document.getElementById('btnF').className = 'sexo-btn' + (val==='F' ? ' selected-f' : '');
}

// Toggle colegio anterior
function toggleColegioAnterior(cb) {
  const sec = document.getElementById('sec-colegio-anterior');
  const docs = document.getElementById('docs-colegio-anterior');
  sec.classList.toggle('visible', cb.checked);
  docs.style.display = cb.checked ? 'block' : 'none';
}

// Upload zones
function fileSelected(input, zoneId, lblId) {
  const zone = document.getElementById(zoneId);
  const lbl  = document.getElementById(lblId);
  if (input.files && input.files[0]) {
    const name = input.files[0].name;
    zone.classList.add('has-file');
    lbl.textContent = name.length > 28 ? name.substring(0,25)+'…' : name;
  }
}

function dragOver(e, zone) {
  e.preventDefault();
  zone.classList.add('dragover');
}

function dragLeave(zone) {
  zone.classList.remove('dragover');
}

function drop(e, zone, inputId) {
  e.preventDefault();
  zone.classList.remove('dragover');
  const input = document.getElementById(inputId);
  input.files = e.dataTransfer.files;
  input.dispatchEvent(new Event('change'));
}

// Resumen paso 5
function fillResumen() {
  const fields = [
    ['Nombres',           document.querySelector('[name=nombres]').value],
    ['Apellidos',         document.querySelector('[name=apellidos]').value],
    ['Fecha nacimiento',  document.querySelector('[name=fecha_nacimiento]').value],
    ['Sexo',              document.getElementById('inputSexo').value === 'M' ? 'Masculino' : 'Femenino'],
    ['Provincia',         document.querySelector('[name=provincia]').value],
    ['Grado solicitado',  document.querySelector('[name=grado_solicitado]').value || '—'],
    ['Tutor',             document.querySelector('[name=tutor_nombres]').value + ' ' + document.querySelector('[name=tutor_apellidos]').value],
    ['Teléfono tutor',    document.querySelector('[name=tutor_telefono]').value],
    ['Email tutor',       document.querySelector('[name=tutor_email]').value],
  ];

  const docs = [
    ['Foto',              document.getElementById('doc-foto').files[0]?.name],
    ['Acta nacimiento',   document.getElementById('doc-acta').files[0]?.name],
    ['Cédula tutor',      document.getElementById('doc-cedtut').files[0]?.name],
    ['Cert. médico',      document.getElementById('doc-medico').files[0]?.name],
    ['Tarjeta vacunas',   document.getElementById('doc-vacuna').files[0]?.name],
  ];

  let html = '';
  fields.forEach(([k,v]) => {
    if (!v) return;
    html += `<div class="col-md-6">
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted">${k}</span>
        <span class="fw-semibold text-end">${v}</span>
      </div></div>`;
  });
  html += '<div class="col-12 mt-2"><p class="fw-bold mb-2" style="font-size:.8rem;color:#0f172a">DOCUMENTOS ADJUNTOS</p></div>';
  docs.forEach(([k,v]) => {
    const ok = !!v;
    html += `<div class="col-md-6">
      <div class="d-flex align-items-center gap-2 py-1">
        <i class="bi ${ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'}"></i>
        <span style="font-size:.82rem">${k}</span>
      </div></div>`;
  });

  document.getElementById('resumen-data').innerHTML = html;
}
</script>
</body>
</html>
