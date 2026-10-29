<?php
// ============================================================
// Protección de los formularios PÚBLICOS (encuesta) contra programas automáticos.
// Se usan dos trampas simples:
//   1) Un campo escondido ("sitio_web") que una persona no ve y no completa, pero un
//      programa que rellena todo sí. Si viene con texto, es un robot.
//   2) Un tiempo mínimo: una persona tarda unos segundos en responder; un programa envía al instante.
// ============================================================

// Se llama al MOSTRAR el formulario: anota a qué hora se abrió.
function marcarInicioFormulario($nombre) {
    $_SESSION['inicio_formulario'][$nombre] = time();
}

// Campo escondido (trampa) para poner dentro del formulario.
function campoTrampa() {
    return '<div style="position:absolute;left:-9999px;" aria-hidden="true">'
         . '<label>No completar este campo <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>';
}

// Se llama al RECIBIR el formulario: true si parece enviado por un programa.
function pareceAutomatico($nombre, $post) {
    if (($post['sitio_web'] ?? '') !== '') {
        return true;   // cayó en la trampa
    }
    $inicio = $_SESSION['inicio_formulario'][$nombre] ?? 0;
    unset($_SESSION['inicio_formulario'][$nombre]);
    return $inicio === 0 || (time() - $inicio) < SEGUNDOS_MINIMOS_FORMULARIO;
}
