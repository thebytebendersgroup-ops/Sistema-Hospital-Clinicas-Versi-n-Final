<?php
// ============================================================
// Funciones de ayuda: paginación de listados y limpieza de filtros.
// ============================================================

// Calcula la paginación. $paginaPedida viene de la URL (?pagina=2), así que se corrige
// si es menor que 1 o mayor que la última página.
// Devuelve: pagina, paginas (total de páginas), por_pagina, desplazamiento y total.
function paginar($total, $porPagina, $paginaPedida) {
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina  = min(max(1, (int) $paginaPedida), $paginas);
    return [
        'pagina'         => $pagina,
        'paginas'        => $paginas,
        'por_pagina'     => $porPagina,
        'desplazamiento' => ($pagina - 1) * $porPagina,
        'total'          => $total,
    ];
}

// Una fecha de filtro (?desde=2026-10-01): si no tiene el formato AAAA-MM-DD, se descarta.
function fechaDeFiltro($texto) {
    $texto = trim((string) $texto);
    $fecha = DateTime::createFromFormat('Y-m-d', $texto);
    return ($fecha && $fecha->format('Y-m-d') === $texto) ? $texto : '';
}

// Un número entero positivo de la URL (?id=5), o 0 si no es válido.
function enteroPositivo($valor) {
    return (is_string($valor) && ctype_digit($valor)) ? (int) $valor : 0;
}

// Al principio de cada script de negocio que modifica datos: solo acepta POST y comprueba el token CSRF.
// Si algo falla, vuelve a $destino. (Requiere haber incluido sesion.php.)
function exigirPost($destino) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir($destino);
    }
    verificarCsrf($destino);
}

// Compara dos listas de campos y devuelve un texto con lo que cambió ("titulo: A → B; ...").
function describirCambios($antes, $despues, $campos) {
    $cambios = [];
    foreach ($campos as $campo => $etiqueta) {
        $a = (string) ($antes[$campo] ?? '');
        $d = (string) ($despues[$campo] ?? '');
        if ($a !== $d) {
            $cambios[] = $etiqueta . ': "' . mb_substr($a, 0, 40) . '" → "' . mb_substr($d, 0, 40) . '"';
        }
    }
    return $cambios ? implode('; ', $cambios) : 'sin cambios en los datos';
}
