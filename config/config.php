<?php
// ============================================================
// Configuración general del sistema.
// (Los datos de la base de datos están en datos/conexion.php)
// ============================================================

// Hora de Uruguay (para las fechas que guarda el sistema)
date_default_timezone_set('America/Montevideo');

// Dirección con la que el CELULAR puede abrir el sistema (se escribe dentro del QR).
// En la PC alcanza con localhost, pero el celular NO puede abrir "localhost".
// Para probar con el celular (misma red wifi) poner la IP de la PC, por ejemplo:
//   http://192.168.1.20/sigsm
define('URL_BASE', 'http://localhost/sigsm');

// Archivos que se suben desde el panel de administración
define('CARPETA_SUBIDAS', __DIR__ . '/../uploads/');
define('TAMANIO_MAXIMO_ARCHIVO', 2 * 1024 * 1024);   // 2 MB

// HTTPS: ponerlo en true SOLO cuando el sitio ya funciona con https:// (ver README, sección HTTPS).
// Si es true, todo el que entre por http:// es redirigido a https:// y la cookie de sesión se marca como "segura".
define('FORZAR_HTTPS', false);

// Límites del login: después de MAX_INTENTOS_LOGIN fallos en los últimos MINUTOS_BLOQUEO minutos
// ese email queda bloqueado hasta que pase ese tiempo.
define('MAX_INTENTOS_LOGIN', 5);
define('MINUTOS_BLOQUEO', 10);

// Protección de los formularios públicos contra programas automáticos:
// un formulario enviado en menos de estos segundos desde que se abrió se rechaza.
define('SEGUNDOS_MINIMOS_FORMULARIO', 3);

// Cantidad de filas por página en los listados
define('POR_PAGINA', 10);

// Errores internos: NO se muestran en pantalla (podrían revelar datos del sistema);
// se guardan en logs/errores.log para que los revise el equipo técnico.
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0775, true);
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/errores.log');
