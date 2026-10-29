<?php
// ============================================================
// CAPA DE NEGOCIO - Procesa el formulario de login (presentacion/login.php)
// Comprueba email y contraseña. Limita los intentos fallidos (fuerza bruta):
// después de MAX_INTENTOS_LOGIN errores se bloquea por MINUTOS_BLOQUEO minutos.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Usuario.php';
require_once __DIR__ . '/../datos/IntentoLogin.php';

exigirPost('../presentacion/login.php');

$email       = trim($_POST['email'] ?? '');
$contrasenia = $_POST['contrasenia'] ?? '';
$ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 1) Validar los datos
$errores = validarLogin($email, is_string($contrasenia) ? $contrasenia : '');
if ($errores) {
    redirigir('../presentacion/login.php?error=' . ($email === '' || $contrasenia === '' ? 'campos_vacios' : 'email_invalido'));
}

try {
    $pdo      = (new Conexion())->getConexion();
    $intentos = new IntentoLogin($pdo);

    // 2) ¿Está bloqueado por demasiados intentos fallidos?
    $desde  = date('Y-m-d H:i:s', time() - MINUTOS_BLOQUEO * 60);
    $conteo = $intentos->contar($email, $ip, $desde);
    if ($conteo['email'] >= MAX_INTENTOS_LOGIN || $conteo['ip'] >= MAX_INTENTOS_LOGIN * 3) {
        registrarAccion($pdo, 'login_bloqueado', 'Acceso bloqueado temporalmente para ' . $email, 'usuario', null, null);
        redirigir('../presentacion/login.php?error=bloqueado');
    }

    // 3) Comprobar usuario y contraseña (misma respuesta si falla uno u otro)
    $usuario = new Usuario($pdo);
    if (!$usuario->iniciarSesion($email, $contrasenia)) {
        $intentos->registrar($email, $ip);
        registrarAccion($pdo, 'login_fallido', 'Intento fallido con el email ' . $email, 'usuario', null, null);
        redirigir('../presentacion/login.php?error=credenciales');
    }

    // 4) Login correcto: sesión nueva, token CSRF nuevo y se borran los intentos fallidos
    session_regenerate_id(true);
    unset($_SESSION['csrf']);
    $intentos->limpiar($email);
    $_SESSION['usuario'] = [
        'id'     => $usuario->id,
        'nombre' => $usuario->nombre,
        'email'  => $usuario->email,
        'rol'    => $usuario->rol,
    ];
    registrarAccion($pdo, 'login', 'Inicio de sesión', 'usuario', $usuario->id, $usuario->id);
    redirigir(paginaDeInicio());

} catch (PDOException $e) {
    error_log('Error en el login: ' . $e->getMessage());
    redirigir('../presentacion/login.php?error=servidor');
}
