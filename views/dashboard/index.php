<?php
// =====================================================
// EduSaaS RD - Dashboard (Vista)
// Las variables vienen del DashboardController
// =====================================================
?>
<?php include __DIR__ . '/../partials/plan_uso.php'; ?>

<div class="row g-3 mb-4">
    <!-- Stat Cards -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;">
                <i class="bi bi-people-fill text-primary"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['estudiantes'] ?? 0) ?></div>
                <div class="stat-label">Estudiantes activos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;">
                <i class="bi bi-person-workspace" style="color:#16a34a;"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['profesores'] ?? 0) ?></div>
                <div class="stat-label">Profesores activos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef9c3;">
                <i class="bi bi-cash-stack" style="color:#ca8a04;"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['cuotas_pendientes'] ?? 0) ?></div>
                <div class="stat-label">Cuotas pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;">
                <i class="bi bi-calendar-x-fill" style="color:#dc2626;"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['ausentes_hoy'] ?? 0) ?></div>
                <div class="stat-label">Ausentes hoy</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Actividad Reciente -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Actividad Reciente</span>
            </div>
            <div class="card-body p-0">
                <p class="text-muted text-center py-4 small">
                    Los registros recientes aparecerán aquí.
                </p>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning-fill me-2 text-warning"></i>Accesos Rápidos
            </div>
            <div class="card-body">
                <?php
                $appUrl = (require __DIR__ . '/../../config/app.php')['url'];
                ?>
                <div class="d-grid gap-2">
                    <a href="<?= $appUrl ?>/estudiantes/crear" class="btn btn-outline-primary btn-sm text-start">
                        <i class="bi bi-person-plus me-2"></i>Registrar estudiante
                    </a>
                    <a href="<?= $appUrl ?>/matriculas/crear" class="btn btn-outline-success btn-sm text-start">
                        <i class="bi bi-journal-plus me-2"></i>Nueva matrícula
                    </a>
                    <a href="<?= $appUrl ?>/asistencia" class="btn btn-outline-info btn-sm text-start">
                        <i class="bi bi-calendar-check me-2"></i>Pasar asistencia
                    </a>
                    <a href="<?= $appUrl ?>/calificaciones" class="btn btn-outline-warning btn-sm text-start">
                        <i class="bi bi-pencil-square me-2"></i>Registrar calificaciones
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>