#!/bin/sh
set -e

chown -R www-data:www-data /var/www/moodledata || true
chmod -R 0770 /var/www/moodledata || true

exec /usr/local/bin/docker-php-entrypoint "$@"