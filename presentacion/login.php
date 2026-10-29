<?php
require_once __DIR__ . '/../negocio/sesion.php';

// Si ya hay una sesión iniciada, se va directo a la página que le corresponde.
if (hayUsuarioLogueado()) {
    redirigir(paginaDeInicio());
}

// Mensajes de error que manda procesarLogin.php en la URL (?error=...)
$mensajesErrores = [
    'campos_vacios'  => 'Debe ingresar email y contraseña.',
    'email_invalido' => 'El email no tiene un formato válido.',
    'credenciales'   => 'Email o contraseña incorrectos.',
    'bloqueado'      => 'Demasiados intentos fallidos. Espere unos minutos antes de volver a intentar.',
    'servidor'       => 'Ocurrió un error en el servidor. Intente nuevamente.'
];

$errorKey = $_GET['error'] ?? '';
$mensajeError = $mensajesErrores[$errorKey] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — SIGSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
      
        body {
            background-color: #eef2f6;
            color: #1f2937;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
        }
        .login-wrapper {
            min-height: 100vh;
            display: flex;
        }
        .login-left {
            width: 58%;
            background-image: url('/public/img/tu-imagen.png'); /* Reemplazar con la imagen institucional */
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .login-overlay {
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(15, 23, 42, 0.82),
                rgba(15, 23, 42, 0.50),
                rgba(15, 23, 42, 0.15)
            );
            display: flex;
            align-items: flex-end;
            padding: 60px;
        }
        .login-brand h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
        }
        .login-brand h3 {
            color: #ffffff;
            font-size: 18px;
            opacity: 0.9;
        }
        .login-brand p {
            color: #ffffff;
            font-size: 14px;
            opacity: 0.8;
        }
        .login-right {
            width: 42%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px;
            background: #f8fafc;
        }
        .login-box {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            padding: 38px;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
        }
        button[type="submit"] {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #1f2937;
            color: #ffffff;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background: #1f2937;
        }
        input {
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 12px 13px !important;
            font-size: 15px !important;
        }
        input:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12) !important;
            outline: none !important;
        }
        @media (max-width: 900px) {
            .login-wrapper { flex-direction: column; }
            .login-left { width: 100%; min-height: 260px; }
            .login-right { width: 100%; padding: 24px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
      
        <div class="login-left d-none d-lg-flex">
            <div class="login-overlay">
                <div class="login-brand">
                    <h3>Hospital de Clínicas</h3>
                    <h1 class="mb-2">S.I.G.S.M.</h1>
                    <p>Sistema Informático de Gestión de Servicios Médicos.</p>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-box">

                <div class="d-lg-none mb-4">
                    <span class="text-muted" style="font-size:13px;">Hospital de Clínicas</span>
                    <h3 class="fw-bold text-dark" style="font-size:22px;">S.I.G.S.M.</h3>
                </div>

                <h2 class="fw-semibold text-dark mb-1" style="font-size:20px;">Iniciar sesión</h2>
                <p class="text-muted mb-4" style="font-size:14px;">Acceso para funcionarios del hospital. Ingrese sus credenciales.</p>

                <form id="loginForm" method="POST" action="../negocio/procesarLogin.php" autocomplete="off" novalidate>
                    <?= campoCsrf() ?>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark" style="font-size:14px;">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="funcionario@hc.edu.uy">
                        <div class="text-danger mt-1" style="font-size:12px;" id="emailError"></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark" style="font-size:14px;">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="contrasenia" placeholder="••••••••" style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass()" id="btnEye" style="border-color: #cbd5e1;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="text-danger mt-1" style="font-size:12px;" id="passwordError"></div>
                    </div>
                    
                    <button type="submit" id="btnSubmit">
                        Ingresar
                    </button>

                    <?php if ($mensajeError): ?>
                        <div class="text-danger text-center mt-3" style="font-size:13px;" id="serverError">
                            <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePass() {
        const p = document.getElementById('password');
        const i = document.querySelector('#btnEye i');
        if (p.type === 'password') { p.type = 'text'; i.className = 'bi bi-eye-slash'; }
        else { p.type = 'password'; i.className = 'bi bi-eye'; }
    }

    // Muestra el error debajo del campo y lo marca en rojo
    function showErr(id, msg) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.parentElement.querySelector('input').classList.add('is-invalid');
    }
    function clearErr(id) {
        const el = document.getElementById(id);
        el.textContent = '';
        el.parentElement.querySelector('input').classList.remove('is-invalid');
    }

    document.getElementById('email').addEventListener('input', () => clearErr('emailError'));
    document.getElementById('password').addEventListener('input', () => clearErr('passwordError'));

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let ok = true;
        const email = document.getElementById('email').value.trim();
        const pass = document.getElementById('password').value;

        if (!email) { showErr('emailError', 'El correo es obligatorio'); ok = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('emailError', 'El correo no tiene un formato válido'); ok = false; }
        else clearErr('emailError');

        if (!pass) { showErr('passwordError', 'La contraseña es obligatoria'); ok = false; }
        else clearErr('passwordError');

        if (!ok) return;

        const submitBtn = document.getElementById('btnSubmit');
        submitBtn.textContent = 'Verificando...';
        submitBtn.disabled = true;
        this.submit();
    });
    </script>
</body>
</html>