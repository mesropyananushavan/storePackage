#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PG_CONTAINER_NAME="${WAREHOUSE_PGSQL_CONTAINER_NAME:-warehouse-core-postgres-verification}"
PG_IMAGE_NAME="${WAREHOUSE_PGSQL_IMAGE:-postgres:16-alpine}"
PG_HOST_PORT="${WAREHOUSE_PGSQL_HOST_PORT:-54328}"
PG_DATABASE_NAME="${WAREHOUSE_PGSQL_DATABASE:-warehouse_core_test}"
PG_DATABASE_USER="${WAREHOUSE_PGSQL_USER:-warehouse}"
PG_DATABASE_PASSWORD="${WAREHOUSE_PGSQL_PASSWORD:-warehouse}"
PG_SCHEMA_FILE="${WAREHOUSE_DB_SCHEMA_FILE:-$ROOT_DIR/database/schema/postgresql.sql}"
PG_CONTAINER_SCHEMA_FILE="${WAREHOUSE_PGSQL_CONTAINER_SCHEMA_FILE:-/app/database/schema/postgresql.sql}"
PG_NETWORK_NAME="${WAREHOUSE_PGSQL_NETWORK_NAME:-warehouse-core-postgres-verification}"
PHP_IMAGE_NAME="${WAREHOUSE_PGSQL_PHP_IMAGE:-warehouse-core-php81-pgsql-verification}"
PHP_DOCKERFILE_PATH="${WAREHOUSE_PGSQL_PHP_DOCKERFILE:-$ROOT_DIR/docker/db-verification/php81-pgsql/Dockerfile}"
KEEP_CONTAINER="${WAREHOUSE_PGSQL_KEEP_CONTAINER:-0}"
DSN="pgsql:host=${PG_CONTAINER_NAME};port=5432;dbname=${PG_DATABASE_NAME}"

log()
{
    printf '[pgsql-verify] %s\n' "$1"
}

cleanup_containers()
{
    if docker ps -aq --filter "name=^/${PG_CONTAINER_NAME}$" | grep -q .; then
        docker rm -f "${PG_CONTAINER_NAME}" >/dev/null 2>&1 || true
    fi

    if docker network ls --format '{{.Name}}' | grep -qx "${PG_NETWORK_NAME}"; then
        docker network rm "${PG_NETWORK_NAME}" >/dev/null 2>&1 || true
    fi
}

ensure_network()
{
    if ! docker network ls --format '{{.Name}}' | grep -qx "${PG_NETWORK_NAME}"; then
        docker network create "${PG_NETWORK_NAME}" >/dev/null
    fi
}

wait_for_postgres()
{
    local attempt

    for attempt in $(seq 1 60); do
        if docker exec "${PG_CONTAINER_NAME}" pg_isready -U "${PG_DATABASE_USER}" -d "${PG_DATABASE_NAME}" >/dev/null 2>&1; then
            log "PostgreSQL is ready on network ${PG_NETWORK_NAME}."
            return 0
        fi

        sleep 2
    done

    log "Timed out while waiting for PostgreSQL."
    docker logs "${PG_CONTAINER_NAME}" || true
    return 1
}

build_php_runtime()
{
    log "Building ${PHP_IMAGE_NAME} from ${PHP_DOCKERFILE_PATH}."
    docker build -t "${PHP_IMAGE_NAME}" -f "${PHP_DOCKERFILE_PATH}" "${ROOT_DIR}" >/dev/null
}

start_container()
{
    cleanup_containers
    ensure_network
    log "Starting ${PG_IMAGE_NAME} as ${PG_CONTAINER_NAME}."
    docker run -d \
        --name "${PG_CONTAINER_NAME}" \
        --network "${PG_NETWORK_NAME}" \
        -e POSTGRES_DB="${PG_DATABASE_NAME}" \
        -e POSTGRES_USER="${PG_DATABASE_USER}" \
        -e POSTGRES_PASSWORD="${PG_DATABASE_PASSWORD}" \
        -p "127.0.0.1:${PG_HOST_PORT}:5432" \
        "${PG_IMAGE_NAME}" >/dev/null

    wait_for_postgres
}

print_env()
{
    cat <<EOF
export WAREHOUSE_DB_DSN='${DSN}'
export WAREHOUSE_DB_USER='${PG_DATABASE_USER}'
export WAREHOUSE_DB_PASSWORD='${PG_DATABASE_PASSWORD}'
export WAREHOUSE_DB_SCHEMA_FILE='${PG_SCHEMA_FILE}'
EOF
}

run_checks()
{
    log "Running PostgreSQL-backed smoke and adapter PHPUnit checks."
    docker run --rm \
        --network "${PG_NETWORK_NAME}" \
        -v "${ROOT_DIR}:/app" \
        -w /app \
        -e COMPOSER_ALLOW_SUPERUSER=1 \
        -e COMPOSER_ROOT_VERSION=dev-main \
        -e WAREHOUSE_DB_DSN="${DSN}" \
        -e WAREHOUSE_DB_USER="${PG_DATABASE_USER}" \
        -e WAREHOUSE_DB_PASSWORD="${PG_DATABASE_PASSWORD}" \
        -e WAREHOUSE_DB_SCHEMA_FILE="${PG_CONTAINER_SCHEMA_FILE}" \
        "${PHP_IMAGE_NAME}" \
        bash -lc 'git config --global --add safe.directory /app && composer test:db-smoke && vendor/bin/phpunit --filter PdoReferenceAdapterTest'
}

usage()
{
    cat <<EOF
Usage: bash tools/run-postgres-verification-docker.sh [start|test|stop|env]

  start  Build PHP verification image, start PostgreSQL container and print export commands.
  test   Build PHP verification image, start PostgreSQL, run DB smoke + adapter PHPUnit, then stop it unless WAREHOUSE_PGSQL_KEEP_CONTAINER=1.
  stop   Remove the PostgreSQL container and helper network.
  env    Print the env vars expected by the verification commands.
EOF
}

COMMAND="${1:-test}"

case "${COMMAND}" in
    start)
        build_php_runtime
        start_container
        print_env
        ;;
    test)
        if [ "${KEEP_CONTAINER}" != "1" ]; then
            trap cleanup_containers EXIT
        fi
        build_php_runtime
        start_container
        run_checks
        ;;
    stop)
        cleanup_containers
        ;;
    env)
        print_env
        ;;
    *)
        usage
        exit 1
        ;;
esac
