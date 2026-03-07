<?php // vars: $fecha ?>
<div style="background:linear-gradient(135deg,#1a56db,#1240a8);border-radius:10px;
            padding:1.5rem;text-align:center;margin-bottom:1.5rem">
    <div style="font-size:2.5rem;margin-bottom:.5rem">🧪</div>
    <h2 style="margin:0;color:#fff;font-size:1.2rem;font-weight:700">Prueba de conexión SMTP</h2>
    <p style="margin:.3rem 0 0;color:rgba(255,255,255,.8);font-size:.85rem">
        EduSaaS RD · <?= htmlspecialchars($fecha) ?>
    </p>
</div>

<p>Si recibes este correo, la configuración SMTP está funcionando correctamente.</p>

<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;
            padding:1rem 1.25rem;margin:1.5rem 0">
    <p style="margin:0;font-size:.88rem;color:#15803d">
        ✅ <strong>Conexión exitosa</strong> — El servidor SMTP responde y puede enviar correos.
    </p>
</div>

<p style="font-size:.85rem;color:#64748b">
    Puedes cerrar esta ventana y continuar configurando tu sistema.
</p>
