#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
IMAGE_TAG="${WAREHOUSE_LEGACY_IMAGE:-warehouse-core-legacy-php56}"
CACHE_DIR="${WAREHOUSE_LEGACY_COMPOSER_CACHE:-$ROOT_DIR/.cache/composer-legacy}"
SKIP_BUILD="${WAREHOUSE_LEGACY_SKIP_BUILD:-0}"
CONTAINER_COMMAND="${*:-composer validate --strict && composer update --no-interaction --prefer-dist --no-progress && composer test:legacy}"

mkdir -p "$CACHE_DIR"

if [ "$SKIP_BUILD" != "1" ]; then
  echo "[legacy-docker] Building image: $IMAGE_TAG"
  docker build \
    --tag "$IMAGE_TAG" \
    --file "$ROOT_DIR/docker/legacy/php56/Dockerfile" \
    "$ROOT_DIR"
else
  echo "[legacy-docker] Reusing prebuilt image: $IMAGE_TAG"
fi

echo "[legacy-docker] Running command inside container: $CONTAINER_COMMAND"
docker run --rm \
  -v "$ROOT_DIR:/app" \
  -v "$CACHE_DIR:/tmp/composer-cache" \
  -e COMPOSER_CACHE_DIR=/tmp/composer-cache \
  -w /app \
  "$IMAGE_TAG" \
  bash -lc "$CONTAINER_COMMAND"
