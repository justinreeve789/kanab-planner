#!/bin/sh
export $(grep -v '^#' .env | xargs)
mysqldump -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD --complete-insert $DB_DATABASE > ~/visitkanab.sql
gzip -f ~/visitkanab.sql
