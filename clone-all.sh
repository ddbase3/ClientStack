#!/bin/bash
set -e

CONFIG="local/libs.json"
DEV_DIR="dev"

mkdir -p "$DEV_DIR"

jq -c '.[]' "$CONFIG" | while read -r lib; do
  repo=$(echo "$lib" | jq -r '.repo // empty')
  dir=$(echo "$lib" | jq -r '.dir // empty')
  [ -z "$repo" ] && continue
  [ -z "$dir" ] && continue

  targetDir="$DEV_DIR/$dir"

  if [ -d "$targetDir/.git" ]; then
    echo "--> $dir bereits geklont"
  else
    echo "--> Klone $dir ($repo)"
    git clone "$repo" "$targetDir"
  fi
done

echo "==> Alle Repos geklont"

