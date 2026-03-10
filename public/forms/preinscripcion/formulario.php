<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Preinscripción — <?= htmlspecialchars($inst['nombre']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root{--azul:#1a56db;--azul-claro:#dbeafe}
  body{background:#f0f4ff;font-family:'Segoe UI',sans-serif;color:#1e293b}

  /* Header */
  .pre-header{background:var(--azul);color:#fff;padding:1.5rem 0;text-align:center}
  .pre-header img{height:60px;object-fit:contain;filter:brightness(0)invert(1)}
  .pre-header h1{font-size:1.35rem;font-weight:700;margin:.5rem 0 .2rem}
  .pre-header p{opacity:.85;font-size:.9rem;margin:0}

  /* Steps */
  .steps{display:flex;justify-content:center;gap:0;margin:1.8rem 0 0;flex-wrap:wrap}
  .step{display:flex;flex-direction:column;align-items:center;padding:0 .6rem;cursor:pointer;min-width:80px}
  .step-num{width:36px;height:36px;border-radius:50%;background:#cbd5e1;color:#64748b;
            font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;
            transition:.3s;position:relative;z-index:1}
  .step.activo .step-num{background:var(--azul);color:#fff;box-shadow:0 0 0 4px var(--azul-claro)}
  .step.completado .step-num{background:#16a34a;color:#fff}
  .step-label{font-size:.7rem;margin-top:.35rem;color:#64748b;text-align:center;font-weight:500}
  .step.activo .step-label{color:var(--azul);font-weight:700}
  .step-line{flex:1;height:2px;background:#cbd5e1;margin-top:-18px;min-width:20px;max-width:60px}
  .step.completado + .step-line{background:#16a34a}

  /* Cards */
  .pre-card{background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.07);
            padding:2rem;margin-bottom:1.5rem;border:1px solid #e2e8f0}
  .pre-card-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;
                   padding-bottom:1rem;border-bottom:2px solid #f1f5f9}
  .pre-card-header .icon-wrap{width:42px;height:42px;border-radius:10px;background:var(--azul-claro);
                              display:flex;align-items:center;justify-content:center;font-size:1.25rem;
                              color:var(--azul);flex-shrink:0}
  .pre-card-header h2{font-size:1.1rem;font-weight:700;margin:0;color:#1e293b}
  .pre-card-header p{margin:0;font-size:.82rem;color:#64748b}

  /* Upload zones */
  .upload-zone{border:2.5px dashed #94a3b8;border-radius:12px;padding:1.5rem 1rem;text-align:center;
               cursor:pointer;transition:.2s;background:#f8fafc;position:relative}
  .upload-zone:hover,.upload-zone.dragover{border-color:var(--azul);background:var(--azul-claro)}
  .upload-zone .uz-icon{font-size:2rem;color:#94a3b8;margin-bottom:.5rem;transition:.2s}
  .upload-zone:hover .uz-icon,.upload-zone.dragover .uz-icon{color:var(--azul)}
  .upload-zone .uz-title{font-weight:600;font-size:.9rem;color:#374151;margin:.3rem 0 .1rem}
  .upload-zone .uz-sub{font-size:.75rem;color:#94a3b8}
  .upload-zone .uz-preview{display:none;gap:.5rem;align-items:center;justify-content:center;
                            flex-wrap:wrap;flex-direction:column}
  .upload-zone.has-file .uz-default{display:none}
  .upload-zone.has-file .uz-preview{display:flex}
  .upload-zone .uz-preview img{width:70px;height:70px;object-fit:cover;border-radius:8px;
                                border:2px solid #d1fae5}
  .upload-zone .uz-preview .uz-filename{font-weight:600;font-size:.82rem;color:#059669;
                                        max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .upload-zone .uz-preview .uz-size{font-size:.72rem;color:#64748b}
  .upload-zone .uz-check{position:absolute;top:8px;right:8px;background:#16a34a;color:#fff;
                         border-radius:50%;width:22px;height:22px;display:none;align-items:center;
                         justify-content:center;font-size:.75rem}
  .upload-zone.has-file .uz-check{display:flex}
  .upload-zone.is-invalid{border-color:#ef4444;background:#fef2f2}
  .badge-req{font-size:.65rem;padding:.2rem .45rem;border-radius:20px;
             background:#fee2e2;color:#b91c1c;font-weight:700;vertical-align:middle;margin-left:.3rem}
  .badge-opt{font-size:.65rem;padding:.2rem .45rem;border-radius:20px;
             background:#f0fdf4;color:#16a34a;font-weight:700;vertical-align:middle;margin-left:.3rem}

  /* Navigation */
  .btn-nav{padding:.65rem 1.75rem;font-weight:600;border-radius:10px;font-size:.95rem}
  .btn-siguiente{background:var(--azul);color:#fff;border:none}
  .btn-siguiente:hover{background:#1e40af;color:#fff}
  .btn-anterior{background:#f1f5f9;color:#374151;border:none}
  .btn-anterior:hover{background:#e2e8f0;color:#1e293b}

  /* Alert */
  .alert-field{background:#fff5f5;border:1px solid #fca5a5;color:#b91c1c;
               border-radius:8px;padding:.5rem .75rem;font-size:.8rem;margin-top:.4rem}

  /* Congrats */
  .opt-docs{opacity:.6;transition:.3s}
  .opt-docs.required{opacity:1}

  /* Footer */
  .pre-footer{text-align:center;padding:1.5rem;color:#94a3b8;font-size:.78rem;margin-top:1rem}

  /* Responsive */
  @media(max-width:575px){
    .pre-card{padding:1.25rem}
    .upload-zone{padding:1rem .75rem}
  }
</style>
</head>
<body>

<!-- Header -->
<div class="pre-header">
  <?php if ($inst['logo']): ?>
  <img src="<?= htmlspecialchars($inst['logo']) ?>" alt="<?= htmlspecialchars($inst['nombre']) ?>" class="mb-2">
  <?php else: ?>
  <div class="mb-2" style="font-size:2.5rem">🏫</div>
  <?php endif; ?>
  <h1><?= htmlspecialchars($inst['nombre']) ?></h1>
  <p>Formulario de Preinscripción — Año Escolar <?= date('Y').'-'.(date('Y')+1) ?></p>
</div>

<!-- Steps indicator -->
<div class="container" style="max-width:720px">
  <div class="steps" id="stepsBar">
    <div class="step activo" data-step="1">
      <div class="step-num">1</div>
      <div class="step-label">Estudiante</div>
    </div>
    <div class="step-line"></div>
    <div class="step" data-step="2">
      <div class="step-num">2</div>
      <div class="step-label">Tutor</div>
    </div>
    <div class="step-line"></div>
    <div class="step" data-step="3">
      <div class="step-num">3</div>
      <div class="step-label">Documentos</div>
    </div>
    <div class="step-line"></div>
    <div class="step" data-step="4">
      <div class="step-num">4</div>
      <div class="step-label">Confirmación</div>
    </div>
  </div>
</div>

<!-- Errores generales -->
<?php if (!empty($errors['general'])): ?>
<div class="container mt-3" style="max-width:720px">
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $errors['general'] ?></div>
</div>
<?php endif; ?>

<div class="container py-4" style="max-width:720px">
<form method="POST" enctype="multipart/form-data" id="preForm"
      action="<?= (require __DIR__ . '/../../config/app.php')['url'] ?>/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>/enviar" novalidate>
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
<?php
$old = $old ?? [];
$v   = fn(string $k) => htmlspecialchars($old[$k] ?? '');
$err = fn(string $k) => !empty($errors[$k])
    ? '<div class="alert-field"><i class="bi bi-exclamation-circle me-1"></i>'.$errors[$k].'</div>' : '';
$provincias = ['Azua','Bahoruco','Barahona','Dajabón','Distrito Nacional','Duarte',
               'El Seibo','Elías Piña','Espaillat','Hato Mayor','Hermanas Mirabal',
               'Independencia','La Altagracia','La Romana','La Vega','María Trinidad Sánchez',
               'Monseñor Nouel','Monte Cristi','Monte Plata','Pedernales','Peravia',
               'Puerto Plata','Samaná','San Cristóbal','San José de Ocoa','San Juan',
               'San Pedro de Macorís','Sánchez Ramírez','Santiago','Santiago Rodríguez',
               'Santo Domingo','Valverde'];
$tiposSangre = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
?>

<!-- ═══════════════════════════════════════════════════ -->
<!-- PASO 1: Datos del Estudiante                        -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="paso" id="paso-1">
  <div class="pre-card">
    <div class="pre-card-header">
      <div class="icon-wrap"><i class="bi bi-person-fill"></i></div>
      <div><h2>Datos del Estudiante</h2><p>Información personal del niño/a que se va a inscribir</p></div>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
        <input type="text" name="nombres" class="form-control <?= !empty($errors['nombres'])?'is-invalid':'' ?>"
               value="<?= $v('nombres') ?>" placeholder="Ej: Juan Carlos" autocomplete="given-name">
        <?= $err('nombres') ?>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
        <input type="text" name="apellidos" class="form-control <?= !empty($errors['apellidos'])?'is-invalid':'' ?>"
               value="<?= $v('apellidos') ?>" placeholder="Ej: Pérez Ramírez" autocomplete="family-name">
        <?= $err('apellidos') ?>
      </div>
      <div class="col-md-5">
        <label class="form-label fw-semibold">Fecha de nacimiento <span class="text-danger">*</span></label>
        <input type="date" name="fecha_nacimiento" class="form-control <?= !empty($errors['fecha_nacimiento'])?'is-invalid':'' ?>"
               value="<?= $v('fecha_nacimiento') ?>" max="<?= date('Y-m-d') ?>">
        <?= $err('fecha_nacimiento') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
        <div class="d-flex gap-4 mt-1">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="sexo" value="M" id="sexoM"
                   <?= ($old['sexo']??'M')==='M'?'checked':'' ?>>
            <label class="form-check-label" for="sexoM">♂ Masculino</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="sexo" value="F" id="sexoF"
                   <?= ($old['sexo']??'')==='F'?'checked':'' ?>>
            <label class="form-check-label" for="sexoF">♀ Femenino</label>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Tipo de sangre</label>
        <select name="tipo_sangre" class="form-select">
          <option value="">No sé</option>
          <?php foreach($tiposSangre as $ts): ?>
          <option value="<?=$ts?>" <?= ($old['tipo_sangre']??'')===$ts?'selected':'' ?>><?=$ts?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Cédula o pasaporte</label>
        <input type="text" name="cedula" class="form-control" value="<?= $v('cedula') ?>" placeholder="Opcional">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">NIE (MINERD)</label>
        <input type="text" name="nie" class="form-control" value="<?= $v('nie') ?>" placeholder="Si ya tiene">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Lugar de nacimiento</label>
        <input type="text" name="lugar_nacimiento" class="form-control" value="<?= $v('lugar_nacimiento') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Nacionalidad</label>
        <input type="text" name="nacionalidad" class="form-control" value="<?= $v('nacionalidad') ?: 'Dominicana' ?>">
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold">Dirección <span class="text-danger">*</span></label>
        <textarea name="direccion" rows="2" class="form-control <?= !empty($errors['direccion'])?'is-invalid':'' ?>"
                  placeholder="Calle, número, sector…"><?= $v('direccion') ?></textarea>
        <?= $err('direccion') ?>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Municipio</label>
        <input type="text" name="municipio" class="form-control" value="<?= $v('municipio') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Provincia</label>
        <select name="provincia" class="form-select">
          <option value="">Seleccionar…</option>
          <?php foreach($provincias as $p): ?>
          <option value="<?=$p?>" <?= ($old['provincia']??'')===$p?'selected':'' ?>><?=$p?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Teléfono del estudiante</label>
        <input type="tel" name="telefono" class="form-control" value="<?= $v('telefono') ?>" placeholder="Si aplica">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Email del estudiante</label>
        <input type="email" name="email_estudiante" class="form-control" value="<?= $v('email_estudiante') ?>" placeholder="Si aplica">
      </div>
      <!-- Información médica -->
      <div class="col-12 mt-1">
        <div class="alert alert-warning py-2 small mb-0">
          <i class="bi bi-heart-pulse me-1"></i><strong>Información médica</strong> — Importante para emergencias.
        </div>
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold">Alergias conocidas</label>
        <textarea name="alergias" rows="2" class="form-control"
                  placeholder="Ej: Penicilina, mariscos… (deje vacío si ninguna)"><?= $v('alergias') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold">Condiciones médicas</label>
        <textarea name="condiciones_medicas" rows="2" class="form-control"
                  placeholder="Ej: Asma, diabetes… (deje vacío si ninguna)"><?= $v('condiciones_medicas') ?></textarea>
      </div>
      <!-- Grado -->
      <div class="col-12 mt-1">
        <div class="alert alert-info py-2 small mb-0">
          <i class="bi bi-mortarboard me-1"></i><strong>Grado al que aspira</strong>
        </div>
      </div>
      <?php if (!empty($grados)): ?>
      <div class="col-md-7">
        <label class="form-label fw-semibold">Seleccionar grado</label>
        <select name="grado_id" class="form-select">
          <option value="">Seleccionar…</option>
          <?php foreach($grados as $g): ?>
          <option value="<?=$g['id']?>" <?= ($old['grado_id']??'')==$g['id']?'selected':'' ?>>
            <?= htmlspecialchars($g['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <div class="col-md-7">
        <label class="form-label fw-semibold">Grado al que aspira</label>
        <input type="text" name="grado_nombre" class="form-control" value="<?= $v('grado_nombre') ?>" placeholder="Ej: 1er Grado">
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="d-flex justify-content-end">
    <button type="button" class="btn btn-siguiente btn-nav" onclick="irPaso(2)">
      Siguiente <i class="bi bi-arrow-right ms-1"></i>
    </button>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- PASO 2: Datos del Tutor                             -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="paso d-none" id="paso-2">
  <div class="pre-card">
    <div class="pre-card-header">
      <div class="icon-wrap"><i class="bi bi-people-fill"></i></div>
      <div><h2>Padre / Madre / Tutor</h2><p>Datos del responsable legal del estudiante</p></div>
    </div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Parentesco <span class="text-danger">*</span></label>
        <select name="tutor_parentesco" class="form-select">
          <?php foreach(['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor/a',
                         'abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío/a','otro'=>'Otro'] as $v2=>$l): ?>
          <option value="<?=$v2?>" <?= ($old['tutor_parentesco']??'padre')===$v2?'selected':'' ?>><?=$l?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
        <input type="text" name="tutor_nombres" class="form-control <?= !empty($errors['tutor_nombres'])?'is-invalid':'' ?>"
               value="<?= $v('tutor_nombres') ?>">
        <?= $err('tutor_nombres') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
        <input type="text" name="tutor_apellidos" class="form-control <?= !empty($errors['tutor_apellidos'])?'is-invalid':'' ?>"
               value="<?= $v('tutor_apellidos') ?>">
        <?= $err('tutor_apellidos') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Cédula</label>
        <input type="text" name="tutor_cedula" class="form-control" value="<?= $v('tutor_cedula') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
        <input type="tel" name="tutor_telefono" class="form-control <?= !empty($errors['tutor_telefono'])?'is-invalid':'' ?>"
               value="<?= $v('tutor_telefono') ?>" placeholder="809-000-0000">
        <?= $err('tutor_telefono') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Celular</label>
        <input type="tel" name="tutor_celular" class="form-control" value="<?= $v('tutor_celular') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" name="tutor_email" class="form-control <?= !empty($errors['tutor_email'])?'is-invalid':'' ?>"
               value="<?= $v('tutor_email') ?>" placeholder="padre@email.com">
        <div class="form-text">Le enviaremos la confirmación a este correo.</div>
        <?= $err('tutor_email') ?>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Ocupación</label>
        <input type="text" name="tutor_ocupacion" class="form-control" value="<?= $v('tutor_ocupacion') ?>">
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold">Dirección del tutor</label>
        <textarea name="tutor_direccion" rows="2" class="form-control"
                  placeholder="Si es diferente a la del estudiante"><?= $v('tutor_direccion') ?></textarea>
      </div>
      <!-- Colegio anterior -->
      <div class="col-12 mt-1">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="viene_de_otro_colegio"
                 id="vieneDeOtro" value="1" <?= !empty($old['viene_de_otro_colegio'])?'checked':'' ?>
                 onchange="toggleColegioAnterior(this.checked)">
          <label class="form-check-label fw-semibold" for="vieneDeOtro">
            El estudiante viene de otro colegio
          </label>
        </div>
      </div>
      <div id="bloque-colegio-anterior" class="col-12 <?= empty($old['viene_de_otro_colegio'])?'d-none':'' ?>">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nombre del colegio anterior</label>
            <input type="text" name="colegio_anterior" class="form-control" value="<?= $v('colegio_anterior') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Último grado aprobado</label>
            <input type="text" name="ultimo_grado_aprobado" class="form-control" value="<?= $v('ultimo_grado_aprobado') ?>" placeholder="Ej: 3er Grado">
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-anterior btn-nav" onclick="irPaso(1)">
      <i class="bi bi-arrow-left me-1"></i> Anterior
    </button>
    <button type="button" class="btn btn-siguiente btn-nav" onclick="irPaso(3)">
      Siguiente <i class="bi bi-arrow-right ms-1"></i>
    </button>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- PASO 3: Documentos                                  -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="paso d-none" id="paso-3">

  <div class="pre-card">
    <div class="pre-card-header">
      <div class="icon-wrap"><i class="bi bi-file-earmark-check-fill"></i></div>
      <div><h2>Documentos Requeridos</h2><p>Todos los archivos marcados con <span class="badge-req">REQ</span> son obligatorios</p></div>
    </div>

    <div class="row g-4">

      <!-- FOTO -->
      <div class="col-md-6">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-camera-fill me-1 text-primary"></i> Foto del estudiante <span class="badge-req">REQ</span>
        </label>
        <div class="upload-zone <?= !empty($errors['foto'])?'is-invalid':'' ?>" id="zone-foto"
             onclick="document.getElementById('file-foto').click()"
             ondragover="dragOver(event,'zone-foto')" ondragleave="dragLeave('zone-foto')"
             ondrop="dropFile(event,'zone-foto','file-foto')">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-person-bounding-box"></i></div>
            <div class="uz-title">Foto reciente</div>
            <div class="uz-sub">JPG, PNG, WEBP · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <img id="prev-foto" src="#" alt="">
            <div class="uz-filename" id="name-foto"></div>
            <div class="uz-size" id="size-foto"></div>
          </div>
        </div>
        <input type="file" id="file-foto" name="foto" class="d-none" accept="image/*"
               onchange="onFile(this,'zone-foto','prev-foto','name-foto','size-foto',true)">
        <?= $err('foto') ?>
        <div class="form-text mt-1">Foto actual, fondo claro, sin gorro ni lentes.</div>
      </div>

      <!-- ACTA NACIMIENTO -->
      <div class="col-md-6">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-file-person-fill me-1 text-primary"></i> Acta de nacimiento <span class="badge-req">REQ</span>
        </label>
        <div class="upload-zone <?= !empty($errors['acta_nacimiento'])?'is-invalid':'' ?>" id="zone-acta_nacimiento"
             onclick="document.getElementById('file-acta_nacimiento').click()"
             ondragover="dragOver(event,'zone-acta_nacimiento')" ondragleave="dragLeave('zone-acta_nacimiento')"
             ondrop="dropFile(event,'zone-acta_nacimiento','file-acta_nacimiento')">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-file-earmark-person"></i></div>
            <div class="uz-title">Acta de nacimiento</div>
            <div class="uz-sub">PDF, JPG, PNG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-acta_nacimiento"></div>
            <div class="uz-size" id="size-acta_nacimiento"></div>
          </div>
        </div>
        <input type="file" id="file-acta_nacimiento" name="acta_nacimiento" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-acta_nacimiento',null,'name-acta_nacimiento','size-acta_nacimiento')">
        <?= $err('acta_nacimiento') ?>
      </div>

      <!-- CÉDULA TUTOR -->
      <div class="col-md-6">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-card-heading me-1 text-primary"></i> Cédula del padre/tutor <span class="badge-req">REQ</span>
        </label>
        <div class="upload-zone <?= !empty($errors['cedula_tutor'])?'is-invalid':'' ?>" id="zone-cedula_tutor"
             onclick="document.getElementById('file-cedula_tutor').click()"
             ondragover="dragOver(event,'zone-cedula_tutor')" ondragleave="dragLeave('zone-cedula_tutor')"
             ondrop="dropFile(event,'zone-cedula_tutor','file-cedula_tutor')">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-credit-card-2-front"></i></div>
            <div class="uz-title">Cédula de identidad</div>
            <div class="uz-sub">PDF, JPG, PNG · Máx 5MB · Ambas caras</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-cedula_tutor"></div>
            <div class="uz-size" id="size-cedula_tutor"></div>
          </div>
        </div>
        <input type="file" id="file-cedula_tutor" name="cedula_tutor" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-cedula_tutor',null,'name-cedula_tutor','size-cedula_tutor')">
        <?= $err('cedula_tutor') ?>
      </div>

      <!-- CERTIFICADO MÉDICO -->
      <div class="col-md-6">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-heart-pulse-fill me-1 text-danger"></i> Certificado médico <span class="badge-req">REQ</span>
        </label>
        <div class="upload-zone <?= !empty($errors['cert_medico'])?'is-invalid':'' ?>" id="zone-cert_medico"
             onclick="document.getElementById('file-cert_medico').click()"
             ondragover="dragOver(event,'zone-cert_medico')" ondragleave="dragLeave('zone-cert_medico')"
             ondrop="dropFile(event,'zone-cert_medico','file-cert_medico')">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-file-medical"></i></div>
            <div class="uz-title">Certificado médico escolar</div>
            <div class="uz-sub">PDF, JPG, PNG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-cert_medico"></div>
            <div class="uz-size" id="size-cert_medico"></div>
          </div>
        </div>
        <input type="file" id="file-cert_medico" name="cert_medico" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-cert_medico',null,'name-cert_medico','size-cert_medico')">
        <?= $err('cert_medico') ?>
        <div class="form-text mt-1">Expedido por médico certificado, no mayor a 3 meses.</div>
      </div>

      <!-- TARJETA DE VACUNA -->
      <div class="col-12">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-bandaid-fill me-1 text-success"></i> Tarjeta de vacunación <span class="badge-req">REQ</span>
        </label>
        <div class="upload-zone <?= !empty($errors['tarjeta_vacuna'])?'is-invalid':'' ?>" id="zone-tarjeta_vacuna"
             onclick="document.getElementById('file-tarjeta_vacuna').click()"
             ondragover="dragOver(event,'zone-tarjeta_vacuna')" ondragleave="dragLeave('zone-tarjeta_vacuna')"
             ondrop="dropFile(event,'zone-tarjeta_vacuna','file-tarjeta_vacuna')"
             style="max-width:360px">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-shield-check"></i></div>
            <div class="uz-title">Tarjeta de vacunación completa</div>
            <div class="uz-sub">PDF, JPG, PNG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-tarjeta_vacuna"></div>
            <div class="uz-size" id="size-tarjeta_vacuna"></div>
          </div>
        </div>
        <input type="file" id="file-tarjeta_vacuna" name="tarjeta_vacuna" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-tarjeta_vacuna',null,'name-tarjeta_vacuna','size-tarjeta_vacuna')">
        <?= $err('tarjeta_vacuna') ?>
      </div>
    </div>
  </div>

  <!-- Documentos del colegio anterior -->
  <div class="pre-card opt-docs <?= !empty($old['viene_de_otro_colegio'])?'required':'' ?>" id="card-docs-anteriores">
    <div class="pre-card-header">
      <div class="icon-wrap" style="background:#fef3c7"><i class="bi bi-building-fill-check" style="color:#d97706"></i></div>
      <div>
        <h2>Documentos del Colegio Anterior</h2>
        <p id="label-docs-anteriores">
          <?= !empty($old['viene_de_otro_colegio'])
            ? 'Obligatorios porque indicó que viene de otro colegio'
            : 'Solo si el estudiante viene de otro colegio (puede subir después)' ?>
        </p>
      </div>
    </div>
    <div class="row g-4">

      <!-- NOTAS ANTERIORES -->
      <div class="col-md-4">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-file-earmark-bar-graph me-1" style="color:#d97706"></i>
          Notas / Certificado
          <span id="badge-notas" class="<?= !empty($old['viene_de_otro_colegio'])?'badge-req':'badge-opt' ?>">
            <?= !empty($old['viene_de_otro_colegio'])?'REQ':'OPC' ?>
          </span>
        </label>
        <div class="upload-zone <?= !empty($errors['notas_anteriores'])?'is-invalid':'' ?>" id="zone-notas_anteriores"
             onclick="document.getElementById('file-notas_anteriores').click()">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
            <div class="uz-title">Notas finales</div>
            <div class="uz-sub">PDF, JPG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-notas_anteriores"></div>
            <div class="uz-size" id="size-notas_anteriores"></div>
          </div>
        </div>
        <input type="file" id="file-notas_anteriores" name="notas_anteriores" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-notas_anteriores',null,'name-notas_anteriores','size-notas_anteriores')">
        <?= $err('notas_anteriores') ?>
      </div>

      <!-- CARTA DE SALDO -->
      <div class="col-md-4">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-envelope-check me-1" style="color:#d97706"></i>
          Carta de saldo
          <span id="badge-carta" class="<?= !empty($old['viene_de_otro_colegio'])?'badge-req':'badge-opt' ?>">
            <?= !empty($old['viene_de_otro_colegio'])?'REQ':'OPC' ?>
          </span>
        </label>
        <div class="upload-zone <?= !empty($errors['carta_saldo'])?'is-invalid':'' ?>" id="zone-carta_saldo"
             onclick="document.getElementById('file-carta_saldo').click()">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-envelope-paper"></i></div>
            <div class="uz-title">Carta de saldo</div>
            <div class="uz-sub">PDF, JPG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-carta_saldo"></div>
            <div class="uz-size" id="size-carta_saldo"></div>
          </div>
        </div>
        <input type="file" id="file-carta_saldo" name="carta_saldo" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-carta_saldo',null,'name-carta_saldo','size-carta_saldo')">
        <?= $err('carta_saldo') ?>
      </div>

      <!-- SIGERD -->
      <div class="col-md-4">
        <label class="fw-semibold d-block mb-2">
          <i class="bi bi-award me-1" style="color:#d97706"></i>
          SIGERD <span class="badge-opt">OPC</span>
        </label>
        <div class="upload-zone" id="zone-sigerd"
             onclick="document.getElementById('file-sigerd').click()">
          <div class="uz-check"><i class="bi bi-check"></i></div>
          <div class="uz-default">
            <div class="uz-icon"><i class="bi bi-patch-check"></i></div>
            <div class="uz-title">SIGERD / MINERD</div>
            <div class="uz-sub">PDF, JPG · Máx 5MB</div>
          </div>
          <div class="uz-preview">
            <div class="uz-filename" id="name-sigerd"></div>
            <div class="uz-size" id="size-sigerd"></div>
          </div>
        </div>
        <input type="file" id="file-sigerd" name="sigerd" class="d-none"
               accept=".pdf,image/*"
               onchange="onFile(this,'zone-sigerd',null,'name-sigerd','size-sigerd')">
      </div>

    </div>
  </div>

  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-anterior btn-nav" onclick="irPaso(2)">
      <i class="bi bi-arrow-left me-1"></i> Anterior
    </button>
    <button type="button" class="btn btn-siguiente btn-nav" onclick="irPaso(4)">
      Revisar solicitud <i class="bi bi-arrow-right ms-1"></i>
    </button>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- PASO 4: Confirmación                                -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="paso d-none" id="paso-4">
  <div class="pre-card">
    <div class="pre-card-header">
      <div class="icon-wrap" style="background:#dcfce7"><i class="bi bi-clipboard2-check-fill" style="color:#16a34a"></i></div>
      <div><h2>Resumen de la solicitud</h2><p>Revise los datos antes de enviar</p></div>
    </div>

    <div class="alert alert-info small py-2">
      <i class="bi bi-info-circle me-1"></i>
      Si necesita corregir algo, use el botón <strong>Anterior</strong> para volver atrás.
    </div>

    <div id="resumen-datos" class="row g-2 small"></div>

    <!-- Confirmación de veracidad -->
    <div class="mt-4 p-3 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="confirmoVeracidad" required>
        <label class="form-check-label fw-semibold" for="confirmoVeracidad">
          Declaro que toda la información proporcionada es verdadera y los documentos
          son auténticos. Entiendo que falsificar datos puede resultar en la cancelación
          de la preinscripción.
        </label>
      </div>
    </div>
    <div class="mt-3 p-3 rounded" style="background:#fffbeb;border:1px solid #fde68a">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="confirmoTratamiento" required>
        <label class="form-check-label fw-semibold" for="confirmoTratamiento">
          Autorizo al centro educativo a usar los datos personales suministrados
          exclusivamente para fines académicos y administrativos, conforme a la
          Ley 172-13 sobre protección de datos de la República Dominicana.
        </label>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center">
    <button type="button" class="btn btn-anterior btn-nav" onclick="irPaso(3)">
      <i class="bi bi-arrow-left me-1"></i> Anterior
    </button>
    <button type="submit" class="btn btn-siguiente btn-nav px-5" id="btnEnviar" disabled
            onclick="return validarConfirmaciones()">
      <i class="bi bi-send-fill me-2"></i> Enviar Preinscripción
    </button>
  </div>
</div>

</form>
</div>

<div class="pre-footer">
  <?= htmlspecialchars($inst['nombre']) ?> · Preinscripción en línea · EduSaaS RD
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Steps navigation ────────────────────────────
let pasoActual = 1;

function irPaso(n) {
  if (n > pasoActual && !validarPasoActual()) return;

  document.getElementById('paso-' + pasoActual).classList.add('d-none');
  document.getElementById('paso-' + n).classList.remove('d-none');

  // Update steps bar
  document.querySelectorAll('.step').forEach(s => {
    const sn = parseInt(s.dataset.step);
    s.classList.remove('activo','completado');
    if (sn < n) s.classList.add('completado');
    if (sn === n) s.classList.add('activo');
  });

  // Update lines
  document.querySelectorAll('.step-line').forEach((l, i) => {
    l.style.background = i < n - 1 ? '#16a34a' : '#cbd5e1';
  });

  if (n === 4) generarResumen();
  pasoActual = n;
  window.scrollTo({top: 0, behavior: 'smooth'});
}

// ── Validation per step ─────────────────────────
function validarPasoActual() {
  const paso = document.getElementById('paso-' + pasoActual);
  const reqs  = paso.querySelectorAll('[required],[data-req]');
  let ok = true;

  if (pasoActual === 1) {
    const campos = ['nombres','apellidos','fecha_nacimiento','direccion'];
    campos.forEach(c => {
      const el = document.querySelector('[name="'+c+'"]');
      if (!el) return;
      const val = el.value.trim();
      el.classList.toggle('is-invalid', !val);
      if (!val) ok = false;
    });
    const sexo = document.querySelector('[name="sexo"]:checked');
    if (!sexo) {
      ok = false;
      alert('Por favor seleccione el sexo del estudiante.');
    }
  }

  if (pasoActual === 2) {
    const campos = ['tutor_nombres','tutor_apellidos','tutor_telefono','tutor_email'];
    campos.forEach(c => {
      const el = document.querySelector('[name="'+c+'"]');
      if (!el) return;
      const val = el.value.trim();
      el.classList.toggle('is-invalid', !val);
      if (!val) ok = false;
    });
    const emailEl = document.querySelector('[name="tutor_email"]');
    if (emailEl && emailEl.value && !/\S+@\S+\.\S+/.test(emailEl.value)) {
      emailEl.classList.add('is-invalid'); ok = false;
    }
  }

  if (pasoActual === 3) {
    const obligatorios = ['foto','acta_nacimiento','cedula_tutor','cert_medico','tarjeta_vacuna'];
    obligatorios.forEach(c => {
      const fi = document.getElementById('file-' + c);
      const zone = document.getElementById('zone-' + c);
      if (!fi.files.length) {
        zone.classList.add('is-invalid'); ok = false;
        zone.scrollIntoView({behavior:'smooth',block:'center'});
      }
    });
    // Si viene de otro colegio: notas y carta saldo obligatorias
    const viene = document.getElementById('vieneDeOtro');
    if (viene && viene.checked) {
      ['notas_anteriores','carta_saldo'].forEach(c => {
        const fi = document.getElementById('file-' + c);
        const zone = document.getElementById('zone-' + c);
        if (!fi.files.length) {
          zone.classList.add('is-invalid'); ok = false;
        }
      });
    }
    if (!ok) alert('Por favor suba todos los documentos obligatorios (marcados con REQ).');
  }

  return ok;
}

// ── File upload zones ───────────────────────────
function onFile(input, zoneId, previewId, nameId, sizeId, esImagen = false) {
  const file = input.files[0];
  if (!file) return;
  const zone = document.getElementById(zoneId);
  zone.classList.remove('is-invalid');
  zone.classList.add('has-file');

  document.getElementById(nameId).textContent = file.name;
  document.getElementById(sizeId).textContent = (file.size / 1024).toFixed(0) + ' KB';

  if (esImagen && previewId) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById(previewId);
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

function dragOver(e, zoneId) {
  e.preventDefault();
  document.getElementById(zoneId).classList.add('dragover');
}
function dragLeave(zoneId) {
  document.getElementById(zoneId).classList.remove('dragover');
}
function dropFile(e, zoneId, fileId) {
  e.preventDefault();
  dragLeave(zoneId);
  const dt = e.dataTransfer;
  const input = document.getElementById(fileId);
  const isImagen = fileId === 'file-foto';
  const previewId = isImagen ? 'prev-foto' : null;
  const nameId = 'name-' + fileId.replace('file-', '');
  const sizeId = 'size-' + fileId.replace('file-', '');
  // Assign files to input
  const blob = new Blob([dt.files[0]], {type: dt.files[0].type});
  const fileList = Object.create(null);
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(dt.files[0]);
  input.files = dataTransfer.files;
  onFile(input, zoneId, previewId, nameId, sizeId, isImagen);
}

// ── Toggle colegio anterior ─────────────────────
function toggleColegioAnterior(checked) {
  const bloque = document.getElementById('bloque-colegio-anterior');
  const card   = document.getElementById('card-docs-anteriores');
  const label  = document.getElementById('label-docs-anteriores');
  const bN = document.getElementById('badge-notas');
  const bC = document.getElementById('badge-carta');

  bloque.classList.toggle('d-none', !checked);
  card.classList.toggle('required', checked);

  if (checked) {
    label.textContent = 'Obligatorios porque indicó que viene de otro colegio';
    if(bN){bN.className='badge-req';bN.textContent='REQ';}
    if(bC){bC.className='badge-req';bC.textContent='REQ';}
  } else {
    label.textContent = 'Solo si el estudiante viene de otro colegio (puede subir después)';
    if(bN){bN.className='badge-opt';bN.textContent='OPC';}
    if(bC){bC.className='badge-opt';bC.textContent='OPC';}
  }
}

// ── Confirmation checkboxes ─────────────────────
['confirmoVeracidad','confirmoTratamiento'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('change', () => {
    const v = document.getElementById('confirmoVeracidad').checked;
    const t = document.getElementById('confirmoTratamiento').checked;
    document.getElementById('btnEnviar').disabled = !(v && t);
  });
});

function validarConfirmaciones() {
  const v = document.getElementById('confirmoVeracidad').checked;
  const t = document.getElementById('confirmoTratamiento').checked;
  if (!v || !t) { alert('Debe aceptar ambas declaraciones para continuar.'); return false; }
  document.getElementById('btnEnviar').disabled = true;
  document.getElementById('btnEnviar').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';
  return true;
}

// ── Summary ─────────────────────────────────────
function generarResumen() {
  const get = n => {
    const el = document.querySelector('[name="'+n+'"]');
    return el ? el.value : '—';
  };
  const getFile = n => {
    const el = document.getElementById('file-'+n);
    return (el && el.files.length) ? '✅ ' + el.files[0].name : '⚠️ No subido';
  };
  const sexo = document.querySelector('[name="sexo"]:checked');
  const items = [
    ['Estudiante', get('nombres') + ' ' + get('apellidos')],
    ['Nacimiento', get('fecha_nacimiento')],
    ['Sexo', sexo ? (sexo.value==='M'?'Masculino':'Femenino') : '—'],
    ['Dirección', get('direccion')],
    ['Tutor', get('tutor_nombres') + ' ' + get('tutor_apellidos')],
    ['Teléfono tutor', get('tutor_telefono')],
    ['Email tutor', get('tutor_email')],
    ['Foto', getFile('foto')],
    ['Acta de nacimiento', getFile('acta_nacimiento')],
    ['Cédula tutor', getFile('cedula_tutor')],
    ['Certificado médico', getFile('cert_medico')],
    ['Tarjeta vacuna', getFile('tarjeta_vacuna')],
  ];
  const viene = document.getElementById('vieneDeOtro');
  if (viene && viene.checked) {
    items.push(['Notas anteriores', getFile('notas_anteriores')]);
    items.push(['Carta de saldo', getFile('carta_saldo')]);
  }
  const sigerd = document.getElementById('file-sigerd');
  if (sigerd && sigerd.files.length) items.push(['SIGERD', getFile('sigerd')]);

  document.getElementById('resumen-datos').innerHTML = items.map(([k,v]) =>
    `<div class="col-md-6">
      <div class="d-flex justify-content-between p-2 border-bottom">
        <span class="text-muted">${k}</span>
        <span class="fw-semibold text-end ms-3" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${v}</span>
      </div>
    </div>`
  ).join('');
}

// Init: if there were validation errors, jump to first step with error
<?php if (!empty($errors)): ?>
const camposP1 = ['nombres','apellidos','fecha_nacimiento','sexo','direccion',
                  'alergias','condiciones_medicas'];
const camposP2 = ['tutor_nombres','tutor_apellidos','tutor_telefono','tutor_email'];
const camposP3 = ['foto','acta_nacimiento','cedula_tutor','cert_medico','tarjeta_vacuna',
                  'notas_anteriores','carta_saldo'];
const errKeys  = <?= json_encode(array_keys($errors)) ?>;

if (errKeys.some(k => camposP3.includes(k))) {
  pasoActual = 1; irPaso(3);
} else if (errKeys.some(k => camposP2.includes(k))) {
  pasoActual = 1; irPaso(2);
}
<?php endif; ?>
</script>
</body>
</html>
