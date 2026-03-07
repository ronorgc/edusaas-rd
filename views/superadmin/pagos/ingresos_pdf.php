<?php
// Vista de impresión PDF — no usa el layout main.php
// Se abre en una nueva pestaña y se imprime con Ctrl+P

try {
    $_cfg_empresa  = ConfigModel::get('empresa_nombre',       'EduSaaS RD');
    $_cfg_razon    = ConfigModel::get('empresa_razon_social', 'EduSaaS RD SRL');
    $_cfg_rnc      = ConfigModel::get('empresa_rnc',          '');
    $_cfg_email_c  = ConfigModel::get('empresa_email',        'soporte@edusaas.do');
    $_cfg_web      = ConfigModel::get('empresa_sitio_web',    'https://edusaas.do');
    $_cfg_moneda   = ConfigModel::get('factura_moneda',       'RD$');
    $_cfg_itbis    = (float)ConfigModel::get('factura_itbis', '0');
} catch (Exception $_e) {
    $_cfg_empresa  = 'EduSaaS RD';
    $_cfg_razon    = 'EduSaaS RD SRL';
    $_cfg_rnc      = '';
    $_cfg_email_c  = 'soporte@edusaas.do';
    $_cfg_web      = 'https://edusaas.do';
    $_cfg_moneda   = 'RD$';
    $_cfg_itbis    = 0;
}

$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Agrupar por institución para resumen
$porInst = [];
foreach ($pagos as $p) {
    $n = $p['institucion_nombre'];
    if (!isset($porInst[$n])) $porInst[$n] = ['plan' => $p['plan_nombre'], 'total' => 0, 'pagos' => 0];
    $porInst[$n]['total'] += $p['monto'];
    $porInst[$n]['pagos']++;
}
arsort($porInst);

