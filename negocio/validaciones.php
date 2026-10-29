<?php
require_once __DIR__ . '/../config/config.php';

// ============================================================
// Validaciones de formularios en PHP (lado del servidor)
// Cada función devuelve una lista de errores. Si la lista está vacía,
// los datos son correctos. (JavaScript también valida, pero eso se puede
// saltear: la validación que de verdad cuenta es esta.)
// ============================================================

// --- Login ---
function validarLogin($email, $contrasenia) {
    $errores = [];
    if ($email === '' || $contrasenia === '') {
        $errores[] = 'Debe ingresar el email y la contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no tiene un formato válido.';
    }
    return $errores;
}

// --- Documento ---
// $datos: los campos del formulario. $archivo: lo que viene en $_FILES['archivo'].
// $categoria: objeto Categoria (se usa para comprobar que exista el servicio).
function validarDocumento($datos, $archivo, $categoria) {
    $errores = [];

    // Título
    $largo = mb_strlen($datos['titulo']);
    if ($largo < 3 || $largo > 120) {
        $errores[] = 'El título es obligatorio y debe tener entre 3 y 120 caracteres.';
    }

    // Descripción (opcional)
    if (mb_strlen($datos['descripcion']) > 500) {
        $errores[] = 'La descripción no puede superar los 500 caracteres.';
    }

    // Servicio (categoría): tiene que existir y estar activo
    if (!ctype_digit($datos['id_categoria']) || !$categoria->existe($datos['id_categoria'])) {
        $errores[] = 'Seleccione un servicio válido.';
    }

    // Fecha con formato AAAA-MM-DD
    $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_emision']);
    if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_emision']) {
        $errores[] = 'La fecha de emisión no es válida.';
    }

    // Archivo (opcional)
    return array_merge($errores, validarArchivo($archivo));
}

// Revisa el archivo subido: tipo permitido y tamaño máximo.
function validarArchivo($archivo) {
    // Si no se eligió ningún archivo, no hay nada que validar.
    if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return [];
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['No se pudo subir el archivo. Pruebe con otro.'];
    }

    $errores = [];
    if ($archivo['size'] > TAMANIO_MAXIMO_ARCHIVO) {
        $errores[] = 'El archivo no puede pesar más de 2 MB.';
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
        $errores[] = 'Solo se permiten archivos PDF, JPG o PNG.';
    } elseif (function_exists('finfo_open')) {
        // Además de la extensión, se mira el contenido real del archivo.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo  = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        if (!in_array($tipo, ['application/pdf', 'image/jpeg', 'image/png'])) {
            $errores[] = 'El contenido del archivo no corresponde a un PDF, JPG o PNG.';
        }
    }
    return $errores;
}

// --- Servicio (categoría) ---
function validarServicio($nombre, $id, $categoria) {
    $errores = [];
    $largo = mb_strlen($nombre);
    if ($largo < 3 || $largo > 80) {
        $errores[] = 'El nombre del servicio debe tener entre 3 y 80 caracteres.';
    } elseif ($categoria->nombreExiste($nombre, $id)) {
        $errores[] = 'Ya existe un servicio con ese nombre.';
    }
    return $errores;
}

// --- Usuario ---
// $datos: nombre, email, rol, contrasenia. $esAlta: en el alta la contraseña es obligatoria;
// al editar, si se deja vacía no se cambia.
function validarUsuario($datos, $id, $esAlta, $usuario) {
    $errores = [];
    $largo = mb_strlen($datos['nombre']);
    if ($largo < 3 || $largo > 80) {
        $errores[] = 'El nombre debe tener entre 3 y 80 caracteres.';
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['email']) > 100) {
        $errores[] = 'El email no tiene un formato válido.';
    } elseif ($usuario->emailExiste($datos['email'], $id)) {
        $errores[] = 'Ya existe un usuario con ese email.';
    }
    if (!in_array($datos['rol'], ['admin', 'medico', 'enfermera'], true)) {
        $errores[] = 'Seleccione un rol válido.';
    }
    if ($esAlta || $datos['contrasenia'] !== '') {
        $errores = array_merge($errores, validarContraseniaNueva($datos['contrasenia']));
    }
    return $errores;
}

