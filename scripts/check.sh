#!/bin/sh
# Runs existing checks in isolated temporary storage, never in private-local.
set -eu
cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

if [ -z "${PHP_BIN:-}" ]; then
  if [ -x ./runtime/php ]; then PHP_BIN="$PWD/runtime/php"; else PHP_BIN=php; fi
fi
PYTHON_BIN=${PYTHON_BIN:-python3}
NODE_BIN=${NODE_BIN:-node}
TEST_PORT=${TEST_PORT:-8767}

for executable in "$PHP_BIN" "$PYTHON_BIN" "$NODE_BIN"; do
  if ! command -v "$executable" >/dev/null 2>&1; then
    printf 'Missing test dependency: %s\nSee README.md for setup.\n' "$executable" >&2
    exit 1
  fi
done
"$PYTHON_BIN" -c 'from PIL import Image, ImageOps' || {
  printf 'Install test dependencies from requirements-dev.txt first.\n' >&2
  exit 1
}

for file in app/*.php bin/*.php public/*.php; do
  "$PHP_BIN" -l "$file"
done
"$NODE_BIN" --check public/app.js
"$NODE_BIN" --check public/sw.js
"$NODE_BIN" tests/stats.mjs

test_work=$(mktemp -d "${TMPDIR:-/tmp}/mi-armario-tests.XXXXXX")
printf '\nIsolated test files and reports: %s\n' "$test_work"
"$PYTHON_BIN" tests/integration.py --php "$(command -v "$PHP_BIN")" --work "$test_work" --port "$TEST_PORT"
"$PYTHON_BIN" tests/photos.py "$(command -v "$PHP_BIN")" "$test_work"
printf '\nAll checks passed. Your personal armario was not modified.\n'
