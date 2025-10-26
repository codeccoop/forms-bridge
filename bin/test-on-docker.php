#!/bin/bash

docker run --rm -v .:/forms-bridge -w /forms-bridge --name forms-bridge-tests codeccoop/wp-test sh -c "
nohup docker-entrypoint.sh mariadbd &
sleep 5
composer install
bin/install-wp-tests.sh
vendor/bin/phpunit
"
