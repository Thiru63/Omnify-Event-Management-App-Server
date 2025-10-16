# Use official PHP image with required extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev zip unzip libonig-dev libzip-dev && \
    docker-php-ext-install pdo pdo_pgsql mbstring zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy the environment file if not present
RUN cp .env.example .env

# Generate app key
RUN php artisan key:generate

# Create SQLite database and set permissions
RUN mkdir -p database && \
    touch database/database.sqlite && \
    chmod 775 database/database.sqlite && \
    chmod -R 775 storage/ && \
    chmod -R 775 bootstrap/cache/

# Publish Swagger views
RUN php artisan vendor:publish --tag=l5-swagger-views --force

# COMPLETELY REPLACE the Swagger view with HTTPS CDN URLs
RUN rm -f resources/views/vendor/l5-swagger/index.blade.php
RUN echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Event Management API</title><link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" /><link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.9.0/favicon-32x32.png" sizes="32x32" /><link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.9.0/favicon-16x16.png" sizes="16x16" /><style>html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; } *, *:before, *:after { box-sizing: inherit; } body { margin: 0; background: #fafafa; }</style></head><body><div id="swagger-ui"></div><script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script><script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script><script>window.onload = function() { const ui = SwaggerUIBundle({ url: "https://omnify-event-management-app-server.onrender.com/api-docs.json", dom_id: "#swagger-ui", deepLinking: true, presets: [ SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset ], plugins: [ SwaggerUIBundle.plugins.DownloadUrl ], layout: "StandaloneLayout" }); window.ui = ui; };</script></body></html>' > resources/views/vendor/l5-swagger/index.blade.php

# Generate Swagger docs
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force
RUN php artisan l5-swagger:generate

# Clear cache without database dependencies
RUN php artisan config:clear
RUN php artisan view:clear

# Expose port 8000 and start Laravel server
EXPOSE 8000

# Run migrations and cache clear in the CMD (after tables are created)
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000