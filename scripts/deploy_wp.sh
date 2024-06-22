#!/bin/bash
set -e
# Deploys WordPress files and the database dump stored in the repository, replacing domains in the database. Can work with SSH.
# Intended for GitHub Actions runner
WP_HOST=${WP_HOST:-54.66.134.156}
WP_OS_USER=${WP_OS_USER:-ubuntu}
SSH_PASSWORD=${SSH_PASSWORD}
# Staging port here
SSH_PORT=22
SERVER_DIR=${SERVER_DIR:-/home/ubuntu/harvest-website/public/}
WP_PASSWORD=${WP_PASSWORD:-D7OHN34l5mfjsk9kMhBJ}
DB_PASSWORD=${DB_PASSWORD:-harvestj}
DB_HOST=${DB_HOST:-localhost}
DB_USER=harvestj
DATABASE=harvestj
SITE_DOMAINS=${SITE_DOMAINS:-harvest-staging-new.net,id-api.harvest-staging-new.net,id-web.harvest-staging-new.net}
LOCAL_DOMAINS=harvest.local,id-api.local:3000,id-web.local:3002
DB_FILE=dump.sql

DB_ENV_CMD="ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p${SSH_PORT} ${WP_OS_USER}@${WP_HOST} "

set -e

if [ $(basename $(pwd)) = 'scripts' ]; then
    cd ..
fi

echo "Uploading WordPress files..."

# upload
rsync -havz --checksum -e "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p${SSH_PORT}" --exclude=".*" ./public/ ${WP_OS_USER}@${WP_HOST}:${SERVER_DIR}
# clear cache
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p${SSH_PORT} ${WP_OS_USER}@${WP_HOST} "rm -rf ${SERVER_DIR}/wp-content/cache/"

echo "Uploading database dump..."

# put original domains back
python3 scripts/db_url_fixer.py --reverse --src_domains ${LOCAL_DOMAINS} --dst_domains ${SITE_DOMAINS}

# remove DROP statements for excluded tables
sed -i "s/DROP TABLE IF EXISTS \`wp_users\`;//g" data/dump.sql
sed -i "s/DROP TABLE IF EXISTS \`wp_usermeta\`;//g" data/dump.sql
sed -i "s/DROP TABLE IF EXISTS \`wp_posts\`;//g" data/dump.sql
sed -i "s/DROP TABLE IF EXISTS \`wp_woocommerce_order_items\`;//g" data/dump.sql

# replace values in seed file
sed -i "s/WP_PASSWORD/${WP_PASSWORD}/g" data/seed.sql
sed -i "s/DB_USER/${DB_USER}/g" data/seed.sql


if 	[[ $DB_HOST == *"rds.amazonaws.com" ]]; then
	sed -i '/ON *.*/d' data/seed.sql # RDS doesn't allow grant to *.*
	echo "Loading ${DB_FILE}"
	$DB_ENV_CMD "cd ${SERVER_DIR}../ && mysql -vv -u${DB_USER} -p${DB_PASSWORD} -h${DB_HOST} --force" < data/$DB_FILE
	echo "Loading seed.sql"
	$DB_ENV_CMD "cd ${SERVER_DIR}../ && mysql -vv -u${DB_USER} -p${DB_PASSWORD} -h${DB_HOST} --force" < data/seed.sql
else
	echo "Loading ${DB_FILE}"
	$DB_ENV_CMD "cd ${SERVER_DIR}../ && docker exec -i \$(docker compose ps -q mariadb) mysql -uroot -p${DB_PASSWORD} -h${DB_HOST} --force" < data/$DB_FILE
	echo "Loading seed.sql"
	$DB_ENV_CMD "cd ${SERVER_DIR}../ && docker exec -i \$(docker compose ps -q mariadb) mysql -uroot -p${DB_PASSWORD} -h${DB_HOST} --force" < data/seed.sql
fi

echo "Done!"
