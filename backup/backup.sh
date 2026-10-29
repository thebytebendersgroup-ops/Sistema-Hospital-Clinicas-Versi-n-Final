#!/bin/sh
# RESPALDO de SIGSM (Linux / Mac / XAMPP en Linux)
# Guarda en backup/copias/AAAA-MM-DD_HH-MM/: sigsm.sql (base) y uploads.zip (archivos subidos).
# Uso:  sh backup/backup.sh
BASE=sigsm_prueba
USUARIO=root
RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
DESTINO="$RAIZ/backup/copias/$(date +%Y-%m-%d_%H-%M)"
mkdir -p "$DESTINO" || exit 1

echo "Respaldando la base $BASE ..."
mysqldump -u "$USUARIO" --routines --single-transaction "$BASE" > "$DESTINO/sigsm.sql" || { echo "ERROR: mysqldump falló"; exit 1; }

echo "Comprimiendo uploads ..."
(cd "$RAIZ/uploads" && zip -qr "$DESTINO/uploads.zip" .) || { echo "ERROR: zip falló"; exit 1; }

echo "Respaldo listo en $DESTINO"
ls -l "$DESTINO"
