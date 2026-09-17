# =========================================================
# Apex Global HRMS — Production Docker Image
# Compatible with Render, Railway, Fly.io, and Cloud Run
# =========================================================

FROM php:8.2-apache

# Install SQLite libraries & required PHP PDO drivers
RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo_mysql pdo_sqlite \
    && a2enmod rewrite headers \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Fix Windows CRLF line endings on entrypoint script and grant execution permissions
RUN sed -i -e 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh

# Expose standard HTTP port (can be overridden dynamically by $PORT on Render/Railway)
EXPOSE 80

# Execute entrypoint
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
