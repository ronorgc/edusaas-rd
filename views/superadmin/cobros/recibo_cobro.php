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
            min-height: 100vh;
        }

        /* ── Barra de acciones (no se imprime) ── */
        .action-bar {
            background: #0f172a;
            padding: .875rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .action-bar .brand {
            font-family: 'DM Serif Display', serif;
            color: #fff;
            font-size: 1.1rem;
        }
        .action-bar .brand span { color: #f59e0b; }
        .action-bar .acciones { display: flex; gap: .75rem; }
        .btn {
            padding: .55rem 1.25rem;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            transition: all .15s;
        }
        .btn-print   { background: #1a56db; color: #fff; }
        .btn-print:hover { background: #1240a8; }
        .btn-nuevo   { background: #10b981; color: #fff; }
        .btn-nuevo:hover { background: #059669; }
        .btn-volver  { background: transparent; border: 1px solid #475569; color: #94a3b8; }
        .btn-volver:hover { border-color: #94a3b8; color: #e2e8f0; }

        /* Mensaje de éxito -->
        .exito-banner {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            text-align: center;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: .9rem;
        }

        /* ── Recibo ── */
        .page { padding: 2rem; }
        .recibo {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0,0,0,.12);
        }

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
            font-size: 2rem;
        }
        .recibo-brand h1 span { color: #f59e0b; }
        .recibo-brand p { color: rgba(255,255,255,.65); font-size: .8rem; margin-top: .2rem; }

        .recibo-num { text-align: right; }
        .recibo-num .label { color: rgba(255,255,255,.55); font-size: .7rem; text-transform: uppercase; letter-spacing: 1px; }
        .recibo-num .numero { color: #fff; font-size: 1.15rem; font-weight: 700; margin-top: .25rem; }
        .recibo-num .fecha  { color: rgba(255,255,255,.65); font-size: .8rem; margin-top: .2rem; }
        .pagado-stamp {
            display: inline-block;
            border: 3px solid #10b981;
            color: #10b981;
            border-radius: 8px;
            padding: .25rem .75rem;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: .5rem;
        }

        .recibo-body { padding: 2rem 2.5rem; }

        .parties {
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
        .party-label { font-size: .7rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; margin-bottom: .4rem; }
        .party-name  { font-weight: 700; font-size: 1rem; }
        .party-detail { font-size: .82rem; color: #64748b; margin-top: .2rem; }

        .info-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem;
            text-align: center;
        }
        .info-box { background: #f8fafc; border-radius: 8px; padding: .75rem; border: 1px solid #e2e8f0; }
        .info-box .ilabel { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: .3rem; }
        .info-box .ivalue { font-weight: 700; font-size: .92rem; }

        table.detalle { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        table.detalle thead tr { background: #f1f5f9; }
        table.detalle th { padding: .6rem 1rem; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 700; }
        table.detalle td { padding: .85rem 1rem; font-size: .9rem; border-bottom: 1px solid #f1f5f9; }
        table.detalle tfoot td { padding: .75rem 1rem; font-weight: 700; border-top: 2px solid #e2e8f0; }
        .monto-total { color: #1a56db; font-size: 1.3rem; }

        .recibo-footer {
            border-top: 2px dashed #e2e8f0;
            padding: 1.25rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recibo-footer p { font-size: .78rem; color: #94a3b8; }
        .vigencia-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: .6rem 1.25rem;
            font-size: .8rem;
            color: #166534;
            font-weight: 600;
            text-align: center;
        }

        @media print {
            .action-bar, .exito-banner { display: none !important; }
            body { background: #fff; }
            .page { padding: 0; }
            .recibo { box-shadow: none; border-radius: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<!-- Barra de acciones (no se imprime) -->
<div class="action-bar">
    <div class="brand">Edu<span>SaaS</span> RD</div>
    <div class="acciones">
        <a href="<?= $appUrl ?>/superadmin/cobros" class="btn btn-volver">
            ← Volver a cobros
        </a>
        <a href="<?= $appUrl ?>/superadmin/cobros" class="btn btn-nuevo">
            💰 Registrar otro pago
        </a>
        <button class="btn btn-print" onclick="window.print()">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>
</div>

<!-- Banner de éxito -->
<div class="exito-banner">
    ✅ &nbsp;Pago registrado exitosamente — Factura <strong><?= htmlspecialchars($pago['numero_factura']) ?></strong>
    &nbsp;·&nbsp; Suscripción activa hasta <strong><?= date('d/m/Y', strtotime($pago['periodo_hasta'])) ?></strong>
</div>

<div class="page">
<div class="recibo">

    <!-- Header -->
    <div class="recibo-header">
        <div class="recibo-brand">
            <h1>Edu<span>SaaS</span></h1>
            <p>Sistema de Gestión Educativa · República Dominicana 🇩🇴</p>
            <div class="pagado-stamp">✓ Pagado</div>
        </div>
        <div class="recibo-num">
            <div class="label">Número de Recibo</div>
            <div class="numero"><?= htmlspecialchars($pago['numero_factura']) ?></div>
            <div class="fecha">Fecha: <?= date('d \d\e F \d\e Y', strtotime($pago['fecha_pago'])) ?></div>
        </div>
    </div>

    <div class="recibo-body">

        <!-- Partes -->
        <div class="parties">
            <div class="party-box">
                <div class="party-label">Recibido por</div>
                <div class="party-name"><?= htmlspecialchars($_cfg_empresa) ?></div>
                <div class="party-detail">soporte@edusaas.do</div>
                <div class="party-detail">edusaas.do · República Dominicana</div>
            </div>
            <div class="party-box">
                <div class="party-label">Pagado por</div>
                <div class="party-name"><?= htmlspecialchars($inst['nombre'] ?? '—') ?></div>
                <div class="party-detail"><?= htmlspecialchars($inst['email'] ?? '') ?></div>
                <?php if (!empty($inst['municipio'])): ?>
                <div class="party-detail"><?= htmlspecialchars($inst['municipio'] . ', ' . ($inst['provincia'] ?? '')) ?></div>
                <?php endif; ?>
                <?php if (!empty($inst['codigo_minerd'])): ?>
                <div class="party-detail">Cód. MINERD: <?= htmlspecialchars($inst['codigo_minerd']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info rápida -->
        <div class="info-row">
            <div class="info-box">
                <div class="ilabel">Plan contratado</div>
                <div class="ivalue"><?= htmlspecialchars($plan['nombre'] ?? '—') ?></div>
            </div>
            <div class="info-box">
                <div class="ilabel">Método de pago</div>
                <div class="ivalue" style="text-transform:capitalize"><?= htmlspecialchars($pago['metodo_pago']) ?></div>
            </div>
            <div class="info-box">
                <div class="ilabel">Referencia</div>
                <div class="ivalue"><?= htmlspecialchars($pago['referencia'] ?: '—') ?></div>
            </div>
        </div>

        <!-- Detalle del pago -->
        <table class="detalle">
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
                            Acceso completo al Sistema EduSaaS RD ·
                            <?= ucfirst($susc['tipo_facturacion'] ?? 'mensual') ?>
                        </span>
                        <?php if (!empty($pago['notas'])): ?>
                        <br><span style="color:#94a3b8;font-size:.78rem;font-style:italic">
                            Nota: <?= htmlspecialchars($pago['notas']) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= date('d/m/Y', strtotime($pago['periodo_desde'])) ?></strong>
                        &nbsp;al&nbsp;
                        <strong><?= date('d/m/Y', strtotime($pago['periodo_hasta'])) ?></strong>
                    </td>
                    <td style="text-align:right;font-weight:600">
                        <?php if (!empty($pago['monto_original'])): ?>
                        <span style="text-decoration:line-through;color:#94a3b8;font-size:.82rem">
                            RD$<?= number_format($pago['monto_original'], 2) ?>
                        </span><br>
                        <span style="color:#059669">RD$<?= number_format($pago['monto'], 2) ?></span>
                        <?php else: ?>
                        RD$<?= number_format($pago['monto'], 2) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($pago['monto_original'])): ?>
                <tr>
                    <td colspan="2" style="color:#059669;font-size:.8rem">
                        ✅ Descuento aplicado:
                        <?= !empty($pago['descuento_pct']) ? number_format($pago['descuento_pct'],0).'%' : '' ?>
                        (–RD$<?= number_format($pago['descuento_monto'], 2) ?>)
                        <?= !empty($pago['descuento_motivo']) ? '— ' . htmlspecialchars($pago['descuento_motivo']) : '' ?>
                    </td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <?php
                $_itbis_pct   = (float)($_cfg_itbis ?? 0);
                $_monto_total = (float)$pago['monto'];
                if ($_itbis_pct > 0):
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
                    <td colspan="2" style="text-align:right;color:#64748b;font-size:.9rem">Total pagado:</td>
                    <td style="text-align:right" class="monto-total">
                        <?= htmlspecialchars($_cfg_moneda ?? 'RD$') ?><?= number_format($_monto_total, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer del recibo -->
    <div class="recibo-footer">
        <div>
            <p>Emitido el <?= date('d/m/Y \a \l\a\s H:i') ?></p>
            <p style="margin-top:.2rem"><?= htmlspecialchars($_cfg_empresa) ?> — Sistema de Gestión Educativa</p>
        </div>
        <div class="vigencia-box">
            ✓ Suscripción vigente hasta<br>
            <span style="font-size:1.05rem"><?= date('d/m/Y', strtotime($pago['periodo_hasta'])) ?></span>
        </div>
    </div>

</div><!-- .recibo -->
</div><!-- .page -->

<script>
    // Auto-abrir diálogo de impresión al cargar la página
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 600);
    });
</script>
</body>
</html>