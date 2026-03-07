<?php $appUrl = (require __DIR__ . '/../../../../config/app.php')['url']; ?>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card" style="border-top:4px solid #1a56db">
    <div class="card-header fw-bold">
        <i class="bi bi-pencil-fill me-2 text-primary"></i>
        Editar Super Admin: <?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?>
    </div>
    <div class="card-body">
        <form action="<?= $appUrl ?>/superadmin/usuarios/<?= $usuario['id'] ?>/editar" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nombres</label>
                    <input type="text" name="nombres" class="form-control" required
                           value="<?= htmlspecialchars($usuario['nombres']) ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control"
                           value="<?= htmlspecialchars($usuario['apellidos'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($usuario['email']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control bg-light"
                       value="<?= htmlspecialchars($usuario['username']) ?>" disabled>
                <div class="form-text">El username no se puede cambiar.</div>
            </div>

            <hr class="my-4">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-key-fill me-2 text-warning"></i>Cambiar contraseña
            </h6>

            <div class="mb-4">
                <label class="form-label fw-semibold">Nueva contraseña</label>
                <div class="input-group">
                    <input type="password" name="password" id="passInput"
                           class="form-control" minlength="8"
                           placeholder="Dejar vacío para no cambiar"
                           autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="generarPass()" title="Generar contraseña segura">
                        <i class="bi bi-shuffle"></i>
                    </button>
                </div>
                <div class="form-text" id="passStrength">
                    Solo rellena este campo si quieres cambiar la contraseña.
                </div>
            </div>

            <?php if ((int)$usuario['id'] === $yo): ?>
            <div class="alert alert-info small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Estás editando tu propia cuenta. Los cambios afectarán tu sesión actual.
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= $appUrl ?>/superadmin/usuarios" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar Cambios
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
    document.getElementById('passStrength').textContent = 'Contraseña generada: ' + pass;
    document.getElementById('passStrength').className = 'form-text text-success';
}
</script>