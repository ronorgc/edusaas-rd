<?php
$appUrl = (require __DIR__ . '/../../../../config/app.php')['url'];
$estadoLabels = [
    'pendiente'  => ['label' => 'Pendiente',   'badge' => 'bg-warning text-dark'],
    'contactado' => ['label' => 'Contactado',  'badge' => 'bg-info text-dark'],
    'aprobado'   => ['label' => 'Aprobado',    'badge' => 'bg-success'],
    'rechazado'  => ['label' => 'Rechazado',   'badge' => 'bg-secondary'],
];
?>

<!-- Filtros por estado -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="<?= $appUrl ?>/superadmin/preregistros"
       class="btn btn-sm <?= $estadoActual === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        Todos <span class="badge bg-secondary ms-1"><?= array_sum($contadores) ?></span>
    </a>
    <?php foreach ($estadoLabels as $key => $meta): ?>
    <a href="<?= $appUrl ?>/superadmin/preregistros?estado=<?= $key ?>"
       class="btn btn-sm <?= $estadoActual === $key ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= $meta['label'] ?>
        <?php if ($n = ($contadores[$key] ?? 0)): ?>
        <span class="badge ms-1 <?= $meta['badge'] ?>"><?= $n ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($solicitudes)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-inbox fs-1 text-muted opacity-25 d-block mb-3"></i>
        <h5 class="fw-bold">Sin solicitudes</h5>
        <p class="text-muted small">
            Aquí aparecerán los colegios que completen el formulario de registro en
            <code>/registro</code>.<br>
            Asegúrate de tener activado el <strong>Registro Público</strong> en Configuración.
        </p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Colegio</th>
                    <th>Contacto</th>
                    <th>Plan interés</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $s):
                $lbl = $estadoLabels[$s['estado']] ?? ['label' => $s['estado'], 'badge' => 'bg-secondary'];
            ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($s['nombre']) ?></div>
                    <div class="text-muted small">
                        <?= htmlspecialchars($s['municipio'] ?? '') ?>
                        <?= $s['provincia'] ? '· ' . htmlspecialchars($s['provincia']) : '' ?>
                    </div>
                </td>
                <td>
                    <div><?= htmlspecialchars($s['nombre_director'] ?? '—') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($s['email']) ?></div>
                </td>
                <td>
                    <?php if ($s['plan_nombre']): ?>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['plan_nombre']) ?></span>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                <td><span class="badge <?= $lbl['badge'] ?>"><?= $lbl['label'] ?></span></td>
                <td>
                    <a href="<?= $appUrl ?>/superadmin/preregistros/<?= $s['id'] ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>