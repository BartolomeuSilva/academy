#!/usr/bin/env bash
# Sobe o Academy local completo no Docker (app, filas, agendador, vídeo, banco, Redis, S3 e Mailpit).
# Uso: ./start.sh            reconstrói a imagem com o código atual e sobe tudo
#      ./start.sh --sem-build sobe com a última imagem já construída (mais rápido)
set -euo pipefail

cd "$(dirname "$0")"

BUILD_FLAG=--build

if [[ "${1:-}" == "--sem-build" ]]; then
    BUILD_FLAG=
fi

env_value() {
    grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '"' || true
}

APP_PORT="$(env_value FORWARD_APP_PORT)"
APP_PORT="${APP_PORT:-8080}"
MAILPIT_PORT="$(env_value FORWARD_MAILPIT_DASHBOARD_PORT)"
MAILPIT_PORT="${MAILPIT_PORT:-18025}"
S3_CONSOLE_PORT="$(env_value FORWARD_S3_CONSOLE_PORT)"
S3_CONSOLE_PORT="${S3_CONSOLE_PORT:-9001}"

if ! docker info >/dev/null 2>&1; then
    echo "!!! O Docker não está rodando. Abra o Docker Desktop e tente de novo." >&2
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "!!! Arquivo .env não encontrado. Copie o .env.example para .env e rode de novo." >&2
    exit 1
fi

echo "==> Subindo o Academy${BUILD_FLAG:+ (reconstruindo a imagem, pode levar alguns minutos)}"
# Logo depois de um ./stop.sh o Docker Desktop pode levar alguns segundos para liberar as portas.
if ! docker compose --profile prod up -d ${BUILD_FLAG}; then
    for attempt in $(seq 1 6); do
        echo "==> Portas ainda ocupadas; nova tentativa em 10 segundos (${attempt}/6)"
        sleep 10
        docker compose --profile prod up -d && break
        if [[ "${attempt}" == 6 ]]; then
            echo "!!! Não foi possível subir o Academy. Feche e abra o Docker Desktop e tente de novo." >&2
            exit 1
        fi
    done
fi

# Uma falha ao reservar porta pode deixar um container rodando sem rede; o app então não o encontra.
services_without_network() {
    for container in $(docker compose --profile prod ps -q --status running); do
        docker inspect -f '{{len .NetworkSettings.Networks}} {{index .Config.Labels "com.docker.compose.service"}}' "${container}"
    done | awk '$1 == 0 { print $2 }'
}

broken="$(services_without_network)"

if [[ -n "${broken}" ]]; then
    echo "==> Recriando sem rede: ${broken//$'\n'/, } (os dados ficam nos volumes)"
    # shellcheck disable=SC2086
    docker compose --profile prod up -d --force-recreate ${broken}
    docker compose --profile prod up -d

    if [[ -n "$(services_without_network)" ]]; then
        echo "!!! O Docker Desktop está com uma porta presa. Feche e abra o Docker Desktop e rode ./start.sh de novo." >&2
        exit 1
    fi
fi

echo "==> Aguardando o app responder"
APP_URL="http://localhost:${APP_PORT}"

for _ in $(seq 1 60); do
    if curl -fs -o /dev/null "${APP_URL}/up"; then
        echo
        echo "==> Academy no ar"
        echo
        echo "    Abra o app: ${APP_URL}"
        echo
        echo "    E-mails:    http://localhost:${MAILPIT_PORT}"
        echo "    Arquivos:   http://localhost:${S3_CONSOLE_PORT}"
        echo "    Para parar: ./stop.sh"
        open "${APP_URL}" 2>/dev/null || true
        exit 0
    fi

    if [[ "$(docker compose --profile prod ps -a app --format '{{.State}}')" == exited ]]; then
        break
    fi

    sleep 2
done

echo "!!! O app não subiu. Últimos logs:" >&2
docker compose --profile prod logs --tail 25 --no-log-prefix app >&2
exit 1
