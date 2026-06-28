#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Building web-portal frontend assets..."
docker run --rm \
  -v "$PWD:/build" \
  -w /build \
  node:22-bookworm \
  bash -c "npm install --legacy-peer-deps && npm run build"

echo "Done. Restart the portal: docker compose restart web-portal"
