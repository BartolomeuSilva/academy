#!/usr/bin/env bash
# Para o Academy local. Os dados (banco, Redis, arquivos e vídeos) ficam guardados nos volumes do Docker.
set -euo pipefail

cd "$(dirname "$0")"

if ! docker info >/dev/null 2>&1; then
    echo "==> O Docker não está rodando; não há nada para parar."
    exit 0
fi

echo "==> Parando o Academy"
docker compose --profile prod stop

echo "==> Academy parado. Os dados continuam salvos; para subir de novo: ./start.sh"
