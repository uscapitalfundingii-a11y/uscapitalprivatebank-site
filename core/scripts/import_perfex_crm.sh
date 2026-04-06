#!/bin/bash
set -euo pipefail

DB_HOST="crm.uscapitalprivatebank.com"
DB_NAME="crmuscpb"
DB_USER="uscpbcrm"
DB_PASS="1995?+DM=blessing#$"
SQL_FILE="/home/dh_9a4ezr/uscapitalprivatebank.com/crm/sitebackup/source_artifacts/CRM.sql"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "SHOW TABLES LIKE 'tblstaff'; SELECT COUNT(*) AS staff_count FROM tblstaff;"
