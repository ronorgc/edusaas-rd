<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>
<?php
$tipoLabels = [
    'bienvenida'        => ['label'=>'Bienvenida',        'color'=>'#10b981'],
    'plan_renovado'     => ['label'=>'Renovación',        'color'=>'#3b82f6'],
    'vencimiento_7dias' => ['label'=>'Aviso 7 días',      'color'=>'#f59e0b'],
    'vencimiento_3dias' => ['label'=>'Aviso 3 días',      'color'=>'#f97316'],
    'vencimiento_hoy'   => ['label'=>'Vence hoy',         'color'=>'#ef4444'],
    'plan_vencido'      => ['label'=>'Plan vencido',      'color'=>'#dc2626'],
    'suspension'        => ['label'=>'Suspensión',        'color'=>'#6b7280'],
    'personalizado'     => ['label'=>'Personalizado',     'color'=>'#6366f1'],
    'trial_expirando'   => ['label'=>'Trial expirando',   'color'=>'#8b5cf6'],
    'trial_expirado'    => ['label'=>'Trial expirado',    'color'=>'#7c3aed'],
];
?>

<!-- Filtros -->
<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipoLabels as $k => $t): ?>
                    <option value="<?= $k ?>" <?= ($filtros['tipo']??'')===$k?'selected':'' ?>>
                        <?= $t['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="enviado"  <?= ($filtros['estado']??'')==='enviado' ?'selected':'' ?>>✅ Enviado</option>
                    <option value="error"    <?= ($filtros['estado']??'')==='error'   ?'selected':'' ?>>❌ Error</option>
                    <option value="pendiente"<?= ($filtros['estado']??'')==='pendiente'?'selected':'' ?>>⏳ Pendiente</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Buscar institución / email</label>
                <input type="text" name="buscar" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['buscar']??'') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Desde</label>
                <input type="date" name="desde" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['desde']??'') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="<?= $appUrl ?>/superadmin/emails" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </div>
</form>

<!-- Stats rápidas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4 text-primary"><?= $stats['total'] ?></div>
            <div class="text-muted small">Últimos 30 días</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4 text-success"><?= $stats['enviados'] ?></div>
            <div class="text-muted small">Enviados OK</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4 text-danger"><?= $stats['errores'] ?></div>
            <div class="text-muted small">Con error</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4">
                <?= $stats['total'] > 0
                    ? round(100 * $stats['enviados'] / $stats['total']) . '%'
                    : '—' ?>
            </div>
            <div class="text-muted small">Tasa de entrega</div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            <i class="bi bi-envelope-open-text me-2 text-primary"></i>
            <?= count($historial) ?> correo<?= count($historial)!=1?'s':'' ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:.88rem">
            <thead class="table-light">
                <tr>
                    <th style="width:130px">Fecha</th>
                    <th>Institución</th>
                    <th>Destinatario</th>
                    <th>Tipo</th>
                    <th style="width:80px">Estado</th>
                    <th>Error</th>
                    <th style="width:110px">Enviado por</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($historial)): ?>
            <tr><td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>
                No hay correos con estos filtros
            </td></tr>
            <?php else: ?>
            <?php foreach ($historial as $e):
                $tl = $tipoLabels[$e['tipo']] ?? ['label'=>$e['tipo'],'color'=>'#94a3b8'];
            ?>
            <tr class="<?= $e['estado']==='error' ? 'table-danger' : '' ?>">
                <td class="text-muted" style="white-space:nowrap">
                    <?= date('d/m/Y', strtotime($e['created_at'])) ?><br>
                    <span style="font-size:.76rem"><?= date('H:i', strtotime($e['created_at'])) ?></span>
                </td>
                <td class="fw-semibold"><?= htmlspecialchars($e['institucion_nombre']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($e['destinatario']) ?></td>
                <td>
                    <span class="badge" style="background:<?= $tl['color'] ?>22;color:<?= $tl['color'] ?>;border:1px solid <?= $tl['color'] ?>33">
                        <?= $tl['label'] ?>
                    </span>
                </td>
                <td>
                    <?php if ($e['estado']==='enviado'): ?>
                        <span class="badge bg-success">✅ OK</span>
                    <?php elseif ($e['estado']==='error'): ?>
                        <span class="badge bg-danger">❌ Error</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">⏳</span>
                    <?php endif; ?>
                </td>
                <td class="text-danger small" style="font-size:.76rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= htmlspecialchars($e['error_detalle'] ?? '') ?>
                </td>
                <td class="text-muted small">
                    <?= $e['enviado_por']
                        ? htmlspecialchars($e['enviado_nombres'] . ' ' . $e['enviado_apellidos'])
                        : '<span class="text-muted">Automático</span>' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>