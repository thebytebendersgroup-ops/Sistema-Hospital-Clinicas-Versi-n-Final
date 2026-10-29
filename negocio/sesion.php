<?php
// ============================================================
// Sesiones, control de acceso por rol, protección CSRF y funciones de ayuda.
// Se incluye al principio de TODAS las páginas:
//     require_once __DIR__ . '/../negocio/sesion.php';
//
// Roles de los funcionarios: 'admin' (administrador) y los usuarios básicos
// 'medico' y 'enfermera'. Los pacientes no inician sesión: entran al portal con el QR.
// ============================================================

require_once __DIR__ . '/../config/config.php';   // constantes del sistema (URL_BASE, carpeta de subidas...)

// ¿La página se está pidiendo por HTTPS?
function conexionSegura() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

// Si se activó FORZAR_HTTPS en config.php, todo lo que llegue por http:// se manda a https://
if (FORZAR_HTTPS && !conexionSegura() && PHP_SAPI !== 'cli') {
    header('Location: https://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// Inicia la sesión (con la cookie protegida contra JavaScript y marcada como segura si hay HTTPS).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => conexionSegura()]);
    session_start();
}

// ¿Hay un funcionario con la sesión iniciada?
function hayUsuarioLogueado() {
    return isset($_SESSION['usuario']);
}

// ¿El funcionario que inició sesión es administrador?
function esAdmin() {
    return hayUsuarioLogueado() && $_SESSION['usuario']['rol'] === 'admin';
}

// Página de inicio de los funcionarios (el panel).
// La ruta empieza con ../presentacion/ porque sirve tanto desde presentacion/ como desde negocio/.
function paginaDeInicio() {
    return '../presentacion/index.php';
}

// Protege una página que pueden usar TODOS los funcionarios.
// Si no hay sesión, manda al login.
function exigirLogin() {
    if (!hayUsuarioLogueado()) {
        redirigir('../presentacion/login.php');
    }
}

// Protege una función que solo pueden usar ciertos roles.
// Ejemplo: exigirRol('admin');   o   exigirRol(['admin', 'medico']);
//  - Si no hay sesión, manda al login.
//  - Si el rol no corresponde, vuelve al panel con un aviso.
function exigirRol($rolesPermitidos) {
    exigirLogin();
    $rolesPermitidos = (array) $rolesPermitidos;
    if (!in_array($_SESSION['usuario']['rol'], $rolesPermitidos, true)) {
        guardarMensaje('No tiene permiso para realizar esa acción.', 'danger');
        redirigir(paginaDeInicio());
    }
}

// ---------- Protección CSRF ----------
// Un ataque CSRF consiste en que otra página haga enviar un formulario a nuestro sistema
// aprovechando que el funcionario tiene la sesión abierta. Para evitarlo, cada formulario
// que modifica datos lleva un código secreto (token) y el servidor comprueba que coincida.

// Devuelve el token de esta sesión (lo crea la primera vez).
function tokenCsrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Campo oculto que se pone dentro de TODOS los formularios POST (se imprime con echo campoCsrf).
function campoCsrf() {
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

// Se llama al principio de cada archivo de negocio que recibe un POST.
// Si el token no coincide, no se hace nada y se vuelve a $destino con un aviso.
function verificarCsrf($destino) {
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(tokenCsrf(), $enviado)) {
        guardarMensaje('El formulario no es válido o venció. Vuelva a intentarlo.', 'danger');
        redirigir($destino);
    }
}

// ---------- Token de un solo uso (evita enviar dos veces lo mismo) ----------
// Se crea al mostrar un formulario y se gasta al recibirlo. Si alguien vuelve atrás y lo
// reenvía, el token ya no sirve y no se guarda duplicado.
function nuevoTokenUnico($nombre) {
    $token = bin2hex(random_bytes(16));
    $_SESSION['tokens_unicos'][$nombre] = $token;
    return $token;
}

function usarTokenUnico($nombre, $valor) {
    $esperado = $_SESSION['tokens_unicos'][$nombre] ?? '';
    unset($_SESSION['tokens_unicos'][$nombre]);   // se gasta, sea correcto o no
    return $esperado !== '' && is_string($valor) && hash_equals($esperado, $valor);
}

// Escapa un texto antes de mostrarlo en pantalla (evita que se ejecute HTML/JavaScript metido por alguien).
// Se usa dentro del HTML así:  echo e($variable);
// (OJO: en un comentario de una línea nunca escribir la etiqueta de cierre de PHP, porque corta el código)
function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Redirige a otra página y corta la ejecución.
function redirigir($destino) {
    header("Location: $destino");
    exit;
}

// Guarda un mensaje para mostrarlo en la próxima página.
// $tipo: success (verde), danger (rojo) o warning (amarillo)
function guardarMensaje($texto, $tipo = 'success') {
    $_SESSION['mensaje'] = ['texto' => $texto, 'tipo' => $tipo];
}

// Muestra el mensaje guardado (si hay) y lo borra para que no se repita.
function mostrarMensaje() {
    if (!isset($_SESSION['mensaje'])) {
        return;
    }
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    echo '<div class="alert alert-' . e($mensaje['tipo']) . ' alert-dismissible fade show" role="alert">'
       . e($mensaje['texto'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
}
