#!/bin/bash
set -e

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
SOURCE_DIR="$BASE_DIR/dev/ModularChatbot/src"
TARGET_DIR="$BASE_DIR/assets/modularchatbot"

if [ ! -d "$SOURCE_DIR" ]; then
	echo "ModularChatbot source directory not found: $SOURCE_DIR" >&2
	exit 1
fi

rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"
cp -a "$SOURCE_DIR/." "$TARGET_DIR/"

php "$BASE_DIR/dev/ModularChatbot/tests/verify-deployment.php"
