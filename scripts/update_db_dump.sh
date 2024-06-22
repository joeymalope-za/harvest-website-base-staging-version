#!/bin/bash
# This script dumps local database from docker container. Run it to update dump on Github.
set -e

if [ $(basename $(pwd)) = 'scripts' ]; then
    cd ..
fi

LOCAL=1 DB_PASSWORD=harvestj scripts/export.sh

if [ $? -eq 0 ]; then
    echo "DB dump updated successfully"
else
    echo "DB dump failed, is MySQL container running?"
fi