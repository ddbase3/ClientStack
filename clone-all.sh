#!/bin/bash
set -euo pipefail

CONFIG="local/libs.json"
DEV_DIR="dev"
FILTER="${1:-}"

mkdir -p "$DEV_DIR"

jq -c '.[]' "$CONFIG" | while read -r lib; do
  name=$(echo "$lib" | jq -r '.name')
  repo=$(echo "$lib" | jq -r '.repo // empty')
  dir=$(echo "$lib" | jq -r '.dir // .repoDir // .name')
  branch=$(echo "$lib" | jq -r '.branch // empty')
  tag=$(echo "$lib" | jq -r '.tag // empty')

  if [ -n "$FILTER" ] && [ "$name" != "$FILTER" ]; then
    continue
  fi

  [ -z "$repo" ] && continue
  [ -z "$dir" ] && continue

  targetDir="$DEV_DIR/$dir"

  if [ -d "$targetDir/.git" ]; then
    if [ -n "$(git -C "$targetDir" status --porcelain)" ]; then
      echo "Fehler: $targetDir enthält lokale Änderungen und wird nicht aktualisiert." >&2
      exit 1
    fi

    echo "--> Aktualisiere $dir ($repo)"
  else
    echo "--> Klone $dir ($repo)"

    if [ -n "$tag" ]; then
      git clone --depth 1 --branch "$tag" "$repo" "$targetDir"
      continue
    fi

    if [ -n "$branch" ]; then
      git clone --depth 1 --branch "$branch" "$repo" "$targetDir"
      continue
    fi

    git clone "$repo" "$targetDir"
    continue
  fi

  if [ -n "$tag" ]; then
    git -C "$targetDir" fetch --depth 1 origin "refs/tags/$tag:refs/tags/$tag"
    git -C "$targetDir" checkout --detach "$tag"
    continue
  fi

  if [ -n "$branch" ]; then
    git -C "$targetDir" fetch --depth 1 origin "$branch"
    git -C "$targetDir" checkout "$branch"
    git -C "$targetDir" merge --ff-only "origin/$branch"
    continue
  fi

  git -C "$targetDir" pull --ff-only
done

echo "==> Alle ausgewählten Repos sind bereit"
