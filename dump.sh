#!/bin/sh
export $(grep -v '^#' .env | xargs)
mysqldump -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD --no-create-info --complete-insert $DB_DATABASE $1 > ./database/dumps/$1.sql
