#!/bin/bash
read -p "This will delete all Harvest docker containers and images, losing all changes and requiring significant download upon next start. Are you sure? " -n 1 -r 
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]
then
  echo ""
else
  echo "Aborted."
  exit -1
fi
docker compose down --rmi all
cd ../harvest-web
docker compose down --rmi all
docker rmi $(docker images 'harvest-web-base:latest' -a -q)
echo "Done."
