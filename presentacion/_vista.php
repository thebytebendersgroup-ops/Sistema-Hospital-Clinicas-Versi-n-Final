<?php
// ============================================================
// Ayudas para las pantallas (presentación). No tocan la base de datos.
// ============================================================
require_once __DIR__ . '/../negocio/sesion.php';
require_once __DIR__ . '/../negocio/ayudas.php';

// Arma una URL de esta misma página cambiando algunos parámetros (para filtros y paginación).
function urlCon($cambios, $base = null) {
    $parametros = array_merge($_GET, $cambios);
    $parametros = array_filter($parametros, function ($v) { return $v !== '' && $v !== null; });
    $pagina = $base ?? basename($_SERVER['SCRIPT_NAME']);
    return $pagina . ($parametros ? '?' . http_build_query($parametros) : '');
}

// Botones de paginación (Bootstrap). $p viene de paginar().
function mostrarPaginacion($p) {
    if ($p['paginas'] <= 1) { return; }
    echo '<nav aria-label="Páginas"><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $p['paginas']; $i++) {
        $activa = $i === $p['pagina'] ? ' active' : '';
        echo '<li class="page-item' . $activa . '"><a class="page-link" href="' . e(urlCon(['pagina' => $i])) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

// Formulario mínimo con un botón que envía por POST (con token CSRF).
// $confirmacion: si no es vacío, el navegador pregunta antes de enviar.
function botonPost($url, $campos, $texto, $clase = 'btn btn-sm btn-outline-secondary', $confirmacion = '') {
    $onsubmit = $confirmacion !== '' ? ' onsubmit="return confirm(' . e(json_encode($confirmacion)) . ');"' : '';
    echo '<form method="POST" action="' . e($url) . '" class="d-inline"' . $onsubmit . '>' . campoCsrf();
    foreach ($campos as $nombre => $valor) {
        echo '<input type="hidden" name="' . e($nombre) . '" value="' . e($valor) . '">';
    }
    echo '<button type="submit" class="' . e($clase) . '">' . $texto . '</button></form>';
}

// Muestra (y borra) los errores de formulario guardados por los scripts de negocio.
function mostrarErroresForm() {
    if (empty($_SESSION['errores_form'])) { return; }
    echo '<div class="alert alert-danger py-2" style="font-size:14px;"><ul class="mb-0 ps-3">';
    foreach ($_SESSION['errores_form'] as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></div>';
    unset($_SESSION['errores_form']);
}

// Devuelve (y borra) lo que el usuario había escrito en un formulario con errores.
function datosForm() {
    $d = $_SESSION['datos_form'] ?? [];
    unset($_SESSION['datos_form']);
    return $d;
}

// Estado de encuesta con color
function etiquetaEstado($estado) {
    $colores = ['borrador' => 'secondary', 'publicada' => 'success', 'cerrada' => 'dark'];
    return '<span class="badge bg-' . ($colores[$estado] ?? 'secondary') . '">' . e(ucfirst($estado)) . '</span>';
}

$NOMBRES_ROL = ['admin' => 'Administrador', 'medico' => 'Médico', 'enfermera' => 'Enfermería'];
