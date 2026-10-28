#!/usr/bin/env bash
# Implanta uma versão do Academy no Swarm e desfaz automaticamente se o smoke test falhar.
# Uso (no manager do Swarm): IMAGE_TAG=<git-sha> ./deploy.sh
set -euo pipefail

STACK=academy
APP_URL=https://academy.sempertechnology.com.br
DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
SERVICES=(app horizon scheduler video-worker)

: "${IMAGE_TAG:?defina IMAGE_TAG}"

set -a
# shellcheck source=/dev/null
. "${DEPLOY_DIR}/academy.env"
set +a

echo "==> Implantando ${STACK} com a imagem ${IMAGE_TAG}"
docker stack deploy --with-registry-auth --prune -c "${DEPLOY_DIR}/stack.yml" "${STACK}"

rollback() {
    echo "!!! $1 — desfazendo para a versão anterior" >&2
    for service in "${SERVICES[@]}"; do
        docker service rollback "${STACK}_${service}" >/dev/null 2>&1 || true
    done
    exit 1
}

echo "==> Aguardando a convergência do serviço app"
for _ in $(seq 1 60); do
    state="$(docker service inspect "${STACK}_app" --format '{{if .UpdateStatus}}{{.UpdateStatus.State}}{{else}}completed{{end}}')"
    case "${state}" in
        completed) break ;;
        rollback_*|paused) rollback "atualização do app terminou em ${state}" ;;
    esac
    sleep 5
done

echo "==> Smoke tests"
for _ in $(seq 1 12); do
    if curl -fsS -o /dev/null "${APP_URL}/up" && curl -fsS -o /dev/null "${APP_URL}/login"; then
        echo "==> Deploy de ${IMAGE_TAG} concluído"
        exit 0
    fi
    sleep 5
done

rollback "smoke tests falharam"
