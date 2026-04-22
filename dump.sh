#!/bin/sh
export $(grep -v '^#' .env | xargs)
mysqldump -P 3307 -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD --no-create-info --no-tablespaces --complete-insert $DB_DATABASE $1 > ./database/dumps/$1.sql
gzip -f ./database/dumps/$1.sql
