#!/bin/bash
chown -R www-data:www-data /var/www/moodledata
chmod -R 0770 /var/www/moodledata

exec "$@"