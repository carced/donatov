#!/bin/bash
set -e

mkdir -p /var/www/html/data /var/www/html/storage
chown -R www-data:www-data /var/www/html/data /var/www/html/storage 2>/dev/null \
    || chmod -R 777 /var/www/html/data /var/www/html/storage

exec "$@"
