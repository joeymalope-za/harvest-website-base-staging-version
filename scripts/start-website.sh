#!/bin/bash

# This script creates, configures and launches a local docker environment for development
set -e
if [ $(basename $(pwd)) = 'scripts' ]; then
    cd ..
fi

# assume apple silicon if mac
if [[ "$OSTYPE" == "darwin"* ]]; then
  export DOCKER_DEFAULT_PLATFORM=linux/aarch64
else
  export DOCKER_DEFAULT_PLATFORM=linux/amd64
fi

WP_HOST="harvest.local"
# WP_VERSION=$(cat public/wp-includes/version.php|sed -n "s/\$wp_version = '//p"|sed "s/';//g")
# echo Detected WordPress version: $WP_VERSION

# if [ "$(docker ps -aq -f status=running -f name=harvest-website-wordpress-1)" ]; then
#     read -p "Wordpress container is already running. Database changes, which are not in the dump, will be lost. Are you sure? " -n 1 -r
#     echo ""
#     if [[ $REPLY =~ ^[Yy]$ ]]
#     then
#       echo ""
#     else
#       echo "Aborted. Use 'docker compose restart' to restart environment without losing the data."
#       exit -1
#     fi
# fi

echo Configuring dev environment...
HARVEST_ENV=development python3 scripts/set_env.py

# echo Building Harvest Web docker containers...
# if [ ! -d "../harvest-web" ]; then
#   echo "ERROR: harvest-web dir not found, it should be put adjacent to this repo"
#   exit -1
# fi

# cd ../harvest-web
# # the local dev image will have dirs mounted, so we don't care about missing npm packages
# BASE_TAG=harvest-web-base:latest
# IMAGE_TAG=harvest-web:latest

# if [[ "$(docker images -q $BASE_TAG 2> /dev/null)" == "" ]]; then
#   docker build -t $BASE_TAG -f Dockerfile-base .
# fi
# if [[ "$(docker images -q $IMAGE_TAG 2> /dev/null)" == "" ]]; then
#   docker build -t $IMAGE_TAG --build-arg BASE_IMAGE="$BASE_TAG" .
# fi

# echo Stopping Harvest Web docker containers...
# docker compose  --env-file .env --env-file .env-DEV down
# echo Starting Harvest Web docker containers...
# docker compose --env-file .env-all --env-file .env-DEV --env-file .env -f docker-compose.yml -f docker-compose.dev.yml up -d
# sleep 5
# echo Seeding the data...
# docker exec -i $(docker compose ps -q harvest-api.local 2>/dev/null) sh -c "npm run api:seed"

# exit -1

# cd ../harvest-website
# echo Building WordPress docker containers...
# WP_VERSION=$WP_VERSION docker compose build

# echo Starting WordPress docker containers...
# WP_PORT=80 docker compose up -d

# sleep 5
# switch container's www-data uid to current user, so WP wouldn't break file permission in the repo
echo "Switch container's user UID and GID to current user - ignore non-critical errors"
X_UID=$(id -u)
X_GID=$(id -g)
# on Mac, the usual GID is 20, which overlaps with non-essential group in container
docker exec -i $(docker compose ps -q wordpress) sh -c "groupdel dialout || true"
docker exec -i $(docker compose ps -q wordpress) sh -c "usermod -u $X_UID www-data"
docker exec -i $(docker compose ps -q wordpress) sh -c "groupmod -g $X_GID www-data"
set +e
docker exec -i $(docker compose ps -q wordpress) sh -c "find / -uid 33 -exec chown -h $X_UID {} +"
docker exec -i $(docker compose ps -q wordpress) sh -c "find / -gid 33 -exec chown -h $X_GID {} +"
set -e
echo "Install PHP dependencies..."
docker exec -i $(docker compose ps -q wordpress) php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
docker exec -i $(docker compose ps -q wordpress) sh -c "php composer-setup.php \
    && mv composer.phar /usr/local/bin/composer && ((mkdir /var/www/.composer && chown www-data /var/www/.composer) || true) \
    && (unzip > /dev/null || (apt update && apt install -y unzip))"
docker exec -i $(docker compose ps -q wordpress) su -l www-data -s /bin/bash -c 'cd /var/www/html/ && composer install'
## required to rewrite https links to http in response
#docker exec -i $(docker compose ps -q wordpress) sh -c "a2enmod substitute && a2enmod headers && apache2ctl -k graceful"
#docker restart $(docker compose ps -q wordpress)

# echo "Wait for DB to start (10 sec)..."
# sleep 10

# echo Deploying the database...
docker exec -i $(docker compose ps -q mariadb) mysql -uroot -pharvestj < data/dump.sql
# apply dev data
docker exec -i $(docker compose ps -q mariadb) mysql -uroot -pharvestj < data/dev.sql

echo "Done! Harvest deployed to http://$WP_HOST
See logs with 'docker compose logs -f --tail 20'."