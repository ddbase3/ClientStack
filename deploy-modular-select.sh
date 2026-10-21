#!/bin/sh
set -eu
BASE_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
SOURCE_DIR="$BASE_DIR/dev/ModularSelect/src"
TARGET_DIR="$BASE_DIR/assets/modularselect"

if [ ! -f "$SOURCE_DIR/index.js" ] || [ ! -f "$SOURCE_DIR/styles/modularselect.css" ]; then
  echo "ModularSelect source not found: $SOURCE_DIR" >&2
  exit 1
fi

rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"
cp -R "$SOURCE_DIR/." "$TARGET_DIR/"
diff -qr "$SOURCE_DIR" "$TARGET_DIR"
echo "ModularSelect deployed: src/ -> assets/modularselect/"
