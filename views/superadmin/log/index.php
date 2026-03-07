<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>
<?php
$moduloLabels = [
    'instituciones' => ['label' => 'Instituciones', 'color' => '#3b82f6', 'icono' => 'bi-building'],
    'planes'        => ['label' => 'Planes',         'color' => '#8b5cf6', 'icono' => 'bi-box'],
    'cobros'        => ['label' => 'Cobros',          'color' => '#10b981', 'icono' => 'bi-cash-stack'],
    'usuarios'      => ['label' => 'Usuarios SA',     'color' => '#f59e0b', 'icono' => 'bi-people-fill'],
    'configuracion' => ['label' => 'Configuración',   'color' => '#6366f1', 'icono' => 'bi-gear-fill'],
    'preregistros'  => ['label' => 'Preregistros',    'color' => '#ec4899', 'icono' => 'bi-building-add'],
];
$accionIconos = [
    'crear'       => 'bi-plus-circle-fill text-success',
    'crear_inst'  => 'bi-plus-circle-fill text-success',
    'editar'      => 'bi-pencil-fill text-primary',
    'eliminar'    => 'bi-trash3-fill text-danger',
    'suspender'   => 'bi-pause-circle-fill text-warning',
    'reactivar'   => 'bi-play-circle-fill text-success',
    'cambiar_plan' => 'bi-arrow-left-right text-info',
    'pago'        => 'bi-cash-coin text-success',
    'smtp'        => 'bi-envelope-gear text-secondary',
    'aprobar'     => 'bi-check-circle-fill text-success',
    'rechazar'    => 'bi-x-circle-fill text-danger',
    'activar'     => 'bi-toggle-on text-success',
    'desactivar'  => 'bi-toggle-off text-secondary',
];
?>

<!-- Filtros -->
<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Módulo</label>
                <select name="modulo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($moduloLabels as $k => $m): ?>
                        <option value="<?= $k ?>" <?= ($filtros['modulo'] ?? '') === $k ? 'selected' : '' ?>>
                            <?= $m['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Buscar</label>
                <input type="text" name="buscar" class="form-control form-control-sm"
                    placeholder="Descripción..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Desde</label>
                <input type="date" name="desde" class="form-control form-control-sm"
                    value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Mostrar</label>
                <select name="limite" class="form-select form-select-sm">
                    <?php foreach ([50, 100, 200, 500] as $l): ?>
                        <option value="<?= $l ?>" <?= ($filtros['limite'] ?? 50) == $l ? 'selected' : '' ?>><?= $l ?> registros</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="<?= $appUrl ?>/superadmin/log" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </div>
</form>

<!-- Contadores por módulo (últimos 30 días) -->
<?php if (!empty($contadores)): ?>
    <div class="d-flex gap-2 flex-wrap mb-4">
        <?php foreach ($moduloLabels as $key => $m):
            $n = $contadores[$key] ?? 0;
            if (!$n) continue;
        ?>
            <a href="?modulo=<?= $key ?>"
                class="badge text-decoration-none d-flex align-items-center gap-1 px-3 py-2"
                style="background:<?= $m['color'] ?>22;color:<?= $m['color'] ?>;border:1px solid <?= $m['color'] ?>44;font-size:.8rem;border-radius:20px">
                <i class="bi <?= $m['icono'] ?>"></i>
                <?= $m['label'] ?>: <?= $n ?>
            </a>
        <?php endforeach; ?>
        <span class="text-muted small ms-2 align-self-center">Últimos 30 días</span>
    </div>
<?php endif; ?>

<!-- Tabla de log -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-journal-text me-2 text-primary"></i>
            <?= count($registros) ?> registro<?= count($registros) != 1 ? 's' : '' ?>
        </span>
        <span class="text-muted small">Ordenado por más reciente</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px">Fecha</th>
                        <th style="width:110px">Módulo</th>
                        <th style="width:36px"></th>
                        <th>Descripción</th>
                        <th style="width:140px">Usuario</th>
                        <th style="width:90px">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x d-block fs-2 mb-2 opacity-25"></i>
                                No hay registros con estos filtros
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r):
                            $mod  = $moduloLabels[$r['modulo']] ?? ['label' => $r['modulo'], 'color' => '#94a3b8', 'icono' => 'bi-circle'];
                            $icon = $accionIconos[$r['accion']] ?? 'bi-dot text-muted';
                            $det  = $r['detalle'] ? json_decode($r['detalle'], true) : null;
                        ?>
                            <tr>
                                <td class="text-muted" style="white-space:nowrap">
                                    <?= date('d/m/Y', strtotime($r['created_at'])) ?><br>
                                    <span style="font-size:.78rem"><?= date('H:i:s', strtotime($r['created_at'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge" style="background:<?= $mod['color'] ?>22;color:<?= $mod['color'] ?>;border:1px solid <?= $mod['color'] ?>33">
                                        <?= $mod['label'] ?>
                                    </span>
                                </td>
                                <td><i class="bi <?= $icon ?>" style="font-size:1rem"></i></td>
                                <td>
                                    <div><?= htmlspecialchars($r['descripcion']) ?></div>
                                    <?php if ($det): ?>
                                        <div class="text-muted" style="font-size:.76rem;margin-top:2px">
                                            <?php foreach ($det as $k => $v): ?>
                                                <span class="me-2"><span class="fw-semibold"><?= htmlspecialchars($k) ?>:</span> <?= htmlspecialchars((string)$v) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($r['usuario_nombre'] ?? '—') ?>
                                </td>
                                <td class="text-muted" style="font-size:.76rem;font-family:monospace">
                                    <?= htmlspecialchars($r['ip'] ?? '') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>