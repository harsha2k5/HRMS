#!/bin/bash
set -e

# Dynamically bind Apache to cloud-assigned $PORT (Render, Railway, Fly.io, Heroku, etc.)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure required runtime directories exist and have proper permissions for www-data
mkdir -p /var/www/html/storage/uploads/contracts \
         /var/www/html/storage/uploads/certificates \
         /var/www/html/storage/uploads/identity \
         /var/www/html/storage/uploads/policies \
         /var/www/html/storage/uploads/resumes \
         /var/www/html/storage/uploads/avatars \
         /var/www/html/storage/uploads/general \
         /var/www/html/database

chown -R www-data:www-data /var/www/html/storage /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/database

echo "Starting Apache in foreground..."
exec apache2-foreground
