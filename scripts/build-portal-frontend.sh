#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORTAL="${ROOT}/web-portal"

echo "Building web-portal frontend in Node container..."
docker run --rm \
  -v "${PORTAL}:/app" \
  -w /app \
  node:22-bookworm \
  bash -lc "rm -rf node_modules && npm install --legacy-peer-deps && node ./node_modules/vite/bin/vite.js build"

echo "Done. Assets in web-portal/public/build/"