// Reglas de una contraseña nueva: mínimo 6 caracteres, con al menos una letra y un número.
function validarContraseniaNueva($contrasenia) {
    if (strlen($contrasenia) < 6 || strlen($contrasenia) > 72) {
        return ['La contraseña debe tener entre 6 y 72 caracteres.'];
    }
    if (!preg_match('/[A-Za-z]/', $contrasenia) || !preg_match('/[0-9]/', $contrasenia)) {
        return ['La contraseña debe tener al menos una letra y un número.'];
    }
    return [];
}

// --- Cambio de contraseña (el propio usuario) ---
function validarCambioContrasenia($actual, $nueva, $repetida, $usuario, $idUsuario) {
    $errores = [];
    if (!$usuario->contraseniaCorrecta($idUsuario, $actual)) {
        $errores[] = 'La contraseña actual no es correcta.';
    }
    $errores = array_merge($errores, validarContraseniaNueva($nueva));
    if ($nueva !== $repetida) {
        $errores[] = 'La contraseña nueva y su repetición no coinciden.';
    }
    return $errores;
}

// --- Categoría de encuesta (Satisfacción, Instalaciones...) ---
function validarCategoriaEncuesta($nombre, $id, $categoriaEncuesta) {
    $errores = [];
    $largo = mb_strlen($nombre);
    if ($largo < 3 || $largo > 60) {
        $errores[] = 'El nombre de la categoría debe tener entre 3 y 60 caracteres.';
    } elseif ($categoriaEncuesta->nombreExiste($nombre, $id)) {
        $errores[] = 'Ya existe una categoría de encuestas con ese nombre.';
    }
    return $errores;
}

// --- Encuesta (datos generales) ---
// $datos: titulo, descripcion, id_categoria ('' = encuesta general), id_categoria_encuesta ('' = sin categoría).
// $categoriaEncuesta (opcional): objeto CategoriaEncuesta para comprobar que la categoría exista.
// $idActual: categoría que la encuesta ya tenía (se acepta aunque esa categoría esté de baja).
function validarEncuesta($datos, $categoria, $categoriaEncuesta = null, $idActual = '') {
    $errores = [];
    $largo = mb_strlen($datos['titulo']);
    if ($largo < 3 || $largo > 120) {
        $errores[] = 'El título debe tener entre 3 y 120 caracteres.';
    }
    if (mb_strlen($datos['descripcion']) > 300) {
        $errores[] = 'La descripción no puede superar los 300 caracteres.';
    }
    if ($datos['id_categoria'] !== '' && (!ctype_digit($datos['id_categoria']) || !$categoria->existe($datos['id_categoria']))) {
        $errores[] = 'El servicio elegido no es válido.';
    }
    $tipo = $datos['id_categoria_encuesta'] ?? '';
    if ($tipo !== '' && $categoriaEncuesta) {
        if (!ctype_digit($tipo) || (!$categoriaEncuesta->existe($tipo) && (string) $tipo !== (string) $idActual)) {
            $errores[] = 'La categoría de encuesta elegida no es válida.';
        }
    }
    return $errores;
}

// --- Pregunta de una encuesta ---
// $datos: texto, tipo, opciones (texto con una opción por línea).
// Devuelve ['errores' => [...], 'opciones' => [lista de textos ya limpios]].
function validarPregunta($datos) {
    $errores = [];
    $opciones = [];

    $largo = mb_strlen($datos['texto']);
    if ($largo < 3 || $largo > 200) {
        $errores[] = 'La pregunta debe tener entre 3 y 200 caracteres.';
    }
    if (!in_array($datos['tipo'], ['unica', 'multiple', 'valoracion', 'texto'], true)) {
        $errores[] = 'Seleccione el tipo de pregunta.';
    }

    // Las preguntas de opción necesitan entre 2 y 10 opciones (una por línea)
    if (in_array($datos['tipo'], ['unica', 'multiple'], true)) {
        foreach (preg_split('/\R/', $datos['opciones']) as $linea) {
            $linea = trim($linea);
            if ($linea !== '') {
                $opciones[] = $linea;
            }
        }
        $minusculas = array_map('mb_strtolower', $opciones);
        if (count($opciones) < 2 || count($opciones) > 10) {
            $errores[] = 'Escriba entre 2 y 10 opciones, una por línea.';
        } elseif (count(array_unique($minusculas)) !== count($minusculas)) {
            $errores[] = 'Las opciones no pueden repetirse.';
        }
        foreach ($opciones as $opcion) {
            if (mb_strlen($opcion) > 100) {
                $errores[] = 'Cada opción puede tener hasta 100 caracteres.';
                break;
            }
        }
    }
    return ['errores' => $errores, 'opciones' => $opciones];
}

