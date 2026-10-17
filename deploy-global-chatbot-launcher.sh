#!/bin/sh
set -eu
BASE_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
SOURCE_DIR="$BASE_DIR/dev/GlobalChatbotLauncher/src"
TARGET_DIR="$BASE_DIR/assets/globalchatbotlauncher"
if [ ! -f "$SOURCE_DIR/index.js" ]; then
  echo "GlobalChatbotLauncher source not found: $SOURCE_DIR" >&2
  exit 1
fi
rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"
cp -a "$SOURCE_DIR/." "$TARGET_DIR/"
php "$BASE_DIR/dev/GlobalChatbotLauncher/tests/verify-deployment.php"
