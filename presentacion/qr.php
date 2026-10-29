<?php
// ============================================================
// Código QR del hospital: es UNO solo, igual para todos, y lleva al portal público del paciente.
// ============================================================
require_once __DIR__ . '/_vista.php';
exigirLogin();

$urlPortal    = URL_BASE . '/presentacion/portal-paciente.php';
$tituloPagina = 'Código QR';
$paginaActiva = 'qr';
$scriptExtra  = '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Se dibuja el QR con la dirección del portal (viene de config/config.php, URL_BASE)
new QRCode(document.getElementById("qrPortal"), { text: ' . json_encode($urlPortal) . ', width: 260, height: 260 });
function descargarQR() {
    var canvas = document.querySelector("#qrPortal canvas");
    var imagen = document.querySelector("#qrPortal img");
    var enlace = document.createElement("a");
    enlace.href = canvas ? canvas.toDataURL("image/png") : imagen.src;
    enlace.download = "qr-portal-pacientes.png";
    enlace.click();
}
</script>';
require __DIR__ . '/_cabecera.php';
?>
<style>@media print { body * { visibility:hidden; } #areaQR, #areaQR * { visibility:visible; } #areaQR { position:absolute; left:0; top:0; width:100%; } }</style>
<h5 class="fw-semibold mb-1">Código QR del hospital</h5>
<p class="text-secondary" style="font-size:14px;">Es el mismo para todos los pacientes. Al escanearlo se abre el portal con los documentos y la encuesta de satisfacción.</p>

<div class="card border shadow-sm" style="max-width:420px;"><div class="card-body text-center" id="areaQR">
    <div id="qrPortal" class="d-flex justify-content-center my-3"></div>
    <p class="fw-semibold mb-0">Hospital de Clínicas</p>
    <p class="text-secondary mb-0" style="font-size:12px;">Escaneá para ver información y responder la encuesta</p>
    <p class="text-secondary small mt-2 mb-0 text-break"><?= e($urlPortal) ?></p>
</div>
<div class="card-footer bg-white d-flex gap-2 no-imprimir">
    <button class="btn btn-hc btn-sm" onclick="descargarQR()"><i class="bi bi-download"></i> Descargar</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
</div></div>

<?php require __DIR__ . '/_pie.php'; ?>