// Agrupar por método de pago
$porMetodo = [];
foreach ($pagos as $p) {
    $m = ucfirst($p['metodo_pago']);
    $porMetodo[$m] = ($porMetodo[$m] ?? 0) + $p['monto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Ingresos <?= htmlspecialchars($periodoLabel) ?> — <?= htmlspecialchars($_cfg_empresa) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f8fafc;
            padding: 20px;
        }
        .page {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 32px 36px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            border-radius: 8px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a56db;
            margin-bottom: 24px;
        }
        .header-empresa h1 { font-size: 22px; font-weight: 800; color: #1a56db; }
        .header-empresa p  { font-size: 10px; color: #64748b; margin-top: 2px; }
        .header-reporte    { text-align: right; }
        .header-reporte h2 { font-size: 14px; font-weight: 700; color: #1e293b; }
        .header-reporte p  { font-size: 10px; color: #64748b; }

        /* Resumen de KPIs */
        .kpis {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .kpi {
            background: #f0f6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .kpi-valor { font-size: 18px; font-weight: 800; color: #1a56db; }
        .kpi-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }

        /* Sección */
        .seccion-titulo {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Tablas */
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th {
            background: #1a56db;
            color: #fff;
            padding: 7px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; }
        tr:hover td { background: #f8fafc; }
        .total-row td { font-weight: 700; background: #f0f6ff; border-top: 2px solid #1a56db; }
        .monto { text-align: right; font-weight: 600; color: #059669; font-family: monospace; }

        /* Resumen lateral */
        .dos-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }

        /* Footer */
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #94a3b8;
        }

        /* Imprimir */
        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; border-radius: 0; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Botón imprimir -->
<div class="no-print" style="max-width:900px;margin:0 auto 12px;text-align:right">
    <button onclick="window.print()"
            style="background:#1a56db;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">
        🖨️ Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px">
        ✕ Cerrar
    </button>
</div>

<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="header-empresa">
            <h1><?= htmlspecialchars($_cfg_empresa) ?></h1>
            <p><?= htmlspecialchars($_cfg_razon) ?><?= $_cfg_rnc ? ' · RNC ' . htmlspecialchars($_cfg_rnc) : '' ?></p>
            <p><?= htmlspecialchars($_cfg_email_c) ?> · <?= htmlspecialchars($_cfg_web) ?></p>
        </div>
        <div class="header-reporte">
            <h2>Reporte de Ingresos</h2>
            <p>Período: <strong><?= htmlspecialchars($periodoLabel) ?></strong></p>
            <p>Generado: <?= date('d/m/Y H:i') ?></p>
            <p><?= count($pagos) ?> pago<?= count($pagos) != 1 ? 's' : '' ?> registrados</p>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpis">
        <div class="kpi">
            <div class="kpi-valor"><?= $_cfg_moneda . number_format($totalGeneral, 0, '.', ',') ?></div>
            <div class="kpi-label">Total ingresado</div>
        </div>
        <div class="kpi">
            <div class="kpi-valor"><?= count($pagos) ?></div>
            <div class="kpi-label">Pagos</div>
        </div>
        <div class="kpi">
            <div class="kpi-valor"><?= count($porInst) ?></div>
            <div class="kpi-label">Instituciones</div>
        </div>
        <div class="kpi">
            <div class="kpi-valor">
                <?= count($pagos) > 0 ? $_cfg_moneda . number_format($totalGeneral / count($pagos), 0, '.', ',') : '—' ?>
            </div>
            <div class="kpi-label">Ticket promedio</div>
        </div>
    </div>

    <!-- Resumen por institución y por método -->
    <div class="dos-cols">
        <div>
            <div class="seccion-titulo">Por institución</div>
            <table>
                <thead><tr><th>Institución</th><th>Plan</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>
                <?php foreach ($porInst as $nombre => $dat): ?>
                <tr>
                    <td><?= htmlspecialchars($nombre) ?></td>
                    <td><?= htmlspecialchars($dat['plan']) ?></td>
                    <td class="monto"><?= $_cfg_moneda . number_format($dat['total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div>
            <div class="seccion-titulo">Por método de pago</div>
            <table>
                <thead><tr><th>Método</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>
                <?php foreach ($porMetodo as $metodo => $total): ?>
                <tr>
                    <td><?= htmlspecialchars($metodo) ?></td>
                    <td class="monto"><?= $_cfg_moneda . number_format($total, 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detalle completo -->
    <div class="seccion-titulo">Detalle de pagos</div>
    <table>
        <thead>
            <tr>
                <th>Factura</th>
                <th>Institución</th>
                <th>Plan</th>
                <th>Método</th>
                <th>Período</th>
                <th>Fecha</th>
                <th style="text-align:right">Monto</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($pagos)): ?>
        <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px">No hay pagos en este período</td></tr>
        <?php else: ?>
        <?php foreach ($pagos as $p): ?>
        <tr>
            <td style="font-family:monospace;font-size:9.5px"><?= htmlspecialchars($p['numero_factura']) ?></td>
            <td><?= htmlspecialchars($p['institucion_nombre']) ?></td>
            <td><?= htmlspecialchars($p['plan_nombre']) ?></td>
            <td><?= ucfirst(htmlspecialchars($p['metodo_pago'])) ?></td>
            <td style="font-size:9.5px;color:#64748b">
                <?= date('d/m/Y', strtotime($p['periodo_desde'])) ?> –
                <?= date('d/m/Y', strtotime($p['periodo_hasta'])) ?>
            </td>
            <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
            <td class="monto"><?= $_cfg_moneda . number_format($p['monto'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="6" style="text-align:right">TOTAL PERÍODO:</td>
            <td class="monto" style="font-size:13px"><?= $_cfg_moneda . number_format($totalGeneral, 2) ?></td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <span><?= htmlspecialchars($_cfg_empresa) ?> · <?= htmlspecialchars($_cfg_email_c) ?></span>
        <span>Reporte generado el <?= date('d/m/Y \a \l\a\s H:i') ?> · Sistema EduSaaS RD</span>
    </div>

</div>

<script>
// Auto-abrir diálogo de impresión
window.addEventListener('load', () => {
    setTimeout(() => window.print(), 500);
});
</script>
</body>
</html>