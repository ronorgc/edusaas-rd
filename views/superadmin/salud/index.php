<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<!-- KPIs de alertas -->
<div class="row g-3 mb-4">

    <?php
    $kpis = [
        [
            'titulo'  => 'Versión PHP',
            'valor'   => $phpVersion,
            'icono'   => 'bi-filetype-php',
            'color'   => version_compare($phpVersion, '8.0', '>=') ? '#10b981' : '#f59e0b',
            'subtitulo' => version_compare($phpVersion, '8.0', '>=') ? 'Soportado' : 'Actualizar recomendado',
        ],
        [
            'titulo'  => 'MySQL',
            'valor'   => $mysqlVersion,
            'icono'   => 'bi-database',
            'color'   => '#3b82f6',
            'subtitulo' => number_format((float)$dbSize, 2) . ' MB en uso',
        ],
        [
            'titulo'  => 'Emails fallidos',
            'valor'   => $emailsError,
            'icono'   => 'bi-envelope-x',
            'color'   => $emailsError > 0 ? '#ef4444' : '#10b981',
            'subtitulo' => 'Últimos 7 días',
        ],
        [
            'titulo'  => 'Susc. vencidas sin procesar',
            'valor'   => $suscVencidas,
            'icono'   => 'bi-calendar-x',
            'color'   => $suscVencidas > 0 ? '#f59e0b' : '#10b981',
            'subtitulo' => $suscVencidas > 0 ? 'Revisar en cobros' : 'Todo al día',
        ],
        [
            'titulo'  => 'Preregistros pendientes',
            'valor'   => $preregistrosPendientes,
            'icono'   => 'bi-building-add',
            'color'   => $preregistrosPendientes > 0 ? '#8b5cf6' : '#10b981',
            'subtitulo' => $preregistrosPendientes > 0 ? 'Sin revisar' : 'Ninguno pendiente',
        ],
        [
            'titulo'  => 'Usuarios bloqueados',
            'valor'   => count($bloqueados),
            'icono'   => 'bi-lock-fill',
            'color'   => count($bloqueados) > 0 ? '#ef4444' : '#10b981',
            'subtitulo' => count($bloqueados) > 0 ? 'Ver abajo' : 'Ninguno bloqueado',
        ],
    ];
    foreach ($kpis as $kpi):
    ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card h-100 text-center py-3 px-2">
            <i class="bi <?= $kpi['icono'] ?> fs-3 mb-2" style="color:<?= $kpi['color'] ?>"></i>
            <div class="fw-bold" style="font-size:1.3rem;color:<?= $kpi['color'] ?>"><?= htmlspecialchars($kpi['valor']) ?></div>
            <div class="fw-semibold small"><?= $kpi['titulo'] ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= $kpi['subtitulo'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-lg-6">

        <!-- Recordatorio vencimiento -->
        <div class="card mb-4">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bell-fill me-2 text-warning"></i>Recordatorio de vencimiento</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Envía emails automáticos a colegios que vencen en 7, 3 y 0 días.
                    No reenvía si ya fue notificado hoy.
                </p>

                <!-- Últimas ejecuciones -->
                <?php if (!empty($cronLogs)): ?>
                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-2">Últimas ejecuciones:</div>
                    <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:.8rem">
                        <thead class="table-light">
                            <tr><th>Fecha</th><th>Resultado</th><th>Enviados</th><th>Errores</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_slice($cronLogs, 0, 5) as $log): ?>
                        <tr>
                            <td><?= date('d/m H:i', strtotime($log['ejecutado_en'])) ?></td>
                            <td>
                                <?php if ($log['resultado'] === 'ok'): ?>
                                <span class="badge bg-success">✅ OK</span>
                                <?php elseif ($log['resultado'] === 'error'): ?>
                                <span class="badge bg-danger">❌ Error</span>
                                <?php else: ?>
                                <span class="badge bg-light text-muted">— Sin trabajo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success fw-semibold"><?= $log['enviados'] ?></td>
                            <td class="text-danger"><?= $log['errores'] ?: '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-muted small text-center py-2 mb-3">
                    <i class="bi bi-clock-history me-1"></i>Sin ejecuciones previas registradas
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= $appUrl ?>/superadmin/cron/avisos-vencimiento">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn btn-warning w-100"
                            onclick="return confirm('¿Ejecutar el envío de avisos de vencimiento ahora?')">
                        <i class="bi bi-send me-2"></i>Ejecutar ahora
                    </button>
                </form>

                <div class="alert alert-light mt-3 small mb-0">
                    <strong>Para automatizar</strong> (cron job en Linux/cPanel):
                    <code class="d-block mt-1 p-2 bg-dark text-white rounded" style="font-size:.72rem;word-break:break-all">
                        0 8 * * * curl -s "<?= $appUrl ?>/superadmin/cron/avisos-vencimiento?token=TU_CRON_SECRET"
                    </code>
                    Define <code>CRON_SECRET</code> en tu <code>config/constants.php</code>.
                </div>
            </div>
        </div>

        <!-- Usuarios bloqueados -->
        <?php if (!empty($bloqueados)): ?>
        <div class="card mb-4 border-danger">
            <div class="card-header fw-semibold text-danger bg-danger bg-opacity-10">
                <i class="bi bi-lock-fill me-2"></i>Usuarios bloqueados ahora
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.85rem">
                    <thead class="table-light">
                        <tr><th>Usuario</th><th>Bloqueado hasta</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bloqueados as $b): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($b['username']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($b['bloqueado_hasta'])) ?></td>
                        <td>
                            <form method="POST" action="<?= $appUrl ?>/superadmin/desbloquear-usuario" class="d-inline">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($b['username']) ?>">
                                <button type="submit" class="btn btn-xs btn-outline-danger"
                                        style="font-size:.72rem;padding:1px 8px"
                                        onclick="return confirm('¿Desbloquear a <?= htmlspecialchars($b['username']) ?>?')">
                                    Desbloquear
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actividad reciente -->
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-journal-text me-2 text-primary"></i>Actividad reciente
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.82rem">
                    <tbody>
                    <?php foreach ($actividadReciente as $a): ?>
                    <tr>
                        <td class="text-muted ps-3" style="white-space:nowrap;width:80px">
                            <?= date('d/m H:i', strtotime($a['created_at'])) ?>
                        </td>
                        <td><?= htmlspecialchars($a['descripcion']) ?></td>
                        <td class="text-muted pe-3" style="white-space:nowrap">
                            <?= htmlspecialchars($a['usuario_nombre'] ?? '—') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($actividadReciente)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Sin actividad reciente</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Columna derecha -->
    <div class="col-lg-6">

        <!-- Exportar instituciones -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-download me-2 text-success"></i>Exportar instituciones
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Exporta la lista completa de instituciones con plan, suscripción, estudiantes y datos de contacto.
                </p>
                <a href="<?= $appUrl ?>/superadmin/instituciones/exportar"
                   class="btn btn-success w-100">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                    Descargar CSV (Excel)
                </a>
            </div>
        </div>

        <!-- Tablas de la BD -->
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-database me-2 text-primary"></i>Base de datos</span>
                <span class="text-muted small fw-normal"><?= number_format((float)$dbSize, 2) ?> MB total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:380px;overflow-y:auto">
                <table class="table table-sm mb-0" style="font-size:.8rem">
                    <thead class="table-light sticky-top">
                        <tr><th class="ps-3">Tabla</th><th class="text-end">Filas</th><th class="text-end pe-3">Tamaño</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tablas as $t): ?>
                    <tr>
                        <td class="ps-3 fw-semibold"><?= htmlspecialchars($t['nombre']) ?></td>
                        <td class="text-end text-muted"><?= number_format((int)$t['filas']) ?></td>
                        <td class="text-end pe-3 text-muted">
                            <?= $t['kb'] >= 1024
                                ? number_format($t['kb'] / 1024, 1) . ' MB'
                                : number_format($t['kb'], 1) . ' KB' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>
</div>