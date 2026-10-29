#!/bin/sh
# RESTAURAR un respaldo de SIGSM.
# Uso:  sh backup/restaurar.sh backup/copias/2026-10-27_10-30 [base]
# Sin nombre de base, restaura en "sigsm_verificacion" (copia de prueba; NO pisa la base real).
CARPETA="$1"
BASE="${2:-sigsm_verificacion}"
USUARIO=root
RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
[ -z "$CARPETA" ] && { echo "Falta la carpeta del respaldo"; exit 1; }

mysql -u "$USUARIO" -e "DROP DATABASE IF EXISTS $BASE; CREATE DATABASE $BASE CHARACTER SET utf8mb4;" || exit 1
mysql -u "$USUARIO" "$BASE" < "$CARPETA/sigsm.sql" || exit 1
if [ "$BASE" = "sigsm_prueba" ]; then
  unzip -qo "$CARPETA/uploads.zip" -d "$RAIZ/uploads"
fi
echo "Controles de la recuperación (comparar con la base original):"
mysql -u "$USUARIO" "$BASE" < "$RAIZ/backup/verificar_recuperacion.sql"
