#!/bin/sh
# Starts one of the app's processes: web (default), worker or scheduler. Any other command is run as given,
# e.g. `docker compose run --rm app php artisan tinker`.
#
# The web container prepares everything on start: an APP_KEY (generated once and kept in the storage volume when
# none is set), migrations, the dataset on first run, and the search index. The worker and scheduler wait for it.
set -e
cd /var/www/html

KEY_FILE=storage/app/.docker-app-key
READY_FILE=storage/app/.docker-ready
role="${1:-web}"

wait_for() {
    php docker/wait-for.php "$1" "$2" "${3:-120}"
}

load_app_key() {
    if [ -z "$APP_KEY" ] && [ -f "$KEY_FILE" ]; then
        APP_KEY="$(cat "$KEY_FILE")"
        export APP_KEY
    fi
}

prepare() {
    load_app_key
    if [ -z "$APP_KEY" ]; then
        APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        export APP_KEY
        printf '%s' "$APP_KEY" > "$KEY_FILE"
        chown www-data:www-data "$KEY_FILE"
        chmod 600 "$KEY_FILE"
        echo "Generated APP_KEY and saved it in the storage volume. Set APP_KEY to manage it yourself."
    fi

    wait_for "${DB_HOST:-mysql}" "${DB_PORT:-3306}" 180
    php artisan migrate --force

    # First run: load database/dumps (DumpSeeder) unless told not to.
    if [ "${SEED_DATABASE:-auto}" = "always" ] || { [ "${SEED_DATABASE:-auto}" = "auto" ] && ! php artisan app:has-data >/dev/null; }; then
        echo "Loading the dataset (database/dumps)..."
        php artisan db:seed --force
        NEEDS_INDEX=1
    fi

    if [ "${SCOUT_DRIVER:-meilisearch}" = "meilisearch" ] && [ -n "$MEILISEARCH_HOST" ]; then
        host="$(echo "$MEILISEARCH_HOST" | sed -E 's#^https?://##; s#[:/].*##')"
        port="$(echo "$MEILISEARCH_HOST" | sed -nE 's#^https?://[^:/]+:([0-9]+).*#\1#p')"
        if wait_for "$host" "${port:-7700}" 60; then
            if [ -n "$NEEDS_INDEX" ] || [ ! -f storage/app/.docker-indexed ]; then
                php artisan search:reindex && touch storage/app/.docker-indexed
            fi
        else
            echo "Meilisearch isn't reachable; search will be empty until it is (then run: php artisan search:reindex)."
        fi
    fi

    php artisan storage:link >/dev/null 2>&1 || true
    php artisan optimize
    chown -R www-data:www-data storage bootstrap/cache
    touch "$READY_FILE"
}

wait_until_ready() {
    i=0
    while [ ! -f "$READY_FILE" ] && [ $i -lt 600 ]; do
        sleep 2
        i=$((i + 2))
    done
    load_app_key
    php artisan optimize >/dev/null
}

case "$role" in
    web)
        rm -f "$READY_FILE"
        prepare
        exec apache2-foreground
        ;;
    worker)
        wait_until_ready
        exec setpriv --reuid=www-data --regid=www-data --init-groups php artisan horizon
        ;;
    scheduler)
        wait_until_ready
        exec setpriv --reuid=www-data --regid=www-data --init-groups php artisan schedule:work
        ;;
    *)
        load_app_key
        exec "$@"
        ;;
esac