// --- Respuestas del paciente ---
// $encuesta: la encuesta completa (con 'lista_preguntas' y sus 'opciones').
// $post: lo que llegó del formulario. Los campos se llaman p_<id de la pregunta>.
// Se validan SOLO las preguntas de esa encuesta y las opciones que le pertenecen:
// cualquier otro campo o número que alguien invente se ignora.
// Devuelve ['errores' => [...], 'respuestas' => [...]] con las respuestas ya limpias.
function validarRespuestasEnvio($encuesta, $post) {
    $errores = [];
    $respuestas = [];

    foreach ($encuesta['lista_preguntas'] as $p) {
        $campo = 'p_' . $p['id'];
        $valor = $post[$campo] ?? null;
        $idPregunta = (int) $p['id'];

        if ($p['tipo'] === 'valoracion') {
            $valor = is_string($valor) ? trim($valor) : '';
            if ($valor === '') {
                if ($p['obligatoria']) { $errores[] = 'Responda con una nota del 1 al 5: ' . $p['texto']; }
                continue;
            }
            if (!in_array($valor, ['1', '2', '3', '4', '5'], true)) {
                $errores[] = 'La nota debe ser un número del 1 al 5: ' . $p['texto'];
                continue;
            }
            $respuestas[] = ['id_pregunta' => $idPregunta, 'id_opcion' => null, 'valor_escala' => (int) $valor, 'valor_texto' => null];

        } elseif ($p['tipo'] === 'unica') {
            $valor = is_string($valor) ? trim($valor) : '';
            if ($valor === '') {
                if ($p['obligatoria']) { $errores[] = 'Elija una opción: ' . $p['texto']; }
                continue;
            }
            $idOpcion = opcionDePregunta($p, $valor);
            if (!$idOpcion) {
                $errores[] = 'La opción elegida no es válida: ' . $p['texto'];
                continue;
            }
            $respuestas[] = ['id_pregunta' => $idPregunta, 'id_opcion' => $idOpcion, 'valor_escala' => null, 'valor_texto' => null];

        } elseif ($p['tipo'] === 'multiple') {
            $marcadas = is_array($valor) ? $valor : [];
            if (!$marcadas) {
                if ($p['obligatoria']) { $errores[] = 'Marque al menos una opción: ' . $p['texto']; }
                continue;
            }
            $vistas = [];
            foreach ($marcadas as $marcada) {
                $idOpcion = is_string($marcada) ? opcionDePregunta($p, $marcada) : 0;
                if (!$idOpcion) {
                    $errores[] = 'Una de las opciones marcadas no es válida: ' . $p['texto'];
                    continue 2;
                }
                if (!isset($vistas[$idOpcion])) {   // una opción repetida se cuenta una sola vez
                    $vistas[$idOpcion] = true;
                    $respuestas[] = ['id_pregunta' => $idPregunta, 'id_opcion' => $idOpcion, 'valor_escala' => null, 'valor_texto' => null];
                }
            }

        } else {   // texto breve
            $valor = is_string($valor) ? trim($valor) : '';
            if ($valor === '') {
                if ($p['obligatoria']) { $errores[] = 'Escriba una respuesta: ' . $p['texto']; }
                continue;
            }
            if (mb_strlen($valor) > 300) {
                $errores[] = 'La respuesta de texto no puede superar los 300 caracteres.';
                continue;
            }
            $respuestas[] = ['id_pregunta' => $idPregunta, 'id_opcion' => null, 'valor_escala' => null, 'valor_texto' => $valor];
        }
    }

    if (!$errores && !$respuestas) {
        $errores[] = 'Responda al menos una pregunta.';
    }
    return ['errores' => $errores, 'respuestas' => $respuestas];
}

// Devuelve el id de la opción si el valor corresponde a una opción de ESA pregunta, o 0 si no.
function opcionDePregunta($pregunta, $valor) {
    if (!ctype_digit($valor)) {
        return 0;
    }
    foreach ($pregunta['opciones'] as $opcion) {
        if ((int) $opcion['id'] === (int) $valor) {
            return (int) $opcion['id'];
        }
    }
    return 0;
}
