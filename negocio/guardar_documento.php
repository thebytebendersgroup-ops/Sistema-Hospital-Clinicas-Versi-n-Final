<?php
// ============================================================
// Alta y edición de documentos (formulario de presentacion/documentos.php).
// Con "id" edita (y puede reemplazar el PDF); sin "id" crea uno nuevo.
// Guarda en el registro de acciones quién lo cargó/cambió y qué cambió.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';
require_once __DIR__ . '/../datos/Categoria.php';

exigirLogin();   // todos los funcionarios pueden cargar y editar
$volver = '../presentacion/documentos.php';
exigirPost($volver);

$id = trim($_POST['id'] ?? '');
$datos = [
    'id'            => $id,
    'titulo'        => trim($_POST['titulo'] ?? ''),
    'descripcion'   => trim($_POST['descripcion'] ?? ''),
    'id_categoria'  => trim($_POST['id_categoria'] ?? ''),
    'fecha_emision' => trim($_POST['fecha_emision'] ?? ''),
    'publicado'     => isset($_POST['publicado']) ? '1' : '0',
];
$archivo = $_FILES['archivo'] ?? null;

try {
    $pdo       = (new Conexion())->getConexion();
    $documento = new Documento($pdo);
    $categoria = new Categoria($pdo);

    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $documento->buscarPorId($id) : false;
        if (!$existente) {
            guardarMensaje('El documento que quiere editar no existe.', 'danger');
            redirigir($volver);
        }
    }

    // En el alta el archivo es obligatorio
    $errores = validarDocumento($datos, $archivo, $categoria);
    if (!$existente && (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE)) {
        $errores[] = 'Debe elegir un archivo (PDF, JPG o PNG).';
    }
    if ($errores) {
        $_SESSION['errores_form'] = $errores;
        $_SESSION['datos_form']   = $datos;
        redirigir($volver);
    }

    // Subir el archivo (nombre al azar, así no se pisan ni se adivinan)
    $nombreArchivo = $existente ? $existente['archivo'] : null;
    $archivoViejo  = null;
    if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
        $extension   = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extension   = $extension === 'jpeg' ? 'jpg' : $extension;
        $nuevoNombre = bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file($archivo['tmp_name'], CARPETA_SUBIDAS . $nuevoNombre)) {
            $_SESSION['errores_form'] = ['No se pudo guardar el archivo en el servidor.'];
            $_SESSION['datos_form']   = $datos;
            redirigir($volver);
        }
        $archivoViejo  = $nombreArchivo;
        $nombreArchivo = $nuevoNombre;
    }

    $documento->id            = $existente ? $existente['id'] : null;
    $documento->titulo        = $datos['titulo'];
    $documento->descripcion   = $datos['descripcion'] !== '' ? $datos['descripcion'] : null;
    $documento->archivo       = $nombreArchivo;
    $documento->fecha_emision = $datos['fecha_emision'];
    $documento->id_categoria  = $datos['id_categoria'];
    $documento->publicado     = $datos['publicado'] === '1' ? 1 : 0;
    $documento->id_usuario    = $_SESSION['usuario']['id'];
    $documento->guardar();

    // Si se reemplazó el archivo, se borra el anterior (excepto los de ejemplo que vienen con el sistema)
    if ($archivoViejo && strpos($archivoViejo, 'ejemplo_') !== 0 && is_file(CARPETA_SUBIDAS . basename($archivoViejo))) {
        unlink(CARPETA_SUBIDAS . basename($archivoViejo));
    }

    if ($existente) {
        $nuevo = ['titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'], 'fecha_emision' => $datos['fecha_emision'],
                  'id_categoria' => $datos['id_categoria'], 'publicado' => $datos['publicado'] === '1' ? 'sí' : 'no',
                  'archivo' => $archivoViejo ? 'archivo reemplazado' : ''];
        $anterior = ['titulo' => $existente['titulo'], 'descripcion' => $existente['descripcion'], 'fecha_emision' => $existente['fecha_emision'],
                     'id_categoria' => $existente['id_categoria'], 'publicado' => $existente['publicado'] ? 'sí' : 'no', 'archivo' => ''];
        $detalle = describirCambios($anterior, $nuevo, ['titulo' => 'título', 'descripcion' => 'descripción', 'fecha_emision' => 'fecha',
                                                         'id_categoria' => 'servicio (id)', 'publicado' => 'publicado', 'archivo' => 'archivo']);
        registrarAccion($pdo, 'documento_editado', mb_substr($detalle, 0, 255), 'documento', $documento->id);
    } else {
        registrarAccion($pdo, 'documento_creado', 'Documento "' . mb_substr($datos['titulo'], 0, 80) . '"', 'documento', $documento->id);
    }

    guardarMensaje($existente ? 'Documento actualizado correctamente.' : 'Documento creado correctamente.');
    redirigir($volver);

} catch (PDOException $e) {
    error_log('Error al guardar el documento: ' . $e->getMessage());
    $_SESSION['errores_form'] = ['Ocurrió un error al guardar en la base de datos.'];
    $_SESSION['datos_form']   = $datos;
    redirigir($volver);
}
