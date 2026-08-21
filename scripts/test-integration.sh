#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

docker compose -f .devcontainer/docker-compose.yml up -d --wait database app
docker compose -f .devcontainer/docker-compose.yml exec app composer install --prefer-dist --no-progress
docker compose -f .devcontainer/docker-compose.yml exec app vendor/bin/pest -c phpunit.integration.xml
