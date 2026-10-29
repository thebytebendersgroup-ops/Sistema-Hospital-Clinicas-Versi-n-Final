<?php
// ============================================================
// Administración de usuarios (solo administrador): alta, edición, baja lógica y cambio de contraseña.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Usuario.php';
exigirRol('admin');

$buscar = trim($_GET['q'] ?? '');
$usuarios = [];
$p = paginar(0, POR_PAGINA, 1);
try {
    $modelo = new Usuario((new Conexion())->getConexion());
    $p = paginar($modelo->contar($buscar), POR_PAGINA, $_GET['pagina'] ?? 1);
    $usuarios = $modelo->listar($buscar, $p['por_pagina'], $p['desplazamiento']);
} catch (PDOException $e) {
    error_log('Error al listar usuarios: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer los usuarios.', 'danger');
}
$datosForm = datosForm();
$reabrir = !empty($_SESSION['errores_form']);

$tituloPagina = 'Usuarios';
$paginaActiva = 'usuarios';
require __DIR__ . '/_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-semibold mb-0">Usuarios</h5>
    <button class="btn btn-hc btn-sm" onclick="abrirUsuario()"><i class="bi bi-plus-lg"></i> Nuevo usuario</button>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-8 col-md-5"><input type="search" name="q" value="<?= e($buscar) ?>" class="form-control" placeholder="Buscar por nombre o email..." maxlength="100"></div>
    <div class="col-4 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary flex-fill">Buscar</button><a href="usuarios.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a></div>
</form>
<div class="card border shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
        <tr class="<?= $u['activo'] ? '' : 'text-secondary' ?>">
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($NOMBRES_ROL[$u['rol']] ?? $u['rol']) ?></td>
            <td><?= $u['activo'] ? '<span class="badge bg-success bg-opacity-10 text-success">Activo</span>' : '<span class="badge bg-secondary">De baja</span>' ?></td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-secondary" onclick="editarUsuario(this)" title="Editar"
                    data-usuario="<?= e(json_encode(['id' => $u['id'], 'nombre' => $u['nombre'], 'email' => $u['email'], 'rol' => $u['rol']])) ?>"><i class="bi bi-pencil"></i></button>
                <?php if ($u['activo']): ?>
                    <?php if ((int) $u['id'] !== (int) $_SESSION['usuario']['id']) botonPost('../negocio/cambiar_estado_usuario.php', ['id' => $u['id'], 'activo' => 0], '<i class="bi bi-person-dash"></i>', 'btn btn-sm btn-outline-danger', '¿Dar de baja a ' . $u['nombre'] . '? Ya no podrá iniciar sesión.'); ?>
                <?php else: ?>
                    <?php botonPost('../negocio/cambiar_estado_usuario.php', ['id' => $u['id'], 'activo' => 1], '<i class="bi bi-person-check"></i>', 'btn btn-sm btn-outline-success', ''); ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$usuarios): ?><tr><td colspan="5" class="text-center text-secondary py-4">No hay usuarios.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-2">
    <span class="text-secondary" style="font-size:13px;"><?= (int) $p['total'] ?> usuario(s)</span>
    <?php mostrarPaginacion($p); ?>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_usuario.php" id="formUsuario" novalidate>
    <?= campoCsrf() ?><input type="hidden" name="id" id="uId">
    <div class="modal-header"><h6 class="modal-title" id="uTitulo">Nuevo usuario</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php mostrarErroresForm(); ?>
        <div class="mb-3"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" id="uNombre" maxlength="80"></div>
        <div class="mb-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" id="uEmail" maxlength="100"></div>
        <div class="mb-3"><label class="form-label">Rol *</label>
            <select class="form-select" name="rol" id="uRol">
                <?php foreach ($NOMBRES_ROL as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
            </select></div>
        <div class="mb-2"><label class="form-label" id="uPassEtiqueta">Contraseña *</label><input type="password" class="form-control" name="contrasenia" id="uPass" autocomplete="new-password" maxlength="72">
            <div class="form-text" id="uPassAyuda">Mínimo 6 caracteres, con letras y números.</div></div>
        <div class="text-danger small d-none" id="uErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Guardar</button></div>
</form></div></div>
<?php
$datosJs = json_encode($datosForm ?: new stdClass());
$scriptExtra = '<script>
var modalU = new bootstrap.Modal(document.getElementById("modalUsuario"));
function llenarU(d) {
    document.getElementById("uId").value = d.id || "";
    document.getElementById("uNombre").value = d.nombre || "";
    document.getElementById("uEmail").value = d.email || "";
    document.getElementById("uRol").value = d.rol || "medico";
    document.getElementById("uPass").value = "";
    document.getElementById("uTitulo").textContent = d.id ? "Editar usuario" : "Nuevo usuario";
    document.getElementById("uPassEtiqueta").textContent = d.id ? "Contraseña nueva (dejar vacía para no cambiarla)" : "Contraseña *";
}
function abrirUsuario() { llenarU({}); modalU.show(); }
function editarUsuario(b) { llenarU(JSON.parse(b.getAttribute("data-usuario"))); modalU.show(); }
document.getElementById("formUsuario").addEventListener("submit", function (ev) {
    var msg = "", esAlta = !document.getElementById("uId").value, pass = document.getElementById("uPass").value;
    if (document.getElementById("uNombre").value.trim().length < 3) msg = "El nombre debe tener al menos 3 caracteres.";
    else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(document.getElementById("uEmail").value)) msg = "Escriba un email válido.";
    else if ((esAlta || pass !== "") && (pass.length < 6 || !/[A-Za-z]/.test(pass) || !/[0-9]/.test(pass))) msg = "La contraseña debe tener al menos 6 caracteres, con letras y números.";
    var caja = document.getElementById("uErrorJs");
    caja.textContent = msg; caja.classList.toggle("d-none", msg === "");
    if (msg) ev.preventDefault();
});
' . ($reabrir ? 'llenarU(' . $datosJs . '); modalU.show();' : '') . '
</script>';
require __DIR__ . '/_pie.php'; ?>
