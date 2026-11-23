#!/bin/sh
# Build the schema the Data test suite runs against. The migrations import database/dumps themselves
# (App\Traits\ImportTable), so this is a migrate:fresh into a separate schema plus the one dump no migration loads.
#
#   DB_HOST=127.0.0.1 DB_USERNAME=kanab DB_PASSWORD=kanab TEST_DATA_DB_DATABASE=kanab_data tests/Data/load-dumps.sh
set -e
cd "$(dirname "$0")/../.."

: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${DB_USERNAME:=kanab}"
: "${DB_PASSWORD:=kanab}"
: "${TEST_DATA_DB_DATABASE:=kanab_data}"

MYSQL="mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD"
$MYSQL -e "CREATE DATABASE IF NOT EXISTS \`$TEST_DATA_DB_DATABASE\`"

DB_CONNECTION=mysql DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USERNAME="$DB_USERNAME" DB_PASSWORD="$DB_PASSWORD" \
    DB_DATABASE="$TEST_DATA_DB_DATABASE" DB_IMPORT_DUMPS=true APP_ENV=testing CACHE_DRIVER=array \
    APP_KEY=base64:3xK4dQ8bH6m2vY1nW9sT0pL5cR7aE3fG2hJ4kM6nP8q= \
    php artisan migrate:fresh --force

# No migration imports resource_busy.
$MYSQL "$TEST_DATA_DB_DATABASE" < database/dumps/resource_busy.sql
