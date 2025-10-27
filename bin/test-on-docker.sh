#!/bin/bash

docker run --rm -v .:/forms-bridge -w /forms-bridge --name forms-bridge-tests codeccoop/wp-test sh -c "
nohup docker-entrypoint.sh mariadbd >/dev/null 2>&1 &
echo -n 'Install composer dependencies: '
composer -q install
echo '✅'
echo -n 'Wait for mariadb to start for three seconds: '
sleep 3
echo '✅'
echo -n 'Install wordpress test suite: '
TMPDIR=/opt bin/install-wp-tests.sh >/dev/null 2>&1
echo '✅'

echo 'Run tests! 🚀'
echo

vendor/bin/phpunit
"
