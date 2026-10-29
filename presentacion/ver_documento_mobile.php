<?php
// ============================================================
// Vista de un documento para el celular (mobile-first).
// Es pública: el paciente llega desde el portal (al que entra con el QR del hospital).
// Se identifica el documento con ?id=NÚMERO. El paciente solo ve documentos PUBLICADOS y de
// servicios activos; un funcionario con sesión iniciada también ve los ocultos (para revisarlos).
// ============================================================
require_once __DIR__ . '/../negocio/sesion.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';

$id  = $_GET['id'] ?? '';
$doc = false;

// El id tiene que ser un número (si no, ni se consulta la base)
if (ctype_digit($id)) {
    try {
        $doc = (new Documento((new Conexion())->getConexion()))->buscarPorId($id, !hayUsuarioLogueado());
    } catch (PDOException $e) {
        error_log('Error al leer el documento: ' . $e->getMessage());
    }
}

if (!$doc) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Documento Médico — SIGSM</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        h2 { color: #0284c7; font-size: 1.25rem; margin-top: 0; }
        .info-group {
            margin-bottom: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .label { font-size: 0.85rem; color: #64748b; }
        .value { font-size: 1rem; font-weight: 600; color: #1e293b; }
        .btn-descargar {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #0284c7;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 12px;
            box-sizing: border-box;
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn-secundario { background-color: #e2e8f0; color: #1e293b; }
        .btn-encuesta { color: #0284c7; text-decoration: none; }
        .aviso { text-align: center; color: #64748b; }
    </style>
</head>
<body>

<div class="container">
<?php if (!$doc): ?>
    <h2>Documento no encontrado</h2>
    <p class="aviso">El documento no existe o no está disponible.</p>
<?php else: ?>
    <h2>📄 <?= e($doc['titulo']) ?></h2>

    <div class="info-group">
        <div class="label">Servicio</div>
        <div class="value"><?= e($doc['categoria']) ?></div>
    </div>

    <div class="info-group">
        <div class="label">Fecha de emisión</div>
        <div class="value"><?= e(date('d/m/Y', strtotime($doc['fecha_emision']))) ?></div>
    </div>

    <?php if ($doc['descripcion']): ?>
    <div class="info-group">
        <div class="label">Descripción</div>
        <div class="value"><?= e($doc['descripcion']) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($doc['archivo']): ?>
        <a href="../uploads/<?= e(rawurlencode($doc['archivo'])) ?>" target="_blank" class="btn-descargar">Abrir archivo adjunto</a>
    <?php endif; ?>
    <button type="button" class="btn-descargar btn-secundario" onclick="window.print();">Imprimir / Guardar PDF</button>
<?php endif; ?>
</div>

<div style="text-align: center; margin-top: 20px;">
    <?php if (hayUsuarioLogueado()): ?>
        <a href="documentos.php" class="btn-encuesta">&larr; Volver al panel</a>
    <?php else: ?>
        <a href="portal-paciente.php" class="btn-encuesta">&larr; Volver al portal</a>
    <?php endif; ?>
</div>
</body>
</html>
