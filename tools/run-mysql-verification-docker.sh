#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONTAINER_NAME="${WAREHOUSE_MYSQL_CONTAINER_NAME:-warehouse-core-mysql-verification}"
IMAGE_NAME="${WAREHOUSE_MYSQL_IMAGE:-mysql:8.0}"
HOST_PORT="${WAREHOUSE_MYSQL_HOST_PORT:-33067}"
DATABASE_NAME="${WAREHOUSE_MYSQL_DATABASE:-warehouse_core_test}"
DATABASE_USER="${WAREHOUSE_MYSQL_USER:-warehouse}"
DATABASE_PASSWORD="${WAREHOUSE_MYSQL_PASSWORD:-warehouse}"
ROOT_PASSWORD="${WAREHOUSE_MYSQL_ROOT_PASSWORD:-rootpass}"
SCHEMA_FILE="${WAREHOUSE_DB_SCHEMA_FILE:-$ROOT_DIR/database/schema/mysql.sql}"
KEEP_CONTAINER="${WAREHOUSE_MYSQL_KEEP_CONTAINER:-0}"
DSN="mysql:host=127.0.0.1;port=${HOST_PORT};dbname=${DATABASE_NAME};charset=utf8mb4"

log()
{
    printf '[mysql-verify] %s\n' "$1"
}

run_cleanup()
{
    if docker ps -aq --filter "name=^/${CONTAINER_NAME}$" | grep -q .; then
        docker rm -f "${CONTAINER_NAME}" >/dev/null 2>&1 || true
    fi
}

wait_for_mysql()
{
    local attempt

    for attempt in $(seq 1 60); do
        if WAREHOUSE_DB_DSN="${DSN}" \
            WAREHOUSE_DB_USER="${DATABASE_USER}" \
            WAREHOUSE_DB_PASSWORD="${DATABASE_PASSWORD}" \
            php -r '
                try {
                    $pdo = new PDO(getenv("WAREHOUSE_DB_DSN"), getenv("WAREHOUSE_DB_USER"), getenv("WAREHOUSE_DB_PASSWORD"));
                    $pdo->query("SELECT 1");
                    exit(0);
                } catch (Exception $exception) {
                    exit(1);
                }
            '; then
            log "MySQL is ready on port ${HOST_PORT}."
            return 0
        fi

        sleep 2
    done

    log "Timed out while waiting for MySQL."
    docker logs "${CONTAINER_NAME}" || true
    return 1
}

start_container()
{
    run_cleanup
    log "Starting ${IMAGE_NAME} as ${CONTAINER_NAME} on 127.0.0.1:${HOST_PORT}."
    docker run -d \
        --name "${CONTAINER_NAME}" \
        -e MYSQL_ROOT_PASSWORD="${ROOT_PASSWORD}" \
        -e MYSQL_DATABASE="${DATABASE_NAME}" \
        -e MYSQL_USER="${DATABASE_USER}" \
        -e MYSQL_PASSWORD="${DATABASE_PASSWORD}" \
        -p "127.0.0.1:${HOST_PORT}:3306" \
        "${IMAGE_NAME}" >/dev/null

    wait_for_mysql
}

print_env()
{
    cat <<EOF
export WAREHOUSE_DB_DSN='${DSN}'
export WAREHOUSE_DB_USER='${DATABASE_USER}'
export WAREHOUSE_DB_PASSWORD='${DATABASE_PASSWORD}'
export WAREHOUSE_DB_SCHEMA_FILE='${SCHEMA_FILE}'
EOF
}

run_checks()
{
    log "Running MySQL-backed smoke and adapter PHPUnit checks."
    (
        cd "${ROOT_DIR}"
        export WAREHOUSE_DB_DSN="${DSN}"
        export WAREHOUSE_DB_USER="${DATABASE_USER}"
        export WAREHOUSE_DB_PASSWORD="${DATABASE_PASSWORD}"
        export WAREHOUSE_DB_SCHEMA_FILE="${SCHEMA_FILE}"
        composer test:db-smoke
        vendor/bin/phpunit --filter PdoReferenceAdapterTest
    )
}

usage()
{
    cat <<EOF
Usage: bash tools/run-mysql-verification-docker.sh [start|test|stop|env]

  start  Start MySQL container and print export commands.
  test   Start MySQL container, run DB smoke + adapter PHPUnit, then stop it unless WAREHOUSE_MYSQL_KEEP_CONTAINER=1.
  stop   Remove the MySQL container.
  env    Print the env vars expected by the verification commands.
EOF
}

COMMAND="${1:-test}"

case "${COMMAND}" in
    start)
        start_container
        print_env
        ;;
    test)
        if [ "${KEEP_CONTAINER}" != "1" ]; then
            trap run_cleanup EXIT
        fi
        start_container
        run_checks
        ;;
    stop)
        run_cleanup
        ;;
    env)
        print_env
        ;;
    *)
        usage
        exit 1
        ;;
esac
