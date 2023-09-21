mysqldump -h localhost --no-create-info --complete-insert kanabapi $1 > ./database/dumps/$1.sql
