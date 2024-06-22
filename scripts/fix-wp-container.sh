#!/bin/bash
echo "Switch container's user UID and GID to current user"
X_UID=$(id -u)
X_GID=$(id -g)
docker exec -i $(docker compose ps -q wordpress) sh -c "usermod -u $X_UID www-data"
docker exec -i $(docker compose ps -q wordpress) sh -c "groupmod -g $X_GID www-data"
set +e
docker exec -i $(docker compose ps -q wordpress) sh -c "groupdel dialout"
docker exec -i $(docker compose ps -q wordpress) sh -c "find / -uid 33 -exec chown -h $X_UID {} +"
docker exec -i $(docker compose ps -q wordpress) sh -c "find / -gid 33 -exec chown -h $X_GID {} +"
set -e
docker exec -i $(docker compose ps -q wordpress) sh -c "a2enmod substitute && a2enmod headers && apache2ctl -k graceful"
docker restart $(docker compose ps -q wordpress)
echo "Install composer and dependencies"
docker exec -i $(docker compose ps -q wordpress) php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
docker exec -i $(docker compose ps -q wordpress) sh -c "php composer-setup.php \
    && mv composer.phar /usr/local/bin/composer && ((mkdir /var/www/.composer && chown www-data /var/www/.composer) || true) \
    && (unzip > /dev/null || (apt update && apt install -y unzip))"
docker exec -i $(docker compose ps -q wordpress) su -l www-data -s /bin/bash -c 'cd /var/www/html/ && composer install'
echo "Done"