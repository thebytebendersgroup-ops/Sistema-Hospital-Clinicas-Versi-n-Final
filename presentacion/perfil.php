<?php
// ============================================================
// Mi cuenta: datos del funcionario y cambio de su propia contraseña.
// ============================================================
require_once __DIR__ . '/_vista.php';
exigirLogin();
$tituloPagina = 'Mi cuenta';
$paginaActiva = 'perfil';
require __DIR__ . '/_cabecera.php';
?>
<h5 class="fw-semibold mb-3">Mi cuenta</h5>
<div class="row g-3">
    <div class="col-md-5"><div class="card border shadow-sm"><div class="card-body">
        <h6 class="fw-semibold">Mis datos</h6>
        <p class="mb-1"><strong><?= e($_SESSION['usuario']['nombre']) ?></strong></p>
        <p class="mb-1 text-secondary"><?= e($_SESSION['usuario']['email']) ?></p>
        <span class="badge bg-primary"><?= e($nombreRol) ?></span>
    </div></div></div>
    <div class="col-md-7"><div class="card border shadow-sm"><div class="card-body">
        <h6 class="fw-semibold">Cambiar contraseña</h6>
        <?php mostrarErroresForm(); ?>
        <form method="POST" action="../negocio/cambiar_contrasenia.php" id="formClave" novalidate>
            <?= campoCsrf() ?>
            <div class="mb-2"><label class="form-label">Contraseña actual</label><input type="password" class="form-control" name="actual" autocomplete="current-password" maxlength="72"></div>
            <div class="mb-2"><label class="form-label">Contraseña nueva</label><input type="password" class="form-control" name="nueva" id="nueva" autocomplete="new-password" maxlength="72">
                <div class="form-text">Mínimo 6 caracteres, con letras y números.</div></div>
            <div class="mb-3"><label class="form-label">Repetir la nueva</label><input type="password" class="form-control" name="repetida" id="repetida" autocomplete="new-password" maxlength="72"></div>
            <div class="text-danger small mb-2 d-none" id="claveError"></div>
            <button class="btn btn-hc btn-sm">Cambiar contraseña</button>
        </form>
    </div></div></div>
</div>
<?php
$scriptExtra = '<script>
document.getElementById("formClave").addEventListener("submit", function (ev) {
    var n = document.getElementById("nueva").value, r = document.getElementById("repetida").value, msg = "";
    if (n.length < 6 || !/[A-Za-z]/.test(n) || !/[0-9]/.test(n)) msg = "La contraseña nueva debe tener al menos 6 caracteres, con letras y números.";
    else if (n !== r) msg = "La contraseña nueva y su repetición no coinciden.";
    var caja = document.getElementById("claveError"); caja.textContent = msg; caja.classList.toggle("d-none", msg === "");
    if (msg) ev.preventDefault();
});
</script>';
require __DIR__ . '/_pie.php'; ?>
