#!/bin/sh
# Converte variáveis VAR_FILE (Docker secrets) em VAR e delega ao entrypoint da imagem base.
set -e

for file_var in $(env | grep -o '^[A-Z0-9_]*_FILE=' | sed 's/=$//'); do
    var="${file_var%_FILE}"
    file_path="$(printenv "$file_var")"

    if [ -n "$(printenv "$var" || true)" ]; then
        echo "academy-entrypoint: $var e $file_var definidos; usando $var" >&2
        continue
    fi

    if [ -r "$file_path" ]; then
        export "$var=$(cat "$file_path")"
    fi
done

exec docker-php-serversideup-entrypoint "$@"
