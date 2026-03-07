<?php
$appUrl    = (require __DIR__ . '/../../../config/app.php')['url'];
$conteos   = $conteos ?? [];
$totalTodos = array_sum($conteos);
$estadoBadge = [
    'pendiente'    => ['⏳ Pendiente',   '#fef9c3', '#92400e'],
    'en_revision'  => ['🔍 En revisión', '#dbeafe', '#1e40af'],
    'aprobada'     => ['✅ Aprobada',    '#dcfce7', '#166534'],
    'rechazada'    => ['❌ Rechazada',   '#fee2e2', '#991b1b'],
    'convertida'   => ['🎓 Convertida',  '#ede9fe', '#5b21b6'],
];
?>
<!-- Tabs de estado -->
<div class="d-flex gap-2 flex-wrap mb-4">
  <?php
  $tabs = ['pendiente'=>'⏳ Pendiente','en_revision'=>'🔍 En revisión',
            'aprobada'=>'✅ Aprobadas','rechazada'=>'❌ Rechazadas',
            'convertida'=>'🎓 Convertidas','todas'=>'📋 Todas'];
  foreach ($tabs as $est => $label):
    $cnt = $est === 'todas' ? $totalTodos : ($conteos[$est] ?? 0);
    $activo = $estadoActivo === $est;
  ?>
  <a href="?estado=<?= $est ?>"
     class="btn btn-sm <?= $activo ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <?= $label ?>
    <?php if ($cnt): ?><span class="badge <?= $activo?'bg-white text-primary':'bg-primary text-white' ?> ms-1"><?= $cnt ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Link público -->
<?php if ($inst && $inst['subdomain']): ?>
<div class="alert alert-info py-2 small mb-4 d-flex align-items-center gap-3">
  <i class="bi bi-link-45deg fs-5 flex-shrink-0"></i>
  <div>
    <strong>Enlace del formulario para padres:</strong>
    <a href="<?= $appUrl ?>/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>" target="_blank" class="fw-semibold">
      <?= $appUrl ?>/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>
    </a>
    &nbsp;<button type="button" class="btn btn-sm btn-outline-secondary py-0"
       onclick="navigator.clipboard.writeText('<?= $appUrl ?>/preinscripcion/<?= htmlspecialchars($inst['subdomain']) ?>');this.textContent='✅ Copiado';setTimeout(()=>this.textContent='📋 Copiar',2000)">
      📋 Copiar
    </button>
    — Compártalo con los padres por WhatsApp o email.
  </div>
</div>
<?php endif; ?>

<!-- Tabla -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-inbox-fill me-2 text-primary"></i>
      Preinscripciones
      <span class="badge bg-primary ms-1"><?= count($preinscripciones) ?></span>
    </span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($preinscripciones)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox d-block fs-1 mb-2 opacity-25"></i>
      No hay solicitudes en este estado.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>Código</th><th>Estudiante</th><th>Grado</th><th>Tutor</th><th>Fecha</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($preinscripciones as $p):
          $bd = $estadoBadge[$p['estado']] ?? ['—','#f1f5f9','#64748b'];
        ?>
        <tr>
          <td><code class="small"><?= htmlspecialchars($p['codigo_solicitud']) ?></code></td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($p['apellidos'].', '.$p['nombres']) ?></div>
            <div class="text-muted small"><?= date('d/m/Y', strtotime($p['fecha_nacimiento'])) ?></div>
          </td>
          <td class="small"><?= htmlspecialchars($p['grado_nombre_real'] ?? $p['grado_nombre'] ?? '—') ?></td>
          <td class="small">
            <div><?= htmlspecialchars($p['tutor_nombres'].' '.$p['tutor_apellidos']) ?></div>
            <div class="text-muted"><?= htmlspecialchars($p['tutor_telefono']) ?></div>
          </td>
          <td class="small"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
          <td>
            <span class="badge" style="background:<?= $bd[1] ?>;color:<?= $bd[2] ?>;font-size:.78rem;padding:.3rem .6rem;border-radius:20px">
              <?= $bd[0] ?>
            </span>
          </td>
          <td>
            <a href="<?= $appUrl ?>/admin/preinscripciones/<?= $p['id'] ?>"
               class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye me-1"></i>Revisar
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