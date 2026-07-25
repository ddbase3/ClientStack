#!/bin/sh
set -eu

BASE_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
SOURCE_DIR="$BASE_DIR/dev/ClassicChatbot/src"
TARGET_DIR="$BASE_DIR/assets/classicchatbot"

if [ ! -d "$SOURCE_DIR" ]; then
	echo "ClassicChatbot source directory not found: $SOURCE_DIR" >&2
	exit 1
fi

rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"
cp -R "$SOURCE_DIR/." "$TARGET_DIR/"

php "$BASE_DIR/dev/ClassicChatbot/tests/verify-deployment.php"
