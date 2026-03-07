<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card" style="border-top:4px solid #1a56db">
    <div class="card-header fw-bold">
        <i class="bi bi-person-plus-fill me-2 text-primary"></i>Nuevo Super Administrador
    </div>
    <div class="card-body">

        <div class="alert alert-warning small mb-4">
            <i class="bi bi-shield-fill-exclamation me-1"></i>
            Los Super Admins tienen <strong>acceso total</strong> al sistema: todas las instituciones,
            facturación, configuración y datos. Crea este usuario solo para personas de absoluta confianza.
        </div>

        <form action="<?= $appUrl ?>/superadmin/usuarios/crear" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres" class="form-control" required
                           placeholder="Juan Carlos">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control"
                           placeholder="Pérez Martínez">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                    <input type="text" name="username" class="form-control" required
                           placeholder="jcperez" autocomplete="off"
                           pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo">
                </div>
                <div class="form-text">Solo letras, números y guión bajo. Sin espacios.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required
                       placeholder="jcperez@edusaas.do">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password" id="passInput"
                           class="form-control" required minlength="8"
                           placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="generarPass()" title="Generar contraseña segura">
                        <i class="bi bi-shuffle"></i>
                    </button>
                </div>
                <div class="form-text" id="passStrength"></div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= $appUrl ?>/superadmin/usuarios" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Crear Super Admin
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('passInput');
    const icon  = document.getElementById('eyeIcon');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function generarPass() {
    const chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@#$!';
    let pass = '';
    for (let i = 0; i < 14; i++) {
        pass += chars[Math.floor(Math.random() * chars.length)];
    }
    const input = document.getElementById('passInput');
    input.value = pass;
    input.type  = 'text';
    document.getElementById('eyeIcon').className = 'bi bi-eye-slash';
    evaluarPass(pass);
}

document.getElementById('passInput').addEventListener('input', function() {
    evaluarPass(this.value);
});

function evaluarPass(val) {
    const el = document.getElementById('passStrength');
    if (!val) { el.textContent = ''; return; }
    const score = [val.length >= 8, /[A-Z]/.test(val), /[0-9]/.test(val), /[^a-zA-Z0-9]/.test(val)]
        .filter(Boolean).length;
    const [txt, cls] = score <= 1 ? ['Débil', 'text-danger']
        : score === 2 ? ['Regular', 'text-warning']
        : score === 3 ? ['Buena', 'text-primary']
        : ['Muy segura ✓', 'text-success'];
    el.textContent = 'Seguridad: ' + txt;
    el.className   = 'form-text ' + cls;
}
</script>