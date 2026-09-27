#!/bin/zsh
set -eu
cd "$(dirname "$0")"
if [[ -x ./runtime/php ]]; then
  armario_php="$PWD/runtime/php"
elif command -v php >/dev/null 2>&1; then
  armario_php="$(command -v php)"
else
  echo "Falta PHP. Pide a tu tío instalar PHP 8.4 con GD, SQLite, mbstring, EXIF y ZIP."
  read -r "?Pulsa Intro para cerrar."
  exit 1
fi
"$armario_php" bin/local-config.php
"$armario_php" bin/install.php --local
(sleep 1; open 'http://127.0.0.1:8765') &
echo 'Mi armario está abierto. Mantén esta ventana abierta mientras lo usas.'
echo 'Para cerrarlo, pulsa Control + C aquí.'
exec "$armario_php" -d upload_max_filesize=128M -d post_max_size=132M -d memory_limit=512M -d max_execution_time=120 -S 127.0.0.1:8765 -t public bin/router.php
