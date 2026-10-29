<?php
// ============================================================
// Cabecera común del panel de funcionarios: barra superior + menú lateral.
// Cada página define antes: $tituloPagina y $paginaActiva (ej.: 'documentos').
// ============================================================
require_once __DIR__ . '/_vista.php';
exigirLogin();

$nombreUsuario = $_SESSION['usuario']['nombre'];
$iniciales = '';
foreach (array_slice(preg_split('/\s+/', trim($nombreUsuario)), 0, 2) as $palabra) {
    $iniciales .= mb_strtoupper(mb_substr($palabra, 0, 1));
}
$nombreRol = $NOMBRES_ROL[$_SESSION['usuario']['rol']] ?? '';

$menu = [
    'inicio'     => ['index.php',      'bi-house',            'Inicio'],
    'documentos' => ['documentos.php', 'bi-folder2-open',     'Documentos'],
    'encuestas'  => ['encuestas.php',  'bi-clipboard-check',  'Encuestas'],
    'resultados' => ['resultados.php', 'bi-bar-chart',        'Resultados'],
    'qr'         => ['qr.php',         'bi-qr-code',          'Código QR'],
];
if (esAdmin()) {
    $menu['usuarios']  = ['usuarios.php',  'bi-people',        'Usuarios'];
    $menu['servicios'] = ['servicios.php', 'bi-hospital',      'Servicios'];
    $menu['registro']  = ['registro.php',  'bi-journal-text',  'Registro'];
}
$menu['perfil'] = ['perfil.php', 'bi-person-gear', 'Mi cuenta'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> — SIGSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #F5F6F8; }
        .bg-hc { background-color: #1B3A5C; }
        .btn-hc { --bs-btn-bg: #2563EB; --bs-btn-border-color: #2563EB; --bs-btn-color: #fff;
                  --bs-btn-hover-bg: #1D4ED8; --bs-btn-hover-border-color: #1D4ED8; --bs-btn-hover-color: #fff; }
        .sidebar-link { color: #6B7280; font-size:14px; padding:8px 12px; border-radius:6px;
                        display:flex; align-items:center; gap:8px; text-decoration:none; }
        .sidebar-link:hover { background-color:#F3F4F6; color:#374151; }
        .sidebar-link.active { background-color:#EFF6FF; color:#2563EB; font-weight:600; }
        .form-control:focus, .form-select:focus { border-color:#2563EB; box-shadow:0 0 0 0.2rem rgba(37,99,235,0.15); }
        .table th { font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#6B7280; font-weight:500; }
        .table td { font-size:14px; vertical-align:middle; }
        .offcanvas-lg { width:220px !important; border-right:1px solid #E5E7EB !important; }
        .barra { height:14px; background:#E5E7EB; border-radius:7px; overflow:hidden; }
        .barra > div { height:100%; background:#2563EB; }
        @media print { .no-imprimir { display:none !important; } }
    </style>
</head>
<body>
<nav class="navbar bg-hc text-white border-bottom px-3 sticky-top no-imprimir" style="z-index:1040;">
    <div class="container-fluid d-flex align-items-center">
        <button class="btn btn-sm text-white me-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Abrir menú">
            <i class="bi bi-list" style="font-size:20px;"></i>
        </button>
        <span class="fw-semibold text-white me-3" style="font-size:14px;">Hospital de Clínicas</span>
        <span class="text-white-50 d-none d-sm-inline" style="font-size:13px;">S.I.G.S.M.</span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold" style="width:30px;height:30px;font-size:12px;"><?= e($iniciales) ?></div>
                <span class="d-none d-sm-inline text-white" style="font-size:13px;"><?= e($nombreUsuario) ?> <span class="text-white-50">· <?= e($nombreRol) ?></span></span>
            </div>
            <form method="POST" action="../negocio/logout.php" class="m-0"><?= campoCsrf() ?>
                <button type="submit" class="btn btn-sm text-white" title="Cerrar sesión"><i class="bi bi-box-arrow-right" style="font-size:16px;"></i> <span class="d-none d-sm-inline">Salir</span></button>
            </form>
        </div>
    </div>
</nav>

<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start bg-white no-imprimir" id="sidebar" tabindex="-1" style="min-height:calc(100vh - 56px);">
        <div class="offcanvas-header d-lg-none border-bottom">
            <span class="fw-semibold" style="font-size:14px;">Menú</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <nav class="p-3 d-flex flex-column gap-1">
            <?php foreach ($menu as $clave => $item): ?>
                <a class="sidebar-link <?= $paginaActiva === $clave ? 'active' : '' ?>" href="<?= e($item[0]) ?>">
                    <i class="bi <?= e($item[1]) ?>"></i> <?= e($item[2]) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="flex-grow-1 p-3 p-md-4" style="min-width:0;">
        <?php mostrarMensaje(); ?>
