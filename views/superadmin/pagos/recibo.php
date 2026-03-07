<?php
// Datos de empresa desde configuración — con fallback si la tabla no existe aún
try {
    $_cfg_empresa  = ConfigModel::get('empresa_nombre',       'EduSaaS RD');
    $_cfg_razon    = ConfigModel::get('empresa_razon_social', 'EduSaaS RD SRL');
    $_cfg_rnc      = ConfigModel::get('empresa_rnc',          '');
    $_cfg_tel      = ConfigModel::get('empresa_telefono',     '');
    $_cfg_email_c  = ConfigModel::get('empresa_email',        'soporte@edusaas.do');
    $_cfg_dir      = ConfigModel::get('empresa_direccion',    'Santo Domingo, RD');
    $_cfg_web      = ConfigModel::get('empresa_sitio_web',    'https://edusaas.do');
    $_cfg_moneda   = ConfigModel::get('factura_moneda',       'RD$');
    $_cfg_nota_pie = ConfigModel::get('factura_nota_pie',     '');
    $_cfg_itbis    = (float)ConfigModel::get('factura_itbis', '0');
} catch (Exception $_e) {
    // Tabla no existe todavía — usar defaults
    $_cfg_empresa  = 'EduSaaS RD';
    $_cfg_razon    = 'EduSaaS RD SRL';
    $_cfg_rnc      = '';
    $_cfg_tel      = '';
    $_cfg_email_c  = 'soporte@edusaas.do';
    $_cfg_dir      = 'Santo Domingo, RD';
    $_cfg_web      = 'https://edusaas.do';
    $_cfg_moneda   = 'RD$';
    $_cfg_nota_pie = '';
    $_cfg_itbis    = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo <?= htmlspecialchars($pago['numero_factura']) ?> — <?= htmlspecialchars($_cfg_empresa) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 2rem;
        }

        .recibo {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0,0,0,.12);
        }

        /* Header */
        .recibo-header {
            background: linear-gradient(135deg, #1a56db, #1240a8);
            padding: 2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .recibo-brand h1 {
            font-family: 'DM Serif Display', serif;
            color: #fff;
            font-size: 1.8rem;
            margin-bottom: .1rem;
        }
        .recibo-brand h1 span { color: #f59e0b; }
        .recibo-brand p { color: rgba(255,255,255,.65); font-size: .8rem; }
        .recibo-num {
            text-align: right;
        }
        .recibo-num .label { color: rgba(255,255,255,.6); font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; }
        .recibo-num .numero { color: #fff; font-size: 1.1rem; font-weight: 700; margin-top: .2rem; }
        .recibo-num .fecha { color: rgba(255,255,255,.7); font-size: .8rem; margin-top: .25rem; }

        /* Estado badge */
        .estado-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: .25rem .75rem;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: .5rem;
        }

        /* Body */
        .recibo-body { padding: 2rem 2.5rem; }

        .recibo-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .party-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            border: 1px solid #e2e8f0;
        }
        .party-box .party-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: .5rem;
        }
        .party-box .party-name { font-weight: 700; font-size: 1rem; color: #0f172a; }
        .party-box .party-detail { font-size: .82rem; color: #64748b; margin-top: .2rem; }

        /* Detalle del pago */
        .detalle-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        .detalle-table thead tr {
            background: #f1f5f9;
        }
        .detalle-table th {
            padding: .6rem 1rem;
            text-align: left;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            font-weight: 700;
        }
        .detalle-table td {
            padding: .75rem 1rem;
            font-size: .88rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .detalle-table tfoot td {
            padding: .75rem 1rem;
            font-weight: 700;
            font-size: 1rem;
            border-top: 2px solid #e2e8f0;
        }
        .total-amount {
            color: #1a56db;
            font-size: 1.2rem;
        }

        /* Info adicional */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-item { text-align: center; }
        .info-item .info-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: .3rem; }
        .info-item .info-value { font-weight: 600; font-size: .9rem; }

        /* Footer */
        .recibo-footer {
            border-top: 2px dashed #e2e8f0;
            padding: 1.25rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recibo-footer p { font-size: .78rem; color: #94a3b8; }
        .valid-badge {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: .5rem 1rem;
            font-size: .78rem;
            color: #166534;
            font-weight: 600;
        }

        /* Acciones (no se imprimen) */
        .acciones {
            max-width: 680px;
            margin: 1.5rem auto 0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn {
            padding: .6rem 1.25rem;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .btn-primary { background: #1a56db; color: #fff; }
        .btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #374151; }

        /* Print */
        @media print {
            body { background: #fff; padding: 0; }
            .recibo { box-shadow: none; border-radius: 0; max-width: 100%; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>

<!-- Acciones (no se imprimen) -->
<div class="acciones no-print">
    <button class="btn btn-outline" onclick="history.back()">← Volver</button>
    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="recibo">
    <!-- Header -->
    <div class="recibo-header">
        <div class="recibo-brand">
            <h1>Edu<span>SaaS</span></h1>
            <p>Sistema de Gestión Educativa · República Dominicana 🇩🇴</p>
            <div class="estado-badge">✓ Pago Confirmado</div>
        </div>
        <div class="recibo-num">
            <div class="label">Número de Factura</div>
            <div class="numero"><?= htmlspecialchars($pago['numero_factura']) ?></div>
            <div class="fecha">Fecha: <?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></div>
        </div>
    </div>

    <div class="recibo-body">
        <!-- Partes -->
        <div class="recibo-parties">
            <div class="party-box">
                <div class="party-label">Emitido por</div>
                <div class="party-name"><?= htmlspecialchars($_cfg_empresa) ?></div>
                <div class="party-detail">soporte@edusaas.do</div>
                <div class="party-detail">edusaas.do</div>
            </div>
            <div class="party-box">
                <div class="party-label">Cliente</div>
                <div class="party-name"><?= htmlspecialchars($inst['nombre'] ?? '—') ?></div>
                <div class="party-detail"><?= htmlspecialchars($inst['email'] ?? '') ?></div>
                <div class="party-detail"><?= htmlspecialchars(($inst['municipio'] ?? '') . ', ' . ($inst['provincia'] ?? '')) ?></div>
                <?php if (!empty($inst['codigo_minerd'])): ?>
                <div class="party-detail">Cód. MINERD: <?= htmlspecialchars($inst['codigo_minerd']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info rápida -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Plan</div>
                <div class="info-value"><?= htmlspecialchars($plan['nombre'] ?? '—') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Método de pago</div>
                <div class="info-value" style="text-transform:capitalize"><?= htmlspecialchars($pago['metodo_pago']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Referencia</div>
                <div class="info-value"><?= htmlspecialchars($pago['referencia'] ?: '—') ?></div>
            </div>
        </div>

        <!-- Detalle -->
        <table class="detalle-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Período cubierto</th>
                    <th style="text-align:right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Suscripción <?= htmlspecialchars($plan['nombre'] ?? '') ?></strong><br>
                        <span style="color:#64748b;font-size:.82rem">
                            Acceso al Sistema EduSaaS RD —
                            <?= ucfirst($susc['tipo_facturacion'] ?? 'mensual') ?>
                        </span>
                    </td>
                    <td>
                        <?= date('d/m/Y', strtotime($pago['periodo_desde'])) ?>
                        al
                        <?= date('d/m/Y', strtotime($pago['periodo_hasta'])) ?>
                    </td>
                    <td style="text-align:right">
                        <?php if (!empty($pago['monto_original'])): ?>
                        <span style="text-decoration:line-through;color:#94a3b8;font-size:.85rem">
                            RD$<?= number_format($pago['monto_original'], 2) ?>
                        </span><br>
                        <span style="color:#059669;font-weight:700">
                            RD$<?= number_format($pago['monto'], 2) ?>
                        </span>
                        <?php else: ?>
                        RD$<?= number_format($pago['monto'], 2) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($pago['monto_original'])): ?>
                <tr>
                    <td colspan="2" style="color:#059669;font-size:.82rem">
                        <i>✅ Descuento aplicado: 
                        <?php if (!empty($pago['descuento_pct'])): ?>
                            <?= number_format($pago['descuento_pct'], 0) ?>%
                        <?php endif; ?>
                        (–RD$<?= number_format($pago['descuento_monto'], 2) ?>)
                        <?= !empty($pago['descuento_motivo']) ? '— ' . htmlspecialchars($pago['descuento_motivo']) : '' ?>
                        </i>
                    </td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <?php
                $_itbis_pct  = (float)($_cfg_itbis ?? 0);
                $_monto_total = (float)$pago['monto'];
                if ($_itbis_pct > 0):
                    // El monto guardado es el total (base + ITBIS incluido)
                    $_subtotal    = round($_monto_total / (1 + $_itbis_pct / 100), 2);
                    $_itbis_monto = round($_monto_total - $_subtotal, 2);
                ?>
                <tr>
                    <td colspan="2" style="text-align:right;color:#64748b;font-size:.85rem">Subtotal:</td>
                    <td style="text-align:right;color:#64748b">
                        <?= htmlspecialchars($_cfg_moneda ?? 'RD$') ?><?= number_format($_subtotal, 2) ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right;color:#64748b;font-size:.85rem">
                        ITBIS (<?= (int)$_itbis_pct ?>%):
                    </td>
                    <td style="text-align:right;color:#64748b">
                        <?= htmlspecialchars($_cfg_moneda ?? 'RD$') ?><?= number_format($_itbis_monto, 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="text-align:right;color:#64748b">Total pagado:</td>
                    <td style="text-align:right" class="total-amount">
                        <?= htmlspecialchars($_cfg_moneda ?? 'RD$') ?><?= number_format($_monto_total, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer del recibo -->
    <div class="recibo-footer">
        <div>
            <p>Generado el <?= date('d/m/Y \a \l\a\s H:i') ?></p>
            <p style="margin-top:.25rem"><?= htmlspecialchars($_cfg_empresa) ?> — Sistema de Gestión Educativa</p>
        </div>
        <div class="valid-badge">
            ✓ Suscripción activa hasta<br>
            <strong><?= date('d/m/Y', strtotime($pago['periodo_hasta'])) ?></strong>
        </div>
    </div>
</div>

</body>
</html>