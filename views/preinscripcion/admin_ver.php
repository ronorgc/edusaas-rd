<?php
$appUrl = (require __DIR__ . '/../../../config/app.php')['url'];
$p = $pre;
$estadoBadge = [
    'pendiente'    => ['⏳ Pendiente',   '#fef9c3', '#92400e'],
    'en_revision'  => ['🔍 En revisión', '#dbeafe', '#1e40af'],
    'aprobada'     => ['✅ Aprobada',    '#dcfce7', '#166534'],
    'rechazada'    => ['❌ Rechazada',   '#fee2e2', '#991b1b'],
    'convertida'   => ['🎓 Convertida',  '#ede9fe', '#5b21b6'],
];
$bd = $estadoBadge[$p['estado']] ?? ['—','#f1f5f9','#64748b'];
$parentMap = ['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor/a',
              'abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío/a','otro'=>'Otro'];

function docLink(string $appUrl, ?string $ruta, string $label): string {
    if (!$ruta) return '<span class="text-muted small">No subido</span>';
    $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    $icon = $ext === 'pdf' ? 'bi-file-pdf text-danger' : 'bi-file-image text-primary';
    return "<a href='{$appUrl}{$ruta}' target='_blank' class='btn btn-sm btn-outline-secondary py-1'>
              <i class='bi {$icon} me-1'></i>{$label}
            </a>";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= $appUrl ?>/admin/preinscripciones">Preinscripciones</a></li>
    <li class="breadcrumb-item active"><?= htmlspecialchars($p['codigo_solicitud']) ?></li>
  </ol></nav>
  <span class="badge fs-6" style="background:<?= $bd[1] ?>;color:<?= $bd[2] ?>"><?= $bd[0] ?></span>
</div>

<div class="row g-4">
  <!-- Columna izquierda -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header fw-semibold small"><i class="bi bi-person-fill me-2 text-primary"></i>Estudiante</div>
      <ul class="list-group list-group-flush small">
        <?php
        $filas = [
          ['Nombre completo', $p['nombres'].' '.$p['apellidos']],
          ['Nacimiento', date('d/m/Y', strtotime($p['fecha_nacimiento'])).' · '.
            (int)date_diff(date_create($p['fecha_nacimiento']),date_create())->y.' años'],
          ['Sexo',     $p['sexo']==='M'?'♂ Masculino':'♀ Femenino'],
          ['Cédula',   $p['cedula']],
          ['NIE',      $p['nie']],
          ['Nac. en',  $p['lugar_nacimiento']],
          ['Nac.',     $p['nacionalidad']],
          ['Dirección',$p['direccion'].($p['municipio']?', '.$p['municipio']:'').($p['provincia']?', '.$p['provincia']:'')],
          ['Sangre',   $p['tipo_sangre']],
          ['Alergias', $p['alergias']],
          ['Cond. médicas', $p['condiciones_medicas']],
        ];
        foreach ($filas as [$k,$v]): if (!$v) continue; ?>
        <li class="list-group-item d-flex justify-content-between py-2 px-3">
          <span class="text-muted"><?= $k ?></span>
          <span class="fw-medium text-end ms-2" style="max-width:200px"><?= htmlspecialchars($v) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card mb-3">
      <div class="card-header fw-semibold small"><i class="bi bi-people me-2 text-primary"></i>Tutor / Padre</div>
      <ul class="list-group list-group-flush small">
        <?php
        $filasTutor = [
          ['Nombre', $p['tutor_nombres'].' '.$p['tutor_apellidos']],
          ['Parentesco', $parentMap[$p['tutor_parentesco']] ?? $p['tutor_parentesco']],
          ['Cédula', $p['tutor_cedula']],
          ['Teléfono', $p['tutor_telefono']],
          ['Celular', $p['tutor_celular']],
          ['Email', $p['tutor_email']],
          ['Ocupación', $p['tutor_ocupacion']],
        ];
        foreach ($filasTutor as [$k,$v]): if (!$v) continue; ?>
        <li class="list-group-item d-flex justify-content-between py-2 px-3">
          <span class="text-muted"><?= $k ?></span>
          <span class="fw-medium text-end ms-2"><?= htmlspecialchars($v) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if ($p['viene_de_otro_colegio']): ?>
    <div class="card mb-3">
      <div class="card-header fw-semibold small"><i class="bi bi-building me-2" style="color:#d97706"></i>Colegio anterior</div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item py-2 px-3 d-flex justify-content-between">
          <span class="text-muted">Colegio</span>
          <span class="fw-medium"><?= htmlspecialchars($p['colegio_anterior'] ?? '—') ?></span>
        </li>
        <li class="list-group-item py-2 px-3 d-flex justify-content-between">
          <span class="text-muted">Último grado</span>
          <span class="fw-medium"><?= htmlspecialchars($p['ultimo_grado_aprobado'] ?? '—') ?></span>
        </li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <!-- Columna derecha -->
  <div class="col-lg-8">
    <!-- Documentos -->
    <div class="card mb-3">
      <div class="card-header fw-semibold"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Documentos subidos</div>
      <div class="card-body">
        <div class="row g-3">
          <?php
          $docs = [
            ['foto',             'Foto del estudiante',    true],
            ['acta_nacimiento',  'Acta de nacimiento',     false],
            ['cedula_tutor',     'Cédula del tutor',       false],
            ['cert_medico',      'Certificado médico',     false],
            ['tarjeta_vacuna',   'Tarjeta de vacunación',  false],
            ['notas_anteriores', 'Notas anteriores',       false],
            ['carta_saldo',      'Carta de saldo',         false],
            ['sigerd',           'SIGERD',                 false],
          ];
          foreach ($docs as [$campo, $label, $esImagen]):
            $ruta = $p['doc_' . $campo] ?? null;
          ?>
          <div class="col-md-6">
            <div class="p-3 rounded border d-flex align-items-start gap-3 <?= $ruta?'border-success bg-white':'border-danger bg-danger bg-opacity-5' ?>">
              <div style="font-size:1.5rem;flex-shrink:0">
                <?= $ruta ? '✅' : '❌' ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= $label ?></div>
                <?php if ($ruta): ?>
                  <?php if ($esImagen): ?>
                  <img src="<?= $appUrl.htmlspecialchars($ruta) ?>" class="rounded mt-1"
                       style="max-height:80px;max-width:100%;object-fit:cover">
                  <?php endif; ?>
                  <div class="mt-1">
                    <?= docLink($appUrl, $ruta, 'Ver archivo') ?>
                  </div>
                <?php else: ?>
                  <div class="text-muted small">No subido</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Panel de revisión -->
    <?php if ($p['estado'] !== 'convertida'): ?>
    <div class="card mb-3">
      <div class="card-header fw-semibold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Revisión</div>
      <div class="card-body">
        <form method="POST" action="<?= $appUrl ?>/admin/preinscripciones/<?= $p['id'] ?>/actualizar">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Estado</label>
            <select name="estado" class="form-select">
              <?php foreach(['pendiente'=>'⏳ Pendiente','en_revision'=>'🔍 En revisión',
                              'aprobada'=>'✅ Aprobada','rechazada'=>'❌ Rechazada'] as $e=>$l): ?>
              <option value="<?=$e?>" <?= $p['estado']===$e?'selected':'' ?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Notas / Observaciones</label>
            <textarea name="notas_admin" class="form-control" rows="3"
                      placeholder="Razón de aprobación, rechazo, documentos faltantes…"><?= htmlspecialchars($p['notas_admin'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Guardar revisión
          </button>
        </form>
      </div>
    </div>

    <!-- Convertir a estudiante -->
    <?php if ($p['estado'] === 'aprobada'): ?>
    <div class="card border-success">
      <div class="card-header fw-semibold text-success">
        <i class="bi bi-person-plus-fill me-2"></i>Convertir a Estudiante
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Crea automáticamente la ficha del estudiante con todos sus datos y el tutor registrado.
          El código de estudiante se genera automáticamente (EST-00001).
        </p>
        <form method="POST" action="<?= $appUrl ?>/admin/preinscripciones/<?= $p['id'] ?>/convertir"
              onsubmit="return confirm('¿Convertir esta preinscripción en estudiante activo?')">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-person-check-fill me-1"></i>Crear estudiante
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle-fill me-2"></i>
      Esta preinscripción ya fue convertida a estudiante.
      <?php if ($p['estudiante_id']): ?>
      <a href="<?= $appUrl ?>/estudiantes/<?= $p['estudiante_id'] ?>" class="alert-link ms-2">
        Ver ficha del estudiante →
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
