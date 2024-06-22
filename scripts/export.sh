#!/bin/bash
# This script downloads all wordpress files and database dump omitting sensitive information and patching things for dev environment.
LOCAL=${LOCAL:-0}
WP_HOST=${WP_HOST:-harvest-staging-new.net}
WP_OS_USER=${WP_OS_USER:-ubuntu}
SSH_PASSWORD=${SSH_PASSWORD}
SSH_PORT=${SSH_PORT:-22}
SERVER_DIR=${SERVER_DIR:-/home/ubuntu/harvest-website/public}
DB_PASSWORD=${DB_PASSWORD:-harvestj}
DB_HOST=${DB_HOST:-localhost}
DB_USER=harvestj
DATABASE=harvestj
SITE_DOMAINS=${SITE_DOMAINS:-harvest-staging-new.net,id-api.harvest-staging-new.net,id-web.harvest-staging-new.net}
LOCAL_DOMAINS=harvest.local,id-api.local:3000,id-web.local:3002
DB_FILE=dump.sql
# WARNING: if editing, make sure to also add corresponding DROP statement removal in the bottom of deploy_wp.sh, otherwise
# it will clear the table upon every deploy
EXCLUDED_TABLES=(
wp_users
wp_usermeta
wp_posts
wp_woocommerce_order_items
)
DB_ENV_CMD="ssh -p${SSH_PORT} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ${WP_OS_USER}@${WP_HOST} "

set -e

if [ $LOCAL == 1 ]; then
   DB_ENV_CMD="docker exec -i $(docker compose ps -q mariadb) sh -c"
fi

if [ "${DB_PASSWORD}" = "" ]; then
    echo 'DB_PASSWORD not set'
    exit -1
fi

if [ $(basename $(pwd)) = 'scripts' ]; then
    cd ..
fi

if [ $LOCAL == 0 ]; then
  echo "Starting WordPress export from ${WP_HOST}:${SSH_PORT}"

  echo "Exporting WordPress files..."
  rsync --delete -vhazO --no-perms --checksum -e "ssh -p${SSH_PORT} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null" --exclude ./public/vendor/ --exclude wp-config.php --exclude=".*" ${WP_OS_USER}@${WP_HOST}:${SERVER_DIR}/ ./public

  # add .htaccess to rewrite https to http
  echo "AddOutputFilterByType SUBSTITUTE text/html
  Substitute s|https://harvest.local|http://harvest.local|ni
  Header edit Location ^https:\/\/harvest.local http://harvest.local
  " > public/.htaccess

  # clear cache
  rm -rf public/wp-content/cache/

  set +e
  git add public/*
  set -e
fi

echo "Creating database dump..."

IGNORED_TABLES_STRING=''
for TABLE in "${EXCLUDED_TABLES[@]}"
do :
    IGNORED_TABLES_STRING+=" --ignore-table=${DATABASE}.${TABLE}"
done

echo "Dumping structure..."
$DB_ENV_CMD "mysqldump --protocol=tcp --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} --single-transaction --databases --no-data --routines ${DATABASE}" > data/${DB_FILE}

echo "Dumping content..."
$DB_ENV_CMD "mysqldump --protocol=tcp --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} ${DATABASE} --no-create-info  --single-transaction --replace --skip-opt --complete-insert  --skip-triggers ${IGNORED_TABLES_STRING}" >> data/${DB_FILE}

echo "Dumping posts except orders..."
$DB_ENV_CMD "mysqldump --protocol=tcp --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} ${DATABASE} wp_posts --no_create_info  --single-transaction --replace --skip-opt --complete-insert  --where 'post_type not in (\"shop_order\")'" >> data/${DB_FILE}

# replace original domain
python3 scripts/db_url_fixer.py --src_domains ${SITE_DOMAINS} --dst_domains ${LOCAL_DOMAINS}

echo "Done!"
